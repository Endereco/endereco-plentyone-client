<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Order types as used by Order::$typeId.
 *
 * OrderCreated fires for every type, so anything that must only touch sales
 * orders has to compare against SALES_ORDER.
 */
final class OrderType
{
    public const SALES_ORDER = 1;
}
