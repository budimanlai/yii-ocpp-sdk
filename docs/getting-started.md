# Getting Started

## Installation

```bash
composer require budimanlai/yii2-ocpp-sdk
```

## Requirements

- PHP >= 7.4
- Yii2 >= 2.0
- `budimanlai/yii2-pkg` >= 1.1.2 — provides `Api3rdBase` and the `api_3rd_log` table

## Database

All API calls are automatically logged to the `api_3rd_log` table provided by `budimanlai/yii2-pkg`.  
Ensure the migration from that package has been run before using this SDK.

## Configuration

### As a Yii2 application component

Add to your application config (`config/main.php` or `config/web.php`):

```php
'components' => [
    'ocpp' => [
        'class' => budimanlai\ocpp\OcppClient::class,
        'baseUrl' => 'http://localhost:8100',
    ],
],
```

Then access it anywhere via:

```php
Yii::$app->ocpp->getServerStatus();
```

### Direct instantiation

```php
$client = new \budimanlai\ocpp\OcppClient();
$client->baseUrl = 'http://localhost:8100';
```

### With explicit user ID

```php
$client = new \budimanlai\ocpp\OcppClient(userId: 42);
$client->baseUrl = 'http://localhost:8100';
```

If `userId` is omitted, the SDK uses the currently authenticated Yii user (`Yii::$app->user->id`).

## Response format

All list methods return the full paginated envelope so you can access pagination metadata:

```php
$result = Yii::$app->ocpp->getChargers(['page' => 1, 'limit' => 20]);

$items    = $result['data'];
$total    = $result['meta']['total'];
$lastPage = $result['meta']['total_page'];
```

All single-item and command methods return only the `data` payload directly.
