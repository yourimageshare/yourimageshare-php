<?php

declare(strict_types=1);

namespace YourImageShare;

final class ListResult
{
    /** @var ListedUpload[] */
    public array $data;
    public ListMeta $meta;

    /** @param ListedUpload[] $data */
    public function __construct(array $data, ListMeta $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }
}
