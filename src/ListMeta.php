<?php

declare(strict_types=1);

namespace YourImageShare;

final class ListMeta
{
    public int $currentPage;
    public int $lastPage;
    public int $total;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->currentPage = (int) $data['current_page'];
        $this->lastPage = (int) $data['last_page'];
        $this->total = (int) $data['total'];
    }
}
