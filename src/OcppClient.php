<?php

namespace carisoftware\ocpp;

use budimanlai\yii2pkg\library\Api3rdBase;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * OcppClient provides a Yii2 HTTP client for the OCPP REST API.
 *
 * Wraps all endpoints exposed by the go-ocpp-api server under `/api/v1`,
 * covering charger management, connector operations, OCPP commands,
 * transactions, and tag (RFID) management.
 *
 * Usage:
 * ```php
 * $client = new OcppClient();
 * $client->baseUrl = 'http://localhost:8100';
 * $charger = $client->getCharger('CHARGER001');
 * ```
 */
class OcppClient extends Api3rdBase
{
    /** @var string Base URL of the go-ocpp-api server (e.g. 'http://localhost:8100') */
    public string $baseUrl = '';

    /** @var string API path prefix appended to every endpoint */
    public string $path = '/api/v1/';

    /** @var string Log category used in `api_3rd_log` */
    public string $category = 'ocpp';

    /**
     * @param int|string $userId ID of the user initiating requests.
     *                           Defaults to the currently authenticated Yii user.
     */
    public function __construct(int|string $userId = 0)
    {
        if ($userId === 0 && !Yii::$app->user->isGuest) {
            $userId = Yii::$app->user->id;
        }
        $this->user_id = $userId;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return ['content-type' => 'application/json'];
    }

    /**
     * Extract the `data` field from an API response envelope.
     *
     * @param mixed $response Decoded response array from the API.
     * @return mixed Value of the `data` key, or `null` if not present.
     */
    private function data(mixed $response): mixed
    {
        return ArrayHelper::getValue($response, 'data');
    }

    // =========================================================================
    // Server Status
    // =========================================================================

    /**
     * Retrieve live server metrics (connected chargers, message counts).
     *
     * @return array{connected_chargers: int, total_msg_in: int, total_msg_out: int}
     */
    public function getServerStatus(): array
    {
        return $this->data($this->get('status'));
    }

    // =========================================================================
    // Chargers
    // =========================================================================

    /**
     * Return a paginated list of all registered charging stations.
     *
     * @param array $params Query parameters: `page`, `limit`, `status`, `registration_status`.
     * @return array Paginated envelope with `meta` (pagination) and `data` (station list).
     */
    public function getChargers(array $params = []): array
    {
        return $this->get('chargers', $params);
    }

    /**
     * Register a new charging station.
     *
     * @param array $params Required: `charge_point_id`, `name`.
     *                      Optional: `ocpp_version`, `registration_status`, `tenant_id`,
     *                                `assets_id`, `num_connectors`.
     * @return array Created Station object.
     * @throws \Exception On duplicate `charge_point_id` (HTTP 409) or validation failure.
     */
    public function createCharger(array $params): array
    {
        return $this->data($this->post('chargers', $params));
    }

    /**
     * Return a single charging station by its `charge_point_id`.
     *
     * @param string $chargePointId OCPP identity string (e.g. `'CHARGER001'`).
     * @return array Station object.
     * @throws \Exception If not found (HTTP 404).
     */
    public function getCharger(string $chargePointId): array
    {
        return $this->data($this->get("chargers/{$chargePointId}"));
    }

    /**
     * Update fields on an existing charging station.
     *
     * Only supplied fields are updated; all fields are optional.
     *
     * @param string $chargePointId OCPP identity string.
     * @param array  $params        Fields to update: `charge_point_id`, `name`, `ocpp_version`,
     *                              `registration_status`, `tenant_id`, `assets_id`.
     * @throws \Exception On duplicate `charge_point_id` (HTTP 409) or not found (HTTP 404).
     */
    public function updateCharger(string $chargePointId, array $params): void
    {
        $this->put("chargers/{$chargePointId}", $params);
    }

    /**
     * Delete a charging station from the database.
     *
     * The charger must be disconnected first; if still connected it will be
     * rejected on its next boot.
     *
     * @param string $chargePointId OCPP identity string.
     * @throws \Exception If not found (HTTP 404).
     */
    public function deleteCharger(string $chargePointId): void
    {
        $this->delete("chargers/{$chargePointId}");
    }

    // =========================================================================
    // Connectors
    // =========================================================================

    /**
     * Return all connectors for a charging station, ordered by `connector_id` ascending.
     *
     * @param string $chargePointId OCPP identity string.
     * @return array List of Connector objects.
     */
    public function getConnectors(string $chargePointId): array
    {
        return $this->data($this->get("chargers/{$chargePointId}/connectors"));
    }

