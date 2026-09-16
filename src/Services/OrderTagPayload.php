<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

/**
 * Builds the tag list for updateOrder() - that list REPLACES the order's
 * tags, so existing ones are read and sent along too. Newly added tags go
 * first, so the most relevant one shows up front in the backend's tag list.
 */
final class OrderTagPayload
{
    public function __construct(
        private OrderRepositoryContract $orders,
    ) {
    }

    /**
     * @param list<int> $addIds
     * @param list<int> $removeIds
     *
     * @return list<array<string, int>>
     */
    public function build(int $orderId, array $addIds, array $removeIds): array
    {
        $order = $this->orders->findById($orderId, ['tags']);
        $tagIds = [];

        foreach ($addIds as $tagId) {
            $tagIds[$tagId] = $tagId;
        }

        foreach ($order->tags as $tag) {
            if (! in_array($tag->id, $removeIds, true)) {
                $tagIds[$tag->id] = $tag->id;
            }
        }

        $payload = [];

        foreach ($tagIds as $tagId) {
            $payload[] = ['tagId' => $tagId];
        }

        return $payload;
    }
}
