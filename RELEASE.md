# Release Notes

## 1.0.2 — 2026-06-23

### Fixes

- `remoteStop()` — rename parameter `$transactionId` → `$ocppTransactionId` dan body key menjadi `ocpp_transaction_id` (sebelumnya `transaction_id`)

---

## 1.0.0 — 2026-06-23

Initial release.

### Features

- `OcppClient` — Yii2 HTTP client extending `budimanlai/yii2-pkg` `Api3rdBase`
- **Server status** — `getServerStatus()`
- **Charger management** — `getChargers()`, `createCharger()`, `getCharger()`, `updateCharger()`, `deleteCharger()`
- **Connector management** — `getConnectors()`, `addConnector()`
- **OCPP commands**
  - `disconnectCharger()`
  - `remoteStart()` / `remoteStop()`
  - `reset()`
  - `unlockConnector()`
  - `clearCache()`
  - `changeAvailability()`
  - `changeConfiguration()` / `getConfiguration()`
  - `triggerMessage()`
  - `reserveNow()` / `cancelReservation()`
  - `getLocalListVersion()`
- **Transactions** — `getTransactions()`, `getTransaction()`
- **Tags** — `getTags()`, `createTag()`, `getTag()`, `updateTag()`, `deleteTag()`
- All API calls logged to `api_3rd_log` via `budimanlai/yii2-pkg`
