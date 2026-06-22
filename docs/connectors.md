# Connectors

Manage connectors (charging ports) on a charging station.

---

## getConnectors

Returns all connectors for a charging station, ordered by `connector_id` ascending.

```php
$connectors = Yii::$app->ocpp->getConnectors('CHARGER001');

foreach ($connectors as $connector) {
    echo $connector['connector_id'] . ': ' . $connector['last_status'];
}
```

Example response item:

```json
{
  "id": 1,
  "charge_id": 1,
  "connector_id": 1,
  "last_status": "Available",
  "previous_status": "Unavailable",
  "error_code": null,
  "info": null,
  "vendor_id": null,
  "vendor_error_code": null,
  "last_updated": "2026-06-13T12:00:00Z"
}
```

| Field | Description |
|-------|-------------|
| `connector_id` | OCPP connector ID (1-based; `0` = entire charger) |
| `last_status` | Current status from last `StatusNotification` |
| `previous_status` | Status before the last update |
| `error_code` | OCPP error code, or `null` |
| `last_updated` | Timestamp of last `StatusNotification` |

---

## addConnector

Manually adds a connector to a charging station.  
The connector is created with `last_status = "Available"`.

```php
$connector = Yii::$app->ocpp->addConnector('CHARGER001', 2);
```

Throws an exception if `connector_id` already exists on this charger.
