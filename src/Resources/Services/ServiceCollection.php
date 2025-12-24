<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Services;

/**
 * Service Collection
 *
 * A collection of Service models.
 */
class ServiceCollection
{
    /** @var Service[] */
    public array $items;
    public array $meta;

    /**
     * @param Service[] $items
     * @param array $meta
     */
    public function __construct(array $items, array $meta = [])
    {
        $this->items = $items;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
            'meta' => $this->meta,
        ];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function first(): ?Service
    {
        return $this->items[0] ?? null;
    }
}
