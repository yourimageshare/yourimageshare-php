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
    /** "private" (file only), "unlisted" (page for anyone with the link) or "public" (listed) */
    public string $visibility;
    /** @var string|null */
    public $description;
    /** @var string|null */
    public $title;
    /** @var string|null New uploads only: a private link that deletes the upload without an API key (shown once) */
    public $deleteUrl;
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
        $this->visibility = (string) ($data['visibility'] ?? 'unlisted');
        $this->description = $data['description'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->deleteUrl = $data['delete_url'] ?? null;
        $this->expiresAt = $data['expires_at'] ?? null;
        $this->duplicate = ($data['duplicate'] ?? false) === true;
    }
}
