# Transactions

Read charging session records from the OCPP server.

---

## getTransactions

Returns a paginated list of transactions.

```php
// All transactions
$result = Yii::$app->ocpp->getTransactions();

// With filters
$result = Yii::$app->ocpp->getTransactions([
    'page'      => 1,
    'limit'     => 20,
    'charge_id' => 1,
    'status'    => 'Ended',
]);

$transactions = $result['data'];
$total        = $result['meta']['total'];
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | int | Page number, 1-based (default `1`) |
| `limit` | int | Items per page (default `20`) |
| `charge_id` | int | Filter by charging station DB id |
| `status` | string | `Pending`, `Started`, `Charging`, `Ended`, `Rejected` |

---

## getTransaction

Returns a single transaction by its database ID.

```php
$tx = Yii::$app->ocpp->getTransaction(42);

echo $tx['charge_point_id'];   // 'CHARGER001'
echo $tx['tag_name'];          // 'TAG001'
echo $tx['status'];            // 'Ended'
echo $tx['meter_start'];       // Wh at session start
echo $tx['meter_stop'];        // Wh at session end
echo $tx['total_wh'];          // energy delivered in Wh
echo $tx['duration'];          // session duration in seconds
echo $tx['soc_start'];         // State of Charge at start (%)
echo $tx['soc_end'];           // State of Charge at end (%)
```

---

## Transaction status lifecycle

```
Pending → Started → Charging → Ended
                             ↘ Rejected
```

| Status | Description |
|--------|-------------|
| `Pending` | Created by `remoteStart`, waiting for charger response |
| `Started` | Charger accepted the start, `StartTransaction` received |
| `Charging` | Active session with MeterValues being received |
| `Ended` | `StopTransaction` received |
| `Rejected` | Charger rejected the `RemoteStartTransaction` |

---

## Transaction object reference

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | DB primary key = OCPP transaction ID |
| `transaction_id` | int | `csms_transaction_id` from `remoteStart` |
| `charge_point_id` | string | Charger identity |
| `tag_name` | string\|null | RFID tag used; `null` for charger-initiated |
| `user_id` | int | CSMS user who initiated; `0` for charger-initiated |
| `meter_start` | int | Meter reading at start (Wh) |
| `meter_stop` | int\|null | Meter reading at end (Wh) |
| `total_wh` | int\|null | Energy delivered (Wh); updated real-time from MeterValues |
| `duration` | int\|null | Session duration in seconds |
| `voltage_start` | float\|null | Voltage at session start (V) |
| `voltage_stop` | float\|null | Voltage at session end (V) |
| `current_start` | float\|null | Current at session start (A) |
| `current_stop` | float\|null | Current at session end (A) |
| `power_start` | float\|null | Power at session start (W) |
| `power_stop` | float\|null | Power at session end (W) |
| `soc_start` | int\|null | State of Charge at start (%) |
| `soc_end` | int\|null | State of Charge at end (%) |
| `battery_id` | string\|null | EV battery ID |
| `stop_reason` | string\|null | OCPP stop reason (e.g. `Remote`, `Local`) |
| `status` | string | Current transaction status |
