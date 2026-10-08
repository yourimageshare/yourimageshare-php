<?php

declare(strict_types=1);

namespace YourImageShare;

/**
 * Same fields as the other SDKs' UploadResult. `path` is the storage URL as uploaded and can change
 * shortly afterwards when the file is converted (WebP/MP4) - store `src`, the permanent link.
 */
final class UploadResult
{
    public string $id;
    public string $type;
    public string $path;
    public string $src;
    public string $direct;
    /** @var string|null 280 px wide WebP thumbnail (a video's first frame) */
    public $thumb;
    /** @var int|null */
    public $width;
    /** @var int|null */
    public $height;
    /** @var int|null File size in bytes as stored */
    public $size;
    /** True if the upload is password-protected */
    public bool $locked;
    /** @var string|null */
    public $expiresAt;
    /** True if your account had already uploaded this exact file and that upload was returned */
    public bool $duplicate;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->id = (string) $data['id'];
        $this->type = (string) $data['type'];
        $this->path = (string) $data['path'];
        $this->src = (string) $data['src'];
        $this->direct = (string) $data['direct'];
        $this->thumb = $data['thumb'] ?? null;
        $this->width = isset($data['width']) ? (int) $data['width'] : null;
        $this->height = isset($data['height']) ? (int) $data['height'] : null;
        $this->size = isset($data['size']) ? (int) $data['size'] : null;
        $this->locked = ($data['locked'] ?? false) === true;
        $this->expiresAt = $data['expires_at'] ?? null;
        $this->duplicate = ($data['duplicate'] ?? false) === true;
    }
}
