<?php

declare(strict_types=1);

namespace YourImageShare;

/**
 * Client for the YourImageShare upload API. Mirrors the official JS
 * (npm: yourimageshare) and Python (PyPI: yourimageshare) SDKs - same
 * method names, same result shapes, same error type - so switching
 * languages doesn't mean relearning the client.
 *
 * Example:
 *   $client = new YourImageShare('YOUR_API_KEY');
 *   $result = $client->upload('photo.jpg');
 *   echo $result->direct;
 */
final class YourImageShare
{
    public const DEFAULT_BASE_URL = 'https://yourimageshare.com/api';
    private const SDK_VERSION = '1.1.0';
    /** Files above this size are sent in pieces (one request can carry at most 100 MB). */
    private const CHUNK_THRESHOLD = 90 * 1024 * 1024;
    /** Size of one piece of a chunked upload (the API accepts at most 5 MB). */
    private const CHUNK_SIZE = 5 * 1024 * 1024;

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $apiKey, string $baseUrl = self::DEFAULT_BASE_URL, int $timeout = 30)
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('YourImageShare: $apiKey is required.');
        }
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('YourImageShare: the curl extension is required.');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Upload a file (up to 200 MB). `$file` is a path to a local file, or an
     * already-open resource (from fopen()) - pass `filename` in $options for
     * the latter so the server sees a real extension. Files over 90 MB are
     * sent in 5 MB pieces automatically.
     *
     * Options: `expires_in` (seconds, 60 to 2,592,000), `allow_duplicate`
     * (store a new copy even if your account already uploaded this exact
     * file - otherwise that upload is returned with `duplicate = true`),
     * `on_progress` (callable(int $sent, int $total), called after each piece).
     *
     * @param resource|string $file
     * @param array{filename?: string, expires_in?: int, allow_duplicate?: bool, on_progress?: callable} $options
     */
    public function upload($file, array $options = []): UploadResult
    {
        $isResource = is_resource($file);
        if (!$isResource && !is_string($file)) {
            throw new \InvalidArgumentException('YourImageShare: $file must be a path string or an open resource.');
        }

        $tmpFile = null;
        try {
            if ($isResource) {
                // CURLFile needs a real path - stream the resource out to a
                // temp file rather than requiring callers to only ever pass
                // paths (matches the JS/Python SDKs both accepting an
                // already-open handle/blob, not just a path).
                $tmpFile = tempnam(sys_get_temp_dir(), 'yis_');
                $out = fopen($tmpFile, 'wb');
                stream_copy_to_stream($file, $out);
                fclose($out);
                $sourcePath = $tmpFile;
                $filename = $options['filename'] ?? basename($tmpFile);
            } else {
                if (!is_file($file)) {
                    throw new \InvalidArgumentException("YourImageShare: file not found: {$file}");
                }
                $sourcePath = $file;
                $filename = $options['filename'] ?? basename($file);
            }

            if ((int) filesize($sourcePath) > self::CHUNK_THRESHOLD) {
                $uploadId = $this->sendChunks($sourcePath, $options['on_progress'] ?? null);
                return $this->postUpload(['upload_id' => $uploadId, 'filename' => $filename], $options);
            }

            $mimeType = function_exists('mime_content_type') ? (mime_content_type($sourcePath) ?: null) : null;
            return $this->postUpload([
                'uploads' => new \CURLFile($sourcePath, $mimeType ?: 'application/octet-stream', $filename),
            ], $options);
        } finally {
            if ($tmpFile !== null) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Upload from a public http(s) link: the server downloads the file itself (up to 200 MB).
     *
     * @param array{expires_in?: int, allow_duplicate?: bool} $options
     */
    public function uploadUrl(string $url, array $options = []): UploadResult
    {
        return $this->postUpload(['url' => $url], $options);
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $options
     */
    private function postUpload(array $fields, array $options): UploadResult
    {
        if (isset($options['expires_in'])) {
            $fields['expires_in'] = (string) $options['expires_in'];
        }
        if (!empty($options['allow_duplicate'])) {
            $fields['allow_duplicate'] = '1';
        }
        $body = $this->request('POST', $this->baseUrl, $fields, max($this->timeout, 180));
        return new UploadResult($body['data']);
    }

    /** Sends the file in CHUNK_SIZE pieces to POST /chunk; returns the upload id to finish with. */
    private function sendChunks(string $path, ?callable $onProgress): string
    {
        $uploadId = bin2hex(random_bytes(16));
        $size = (int) filesize($path);
        $total = (int) ceil($size / self::CHUNK_SIZE);
        $in = fopen($path, 'rb');
        $piecePath = tempnam(sys_get_temp_dir(), 'yis_piece_');
        $sent = 0;
        try {
            for ($index = 0; $index < $total; $index++) {
                $piece = (string) fread($in, self::CHUNK_SIZE);
                // CURLFile needs a path (CURLStringFile is PHP 8.1+)
                file_put_contents($piecePath, $piece);
                for ($attempt = 1; ; $attempt++) {
                    try {
                        $this->request('POST', $this->baseUrl . '/chunk', [
                            'upload_id' => $uploadId,
                            'index' => (string) $index,
                            'total' => (string) $total,
                            'chunk' => new \CURLFile($piecePath, 'application/octet-stream', 'piece'),
                        ]);
                        break;
                    } catch (YourImageShareError $e) {
                        // network errors (status 0) and 5xx are retried; anything the server refused (4xx) is final
                        $status = $e->getStatus();
                        if ($attempt >= 3 || ($status >= 400 && $status < 500)) {
                            throw $e;
                        }
                    }
                }
                $sent += strlen($piece);
                if ($onProgress !== null) {
                    $onProgress($sent, $size);
                }
            }
        } finally {
            fclose($in);
            @unlink($piecePath);
        }
        return $uploadId;
    }

    /** List your uploads, newest first. Paginated 50 per page. */
    public function list(int $page = 1): ListResult
    {
        $url = $this->baseUrl;
        if ($page > 1) {
            $url .= '?' . http_build_query(['page' => $page]);
        }

        $body = $this->request('GET', $url);
        $uploads = array_map(static fn (array $item) => new ListedUpload($item), $body['data']);
        return new ListResult($uploads, new ListMeta($body['meta']));
    }

    /** Delete one of your uploads by id. Throws on a 404/401. */
    public function delete(string $id): void
    {
        $this->request('DELETE', $this->baseUrl . '/' . rawurlencode($id));
    }

    /**
     * @param array<string, mixed>|null $postFields
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, ?array $postFields = null, ?int $timeout = null): array
    {
        $ch = curl_init();
        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'User-Agent: yourimageshare-php/' . self::SDK_VERSION,
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout ?? $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postFields;
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new YourImageShareError("Request failed: {$error}", 0);
        }

        $body = json_decode((string) $raw, true);
        if (!is_array($body)) {
            throw new YourImageShareError("Unexpected non-JSON response (HTTP {$status})", $status);
        }

        if ($status < 200 || $status >= 300 || ($body['type'] ?? null) === 'error') {
            $message = is_string($body['errors'] ?? null) ? $body['errors'] : "Request failed (HTTP {$status})";
            throw new YourImageShareError($message, $status);
        }

        return $body;
    }
}
