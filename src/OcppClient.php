<?php

namespace carisoftware\ocpp;

use budimanlai\yii2pkg\library\Api3rdBase;
use Yii;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\web\BadRequestHttpException;

class OcppClient extends Api3rdBase
{
    public string $baseUrl = '';
    public string $path = '/api/v1/';
    public string $category = 'ocpp';

    public function __construct(int|string $userId = 0)
    {
        if ($userId === 0 && !Yii::$app->user->isGuest) {
            $userId = Yii::$app->user->id;
        }
        $this->user_id = $userId;
    }

    public function getHeaders(): array
    {
        return ['content-type' => 'application/json'];
    }

    private function data(mixed $response): mixed
    {
        return ArrayHelper::getValue($response, 'data');
    }

    /**
     * Send a PUT request to the API.
     * Logging is handled inline because the parent's properties are private.
     */
    public function put(string $endpoint, array $params = []): mixed
    {
        $headers = $this->getHeaders();
        $targetUrl = $this->baseUrl . $this->path . $endpoint;
        $start_time = microtime(true);

        Yii::$app->db->createCommand()->insert('api_3rd_log', [
            'category' => $this->category,
            'created_datetime' => date('Y-m-d H:i:s'),
            'method' => 'put',
            'url' => $targetUrl,
            'reff_id' => $this->user_id,
            'headers' => Json::encode($headers),
            'request_log' => Json::encode($params),
        ])->execute();
        $log_id = Yii::$app->db->getLastInsertID();

        try {
            $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
            $response = $client->createRequest()
                ->setFormat(Client::FORMAT_JSON)
                ->setMethod('PUT')
                ->addHeaders($headers)
                ->setData($params)
                ->setUrl($targetUrl)
                ->send();

            $latency = microtime(true) - $start_time;
            Yii::$app->db->createCommand(
                'UPDATE api_3rd_log SET response_log = :RESP, latency = :L WHERE id = :ID',
                [':RESP' => Json::encode($response->data), ':L' => $latency, ':ID' => $log_id]
            )->execute();

            if ($response->isOk) {
                return $response->data;
            } else {
                throw new \Exception($this->parseError($response->data));
            }
        } catch (Exception | InvalidArgumentException | BadRequestHttpException $e) {
            $latency = microtime(true) - $start_time;
            Yii::$app->db->createCommand(
                'UPDATE api_3rd_log SET response_log = :RESP, latency = :L WHERE id = :ID',
                [':RESP' => Json::encode(['message' => $e->getMessage()]), ':L' => $latency, ':ID' => $log_id]
            )->execute();
            throw new \Exception($e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Server Status
    // -------------------------------------------------------------------------

    public function getServerStatus(): array
    {
        return $this->data($this->get('status'));
    }

    // -------------------------------------------------------------------------
    // Chargers
    // -------------------------------------------------------------------------

    public function getChargers(array $params = []): array
    {
        return $this->get('chargers', $params);
    }

    public function createCharger(array $params): array
    {
        return $this->data($this->post('chargers', $params));
    }

    public function getCharger(string $chargePointId): array
    {
        return $this->data($this->get("chargers/{$chargePointId}"));
    }

    public function updateCharger(string $chargePointId, array $params): void
    {
        $this->put("chargers/{$chargePointId}", $params);
    }

    public function deleteCharger(string $chargePointId): void
    {
        $this->delete("chargers/{$chargePointId}");
    }

    // -------------------------------------------------------------------------
    // Connectors
    // -------------------------------------------------------------------------

    public function getConnectors(string $chargePointId): array
    {
        return $this->data($this->get("chargers/{$chargePointId}/connectors"));
    }

    public function addConnector(string $chargePointId, int $connectorId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/connectors", [
            'connector_id' => $connectorId,
        ]));
    }

    // -------------------------------------------------------------------------
    // Charger commands
    // -------------------------------------------------------------------------

    public function disconnectCharger(string $chargePointId): void
    {
        $this->post("chargers/{$chargePointId}/disconnect", []);
    }

    public function remoteStart(string $chargePointId, array $params): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/remote-start", $params));
    }

    public function remoteStop(string $chargePointId, int $transactionId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/remote-stop", [
            'transaction_id' => $transactionId,
        ]));
    }

    public function reset(string $chargePointId, string $type = 'Soft'): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/reset", ['type' => $type]));
    }

    public function unlockConnector(string $chargePointId, int $connectorId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/unlock-connector", [
            'connector_id' => $connectorId,
        ]));
    }

    public function clearCache(string $chargePointId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/clear-cache", []));
    }

    public function changeAvailability(string $chargePointId, int $connectorId, string $type): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/change-availability", [
            'connector_id' => $connectorId,
            'type' => $type,
        ]));
    }

    public function changeConfiguration(string $chargePointId, string $key, string $value): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/change-configuration", [
            'key' => $key,
            'value' => $value,
        ]));
    }

    public function getConfiguration(string $chargePointId, ?string $key = null): array
    {
        $params = $key !== null ? ['key' => $key] : [];
        return $this->data($this->get("chargers/{$chargePointId}/configuration", $params));
    }

    public function triggerMessage(string $chargePointId, string $requestedMessage, ?int $connectorId = null): array
    {
        $params = ['requested_message' => $requestedMessage];
        if ($connectorId !== null) {
            $params['connector_id'] = $connectorId;
        }
        return $this->data($this->post("chargers/{$chargePointId}/trigger-message", $params));
    }

    public function reserveNow(string $chargePointId, array $params): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/reserve-now", $params));
    }

    public function cancelReservation(string $chargePointId, int $reservationId): array
    {
        return $this->data($this->post("chargers/{$chargePointId}/cancel-reservation", [
            'reservation_id' => $reservationId,
        ]));
    }

    public function getLocalListVersion(string $chargePointId): int
    {
        $result = $this->data($this->get("chargers/{$chargePointId}/local-list-version"));
        return (int) ArrayHelper::getValue($result, 'list_version', 0);
    }

    // -------------------------------------------------------------------------
    // Transactions
    // -------------------------------------------------------------------------

    public function getTransactions(array $params = []): array
    {
        return $this->get('transactions', $params);
    }

    public function getTransaction(int $id): array
    {
        return $this->data($this->get("transactions/{$id}"));
    }

    // -------------------------------------------------------------------------
    // Tags
    // -------------------------------------------------------------------------

    public function getTags(array $params = []): array
    {
        return $this->get('tags', $params);
    }

    public function createTag(array $params): array
    {
        return $this->data($this->post('tags', $params));
    }

    public function getTag(string $idToken): array
    {
        return $this->data($this->get("tags/{$idToken}"));
    }

    public function updateTag(string $idToken, array $params): void
    {
        $this->put("tags/{$idToken}", $params);
    }

    public function deleteTag(string $idToken): void
    {
        $this->delete("tags/{$idToken}");
    }
}
