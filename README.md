# yii2-ocpp-sdk

Yii2 extension — HTTP client SDK for the [go-ocpp-api](https://github.com/carisoftware/go-ocpp-api) server.  
Supports OCPP 1.6 and 2.0.1 over REST.

## Requirements

- PHP >= 7.4
- Yii2 >= 2.0
- [budimanlai/yii2-pkg](https://packagist.org/packages/budimanlai/yii2-pkg) >= 1.1.2

## Installation

```bash
composer require carisoftware/yii2-ocpp-sdk
```

## Database

This SDK logs every API call to the `api_3rd_log` table (provided by `budimanlai/yii2-pkg`).  
Ensure the table exists before use.

## Documentation

- [Getting Started](docs/getting-started.md) — installation, configuration, response format
- [Chargers](docs/chargers.md) — manage charging stations
- [Connectors](docs/connectors.md) — manage connectors
- [Commands](docs/commands.md) — OCPP commands (remote start/stop, reset, unlock, etc.)
- [Transactions](docs/transactions.md) — read charging session records
- [Tags](docs/tags.md) — manage RFID tags and id_tokens

## License

MIT — © Budiman Lai
