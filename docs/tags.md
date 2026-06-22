# Tags

Manage RFID tags and id_tokens used for charger authorization.

---

## getTags

Returns a paginated list of tags.

```php
// All tags
$result = Yii::$app->ocpp->getTags();

// Filter by status
$result = Yii::$app->ocpp->getTags([
    'page'   => 1,
    'limit'  => 20,
    'status' => 'Active',
]);

$tags  = $result['data'];
$total = $result['meta']['total'];
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | int | Page number, 1-based (default `1`) |
| `limit` | int | Items per page (default `20`) |
| `status` | string | Filter by status: `Active`, `Blocked`, `Expired`, `Invalid` |

---

## getTag

Returns a single tag by its `id_token`.

```php
$tag = Yii::$app->ocpp->getTag('TAG001');

echo $tag['id_token'];    // 'TAG001'
echo $tag['tag_type'];    // 'RFID'
echo $tag['status'];      // 'Active'
echo $tag['last_used_at'];
```

---

## createTag

Creates a new tag. Throws an exception if `id_token` already exists.

```php
$tag = Yii::$app->ocpp->createTag([
    'id_token'             => 'TAG001',
    'tag_type'             => 'RFID',
    'status'               => 'Active',
    'additional_id_token'  => null,
]);
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id_token` | string | yes | Unique tag identity |
| `tag_type` | string | no | e.g. `RFID`, `ISO14443` |
| `status` | string | no | Default `Active`. Use `Blocked` to deny authorization |
| `additional_id_token` | string\|null | no | Secondary token for multi-factor auth |

---

## updateTag

Updates fields on an existing tag. Only supplied fields are changed.

```php
// Block a tag
Yii::$app->ocpp->updateTag('TAG001', ['status' => 'Blocked']);

// Re-activate
Yii::$app->ocpp->updateTag('TAG001', ['status' => 'Active']);
```

Valid `status` values: `Active`, `Blocked`, `Expired`, `Invalid`.

---

## deleteTag

Permanently deletes a tag.

```php
Yii::$app->ocpp->deleteTag('TAG001');
```
