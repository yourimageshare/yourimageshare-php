# yourimageshare-php

[![Packagist version](https://img.shields.io/packagist/v/yourimageshare/yourimageshare-php.svg)](https://packagist.org/packages/yourimageshare/yourimageshare-php)
[![license](https://img.shields.io/packagist/l/yourimageshare/yourimageshare-php.svg)](LICENSE)

Official PHP SDK for the [YourImageShare](https://yourimageshare.com)
upload API. Zero Composer dependencies (uses PHP's own `curl` extension),
PHP 7.4+.

- **Get an API key:** sign in and open the **API** tab at
  [yourimageshare.com/my-account](https://yourimageshare.com/my-account).
- **Full HTTP reference:** [yourimageshare.com/about/api](https://yourimageshare.com/about/api)
  or [API.md in the yourimageshare-api repo](https://github.com/yourimageshare/yourimageshare/blob/main/API.md).
- Same API, same result shapes, in [JavaScript/TypeScript](https://www.npmjs.com/package/yourimageshare) and [Python](https://pypi.org/project/yourimageshare/) too.

## Install

```bash
composer require yourimageshare/yourimageshare-php
```

## Usage

```php
use YourImageShare\YourImageShare;

$client = new YourImageShare('YOUR_API_KEY');

// Upload a file by path
$result = $client->upload('photo.jpg');
echo $result->direct; // https://yourimageshare.com/ib/aB3xY9qRz1

// Upload with auto-delete after 1 hour
$client->upload('photo.jpg', ['expires_in' => 3600]);

// Upload from an already-open resource
$handle = fopen('photo.jpg', 'rb');
$client->upload($handle, ['filename' => 'photo.jpg']);

// List your uploads (paginated, 50 per page)
$listing = $client->list();
foreach ($listing->data as $item) {
    echo $item->id . ' ' . $item->direct . PHP_EOL;
}

// Delete an upload
$client->delete($result->id);
```

### Large files, duplicates, links and thumbnails

Files up to 200 MB are supported. Anything over 90 MB is sent in 5 MB pieces automatically (one request can carry at most 100 MB). If your account already uploaded the exact same file, the existing upload is returned with `duplicate` set - opt out with the allow-duplicate option. Results also carry `thumb` (a 280 px WebP thumbnail), `width`, `height`, `size` and `locked`. Store `src`, not `path`: `path` can change shortly after upload when the file is converted (WebP/MP4).

```php
$result = $client->upload('video.mp4', [
    'on_progress' => function (int $sent, int $total) { echo intdiv($sent * 100, $total), "%\n"; },
]);
echo $result->src, ' ', $result->thumb, ' ', $result->width, ' ', $result->duplicate ? 'dup' : 'new';

// a fresh copy even if this exact file is already on your account
$client->upload('photo.jpg', ['allow_duplicate' => true]);

// let the server download a public link (up to 200 MB)
$client->uploadUrl('https://example.com/photo.jpg');
```

### Visibility, titles and changing an upload

Each upload has a `visibility`: `unlisted` (the default - file and page work for anyone with the link, not listed anywhere), `private` (file only - the page link sends everyone but you to the file) or `public` (listed on the site). Uploads can carry a `title` (90 characters) and `description` (500). New uploads return a one-time `delete_url` that deletes the upload without an API key. `get`/`update` need the full API key.

```php
$result = $client->upload('photo.jpg', ['visibility' => 'private', 'title' => 'Sunset']);
echo $result->deleteUrl; // shown once - keep it if you need it

$one = $client->get($result->id);
$client->update($result->id, ['visibility' => 'public', 'description' => 'Lake at dusk']);
```

### Error handling

Failed requests throw `YourImageShare\YourImageShareError`
(`getStatus()` is the HTTP status code, `getApiMessage()` is the server's
error text):

```php
use YourImageShare\YourImageShare;
use YourImageShare\YourImageShareError;

$client = new YourImageShare('YOUR_API_KEY');

try {
    $client->upload('photo.jpg');
} catch (YourImageShareError $e) {
    echo $e->getStatus() . ': ' . $e->getApiMessage();
}
```

## API

### `new YourImageShare(string $apiKey, string $baseUrl = YourImageShare::DEFAULT_BASE_URL, int $timeout = 30)`

`$baseUrl` defaults to `https://yourimageshare.com/api` - override only for
testing against a different environment.

### `$client->upload($file, array $options = [])`

`$file` is a path (`string`) or an open resource (from `fopen()`).
`$options['expires_in']` is seconds, 60 to 2,592,000 (30 days) - omit for a
permanent upload. `$options['filename']` is required when `$file` is a
resource without an obvious name. Returns an `UploadResult` with `id`,
`type`, `path`, `src`, `direct`, `expiresAt`.

### `$client->list(int $page = 1)`

Returns a `ListResult` with `data` (an array of `ListedUpload` - `id`,
`type`, `title`, `path`, `src`, `direct`, `expiresAt`, `createdAt`) and
`meta` (`ListMeta` - `currentPage`, `lastPage`, `total`).

### `$client->delete(string $id)`

Throws `YourImageShareError` on a 404/401; returns `void` on success.

## Rate limits

20 requests/minute and 500/day per key by default (2,000/day per IP as a
backstop). Not currently surfaced on the return values from this SDK - read
the `X-RateLimit-Limit`/`X-RateLimit-Remaining` response headers yourself if
you need them, or open an issue to request them on the result objects.

## License

MIT

## Support

[yourimageshare.com/contact](https://yourimageshare.com/contact)
