<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

/**
 * Reads a single one of the plugin's own properties off an order.
 *
 * findById() has no way to ask for a single property, so every property on
 * the order is fetched and filtered here.
 *
 * A property that is not set, or whose type does not exist yet, comes back
 * as null - callers decide what that means.
 */
final class OrderPropertyReader
{
    public function __construct(
        private OrderRepositoryContract $orders,
        private OrderPropertyTypeRegistry $registry,
    ) {
    }

    public function value(int $orderId, string $name): ?string
    {
        $typeId = $this->registry->typeIds()[$name] ?? null;

        if ($typeId === null) {
            return null;
        }

        $order = $this->orders->findById($orderId, ['properties']);

        foreach ($order->properties as $property) {
            if ($property->typeId === $typeId) {
                return $property->value;
            }
        }

        return null;
    }
}
