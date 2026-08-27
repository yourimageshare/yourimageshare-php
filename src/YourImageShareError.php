<?php

declare(strict_types=1);

namespace YourImageShare;

/**
 * Thrown for any non-2xx response or a `{"type":"error"}` payload - mirrors
 * the JS/Python SDKs' YourImageShareError exactly (same $status/message
 * shape) so error-handling code reads the same across all three.
 */
class YourImageShareError extends \RuntimeException
{
    private int $status;

    public function __construct(string $message, int $status)
    {
        parent::__construct(sprintf('[%d] %s', $status, $message));
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /** The server's own error text, without the "[status] " prefix getMessage() adds. */
    public function getApiMessage(): string
    {
        return preg_replace('/^\[\d+\]\s/', '', $this->getMessage()) ?? $this->getMessage();
    }
}
