# Chargers

Manage charging stations registered in the OCPP server.

---

## getChargers

Returns a paginated list of all registered charging stations.

```php
$result = Yii::$app->ocpp->getChargers();

// With filters and pagination
$result = Yii::$app->ocpp->getChargers([
    'page'                => 1,
    'limit'               => 20,
    'status'              => 'active',
    'registration_status' => 'Accepted',
]);

$chargers = $result['data'];
$total    = $result['meta']['total'];
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | int | Page number, 1-based (default `1`) |
| `limit` | int | Items per page (default `20`) |
| `status` | string | Filter by `status` field |
| `registration_status` | string | Filter by `registration_status` (`Accepted`, `Pending`, `Rejected`) |

---

## getCharger

Returns a single charging station by its `charge_point_id`.

```php
$charger = Yii::$app->ocpp->getCharger('CHARGER001');

echo $charger['name'];
echo $charger['is_online'] ? 'Online' : 'Offline';
echo $charger['last_heartbeat'];
```

---

## createCharger

Registers a new charging station. Throws an exception if `charge_point_id` already exists.

```php
$charger = Yii::$app->ocpp->createCharger([
    'charge_point_id'     => 'CHARGER001',
    'name'                => 'Charger Lantai 1',
    'ocpp_version'        => '1.6',
    'registration_status' => 'Accepted',
    'tenant_id'           => 2,
    'assets_id'           => 10,
    'num_connectors'      => 2,
]);
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `charge_point_id` | string | yes | Unique OCPP identity |
| `name` | string | yes | Display name |
| `ocpp_version` | string | no | Default `1.6` |
| `registration_status` | string | no | `Accepted` (default), `Pending`, `Rejected` |
| `tenant_id` | int | no | Optional tenant association |
| `assets_id` | int | no | Optional asset association |
| `num_connectors` | int | no | Connectors to pre-create (default `1`) |

> Setting `registration_status` to `Pending` or `Rejected` prevents the charger from connecting via OCPP.

---

## updateCharger

Updates fields on an existing charging station. Only supplied fields are changed.

```php
Yii::$app->ocpp->updateCharger('CHARGER001', [
    'name'                => 'Charger Lantai 2',
    'registration_status' => 'Rejected',
]);
```

---

## deleteCharger

Deletes a charging station from the database.

> The charger must be disconnected first. If still connected, it will be rejected on its next boot.

```php
Yii::$app->ocpp->deleteCharger('CHARGER001');
```
