<?php

declare(strict_types=1);

namespace YourImageShare;

/** Same fields as the JS/Python SDKs' UploadResult/UploadResult dataclass. */
final class UploadResult
{
    public string $id;
    public string $type;
    public string $path;
    public string $src;
    public string $direct;
    /** @var string|null */
    public $expiresAt;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->id = (string) $data['id'];
        $this->type = (string) $data['type'];
        $this->path = (string) $data['path'];
        $this->src = (string) $data['src'];
        $this->direct = (string) $data['direct'];
        $this->expiresAt = $data['expires_at'] ?? null;
    }
}
