<?php

declare(strict_types=1);

namespace YourImageShare;

/** Same fields as the JS/Python SDKs' ListedUpload. */
final class ListedUpload
{
    public string $id;
    public string $type;
    /** @var string|null */
    public $title;
    public string $path;
    public string $src;
    public string $direct;
    /** @var string|null */
    public $expiresAt;
    public string $createdAt;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->id = (string) $data['id'];
        $this->type = (string) $data['type'];
        $this->title = $data['title'] ?? null;
        $this->path = (string) $data['path'];
        $this->src = (string) $data['src'];
        $this->direct = (string) $data['direct'];
        $this->expiresAt = $data['expires_at'] ?? null;
        $this->createdAt = (string) $data['created_at'];
    }
}
