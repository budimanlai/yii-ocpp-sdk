# Charger Commands

Send OCPP commands to a connected charging station.

All command methods throw an `\Exception` if:
- The charger is not connected
- The charger did not respond within 30 seconds (HTTP 502)

---

## disconnectCharger

Force-disconnects a charger without removing it from the database.

```php
Yii::$app->ocpp->disconnectCharger('CHARGER001');
```

---

## remoteStart

Validates the tag, creates a pending transaction, then sends `RemoteStartTransaction`.  
Waits up to **30 seconds** for the charger's `StartTransaction` response.

```php
$result = Yii::$app->ocpp->remoteStart('CHARGER001', [
    'connector_id'        => 1,
    'id_tag'              => 'TAG001',
    'csms_transaction_id' => 99001,
    'user_id'             => 5,
    'tenant_id'           => 2,
    'assets_id'           => 10,
]);

echo $result['status'];               // 'Accepted' or 'Rejected'
echo $result['ocpp_transaction_id'];  // use this for remoteStop
echo $result['meter_start'];          // Wh at session start
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `connector_id` | int | yes | Target connector (≥ 1) |
| `id_tag` | string | yes | Must exist in `oc_tags` |
| `csms_transaction_id` | int | yes | CSMS-side transaction ID (idempotency key) |
| `user_id` | int | yes | CSMS user who initiated the session |
| `tenant_id` | int | no | Optional tenant context |
| `assets_id` | int | no | Optional asset association |

---

## remoteStop

Sends `RemoteStopTransaction` and waits up to **30 seconds** for `StopTransaction`.  
Returns stored data immediately if the transaction is already ended.

```php
$result = Yii::$app->ocpp->remoteStop('CHARGER001', $ocppTransactionId);

echo $result['status'];      // 'Accepted'
echo $result['total_wh'];    // energy delivered in Wh
echo $result['duration'];    // session duration in seconds
echo $result['meter_stop'];  // Wh at session end
```

---

## reset

Sends a `Reset` command.

```php
// Soft reset — charger finishes active transaction, then reboots
Yii::$app->ocpp->reset('CHARGER001', 'Soft');

// Hard reset — immediate reboot
Yii::$app->ocpp->reset('CHARGER001', 'Hard');
```

---

## unlockConnector

Sends `UnlockConnector`.

```php
$result = Yii::$app->ocpp->unlockConnector('CHARGER001', 1);
echo $result['status']; // 'Unlocked', 'UnlockFailed', or 'NotSupported'
```

---

## clearCache

Sends `ClearCache`.

```php
$result = Yii::$app->ocpp->clearCache('CHARGER001');
echo $result['status']; // 'Accepted' or 'Rejected'
```

---

## changeAvailability

Sends `ChangeAvailability`. Use `connector_id = 0` to target the entire charger.

```php
// Take entire charger offline
$result = Yii::$app->ocpp->changeAvailability('CHARGER001', 0, 'Inoperative');

// Bring connector 1 back online
$result = Yii::$app->ocpp->changeAvailability('CHARGER001', 1, 'Operative');

echo $result['status']; // 'Accepted', 'Rejected', or 'Scheduled'
```

---

## changeConfiguration

Sends `ChangeConfiguration`.

```php
$result = Yii::$app->ocpp->changeConfiguration('CHARGER001', 'HeartbeatInterval', '60');
echo $result['status']; // 'Accepted', 'Rejected', 'RebootRequired', or 'NotSupported'
```

---

## getConfiguration

Sends `GetConfiguration` and returns current key/value pairs.

```php
// All keys
$config = Yii::$app->ocpp->getConfiguration('CHARGER001');

// Single key
$config = Yii::$app->ocpp->getConfiguration('CHARGER001', 'HeartbeatInterval');

foreach ($config['configuration_key'] as $item) {
    echo $item['key'] . ' = ' . $item['value'];
    echo $item['readonly'] ? ' (readonly)' : '';
}
```

---

## triggerMessage

Sends `TriggerMessage`.

Common `requested_message` values:
`BootNotification`, `DiagnosticsStatusNotification`, `FirmwareStatusNotification`,
`Heartbeat`, `MeterValues`, `StatusNotification`.

```php
// Trigger on all connectors
$result = Yii::$app->ocpp->triggerMessage('CHARGER001', 'StatusNotification');

// Trigger on a specific connector
$result = Yii::$app->ocpp->triggerMessage('CHARGER001', 'MeterValues', 1);

echo $result['status']; // 'Accepted' or 'Rejected'
```

---

## reserveNow

Sends `ReserveNow`.

```php
$result = Yii::$app->ocpp->reserveNow('CHARGER001', [
    'connector_id'   => 1,
    'expiry_date'    => '2026-12-31T23:59:59Z',
    'id_tag'         => 'TAG001',
    'reservation_id' => 7,
]);

echo $result['status']; // 'Accepted', 'Faulted', 'Occupied', 'Rejected', or 'Unavailable'
```

---

## cancelReservation

Sends `CancelReservation`.

```php
$result = Yii::$app->ocpp->cancelReservation('CHARGER001', 7);
echo $result['status']; // 'Accepted' or 'Rejected'
```

---

## getLocalListVersion

Retrieves the local authorization list version stored on the charger.

```php
$version = Yii::$app->ocpp->getLocalListVersion('CHARGER001');
echo $version; // e.g. 3
```