    /**
     * Manually add a connector to a charging station.
     *
     * The connector is created with `last_status = "Available"`.
     *
     * @param string $chargePointId OCPP identity string.
     * @param int    $connectorId   OCPP connector ID (≥ 1).
     * @return array Created Connector object.
     * @throws \Exception On duplicate `connector_id` for the same charger (HTTP 409).
     */
    public function addConnector(string $chargePointId, int $connectorId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/connectors", [
            'connector_id' => $connectorId,
        ]));
    }

    // =========================================================================
    // Charger Commands
    // =========================================================================

    /**
     * Force-disconnect a currently connected charger without removing it from the database.
     *
     * @param string $chargePointId OCPP identity string.
     */
    public function disconnectCharger(string $chargePointId): void
    {
        $this->post("chargers/{$chargePointId}/disconnect", []);
    }

    /**
     * Validate the tag, create a pending transaction, then send `RemoteStartTransaction`.
     *
     * Waits up to 30 seconds for the charger to respond with `StartTransaction`.
     *
     * @param string $chargePointId OCPP identity string.
     * @param array  $params        Required: `connector_id`, `id_tag`, `csms_transaction_id`, `user_id`.
     *                              Optional: `tenant_id`, `assets_id`.
     * @return array{status: string, ocpp_transaction_id: int, csms_transaction_id: int,
     *               charge_point_id: string, id_tag: string, meter_start: int,
     *               tenant_id: int|null, assets_id: int|null}
     * @throws \Exception If charger is offline (HTTP 422), tag not found (HTTP 422),
     *                    or charger did not respond (HTTP 502).
     */
    public function remoteStart(string $chargePointId, array $params): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/remote-start", $params));
    }

    /**
     * Look up the transaction and send `RemoteStopTransaction` to the charger.
     *
     * Waits up to 30 seconds for the charger's `StopTransaction` message.
     * If the transaction already has `status = "Ended"`, returns stored data immediately.
     *
     * @param string $chargePointId OCPP identity string.
     * @param int    $transactionId OCPP transaction ID (`oc_transaction.id`).
     * @return array{status: string, ocpp_transaction_id: int, csms_transaction_id: int,
     *               charge_point_id: string, meter_start: int, meter_stop: int|null,
     *               total_wh: int|null, duration: int|null}
     * @throws \Exception If charger did not respond (HTTP 502).
     */
    public function remoteStop(string $chargePointId, int $transactionId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/remote-stop", [
            'transaction_id' => $transactionId,
        ]));
    }

    /**
     * Send a `Reset` command to the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @param string $type          `'Soft'` (finish active transaction then reboot) or `'Hard'` (immediate).
     * @return array{status: string} `status` is `'Accepted'` or `'Rejected'`.
     */
    public function reset(string $chargePointId, string $type = 'Soft'): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/reset", ['type' => $type]));
    }

    /**
     * Send an `UnlockConnector` command to the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @param int    $connectorId   Target connector ID.
     * @return array{status: string} `status` is `'Unlocked'`, `'UnlockFailed'`, or `'NotSupported'`.
     */
    public function unlockConnector(string $chargePointId, int $connectorId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/unlock-connector", [
            'connector_id' => $connectorId,
        ]));
    }

    /**
     * Send a `ClearCache` command to the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @return array{status: string} `status` is `'Accepted'` or `'Rejected'`.
     */
    public function clearCache(string $chargePointId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/clear-cache", []));
    }

    /**
     * Send a `ChangeAvailability` command to the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @param int    $connectorId   Connector ID; use `0` to target the entire charger.
     * @param string $type          `'Operative'` or `'Inoperative'`.
     * @return array{status: string} `status` is `'Accepted'`, `'Rejected'`, or `'Scheduled'`.
     */
    public function changeAvailability(string $chargePointId, int $connectorId, string $type): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/change-availability", [
            'connector_id' => $connectorId,
            'type' => $type,
        ]));
    }

    /**
     * Send a `ChangeConfiguration` command to the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @param string $key           Configuration key (e.g. `'HeartbeatInterval'`).
     * @param string $value         New value.
     * @return array{status: string} `status` is `'Accepted'`, `'Rejected'`, `'RebootRequired'`,
     *                               or `'NotSupported'`.
     */
    public function changeConfiguration(string $chargePointId, string $key, string $value): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/change-configuration", [
            'key' => $key,
            'value' => $value,
        ]));
    }

    /**
     * Send a `GetConfiguration` command and return current key/value pairs.
     *
     * @param string      $chargePointId OCPP identity string.
     * @param string|null $key           Optional — filter to a single configuration key.
     * @return array{configuration_key: array, unknown_key: array}
     */
    public function getConfiguration(string $chargePointId, ?string $key = null): array
    {
        $params = $key !== null ? ['key' => $key] : [];
        return $this->data($this->get("chargers/{$chargePointId}/configuration", $params));
    }

    /**
     * Send a `TriggerMessage` command to the charger.
     *
     * @param string   $chargePointId    OCPP identity string.
     * @param string   $requestedMessage Message type to trigger, e.g. `'StatusNotification'`,
     *                                   `'Heartbeat'`, `'MeterValues'`, `'BootNotification'`.
     * @param int|null $connectorId      Optional connector context.
     * @return array{status: string} `status` is `'Accepted'` or `'Rejected'`.
     */
    public function triggerMessage(string $chargePointId, string $requestedMessage, ?int $connectorId = null): array
    {
        $params = ['requested_message' => $requestedMessage];
        if ($connectorId !== null) {
            $params['connector_id'] = $connectorId;
        }
        return $this->data($this->post("chargers/{$chargePointId}/trigger-message", $params));
    }

    /**
     * Send a `ReserveNow` command to the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @param array  $params        Required: `connector_id`, `expiry_date`, `id_tag`, `reservation_id`.
     * @return array{status: string} `status` is `'Accepted'`, `'Faulted'`, `'Occupied'`,
     *                               `'Rejected'`, or `'Unavailable'`.
     */
    public function reserveNow(string $chargePointId, array $params): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/reserve-now", $params));
    }

    /**
     * Send a `CancelReservation` command to the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @param int    $reservationId Reservation ID to cancel.
     * @return array{status: string} `status` is `'Accepted'` or `'Rejected'`.
     */
    public function cancelReservation(string $chargePointId, int $reservationId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/cancel-reservation", [
            'reservation_id' => $reservationId,
        ]));
    }

    /**
     * Retrieve the local authorization list version stored on the charger.
     *
     * @param string $chargePointId OCPP identity string.
     * @return int Current list version number.
     */
    public function getLocalListVersion(string $chargePointId): int
    {
        $result = $this->data($this->get("chargers/{$chargePointId}/local-list-version"));
        return (int) ArrayHelper::getValue($result, 'list_version', 0);
    }

    // =========================================================================
    // Transactions
    // =========================================================================

    /**
     * Return a paginated list of transactions.
     *
     * @param array $params Query parameters: `page`, `limit`, `charge_id`, `status`.
     * @return array Paginated envelope with `meta` (pagination) and `data` (transaction list).
     */
    public function getTransactions(array $params = []): array
    {
        return $this->get('transactions', $params);
    }

    /**
     * Return a single transaction by its database ID.
     *
     * @param int $id Transaction database primary key.
     * @return array Transaction object.
     * @throws \Exception If not found (HTTP 404).
     */
    public function getTransaction(int $id): array
    {
        return $this->data($this->get("transactions/{$id}"));
    }

    // =========================================================================
    // Tags
    // =========================================================================

    /**
     * Return a paginated list of tags.
     *
     * @param array $params Query parameters: `page`, `limit`, `status`.
     * @return array Paginated envelope with `meta` (pagination) and `data` (tag list).
     */
    public function getTags(array $params = []): array
    {
        return $this->get('tags', $params);
    }

    /**
     * Create a new tag (RFID / id_token).
     *
     * @param array $params Required: `id_token`. Optional: `tag_type`, `status`, `additional_id_token`.
     * @return array Created Tag object.
     * @throws \Exception On duplicate `id_token` (HTTP 409).
     */
    public function createTag(array $params): array
    {
        return $this->data($this->post('tags', $params));
    }

    /**
     * Return a single tag by its `id_token`.
     *
     * @param string $idToken Tag token value (e.g. `'TAG001'`).
     * @return array Tag object.
     * @throws \Exception If not found (HTTP 404).
     */
    public function getTag(string $idToken): array
    {
        return $this->data($this->get("tags/{$idToken}"));
    }

    /**
     * Update fields on an existing tag.
     *
     * Only supplied fields are updated.
     *
     * @param string $idToken Tag token value.
     * @param array  $params  Fields to update: `tag_type`, `status`, `additional_id_token`.
     *                        Valid `status` values: `'Active'`, `'Blocked'`, `'Expired'`, `'Invalid'`.
     * @throws \Exception If not found (HTTP 404).
     */
    public function updateTag(string $idToken, array $params): void
    {
        $this->put("tags/{$idToken}", $params);
    }

    /**
     * Permanently delete a tag.
     *
     * @param string $idToken Tag token value.
     * @throws \Exception If not found (HTTP 404).
     */
    public function deleteTag(string $idToken): void
    {
        $this->delete("tags/{$idToken}");
    }
}
