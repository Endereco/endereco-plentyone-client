<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * When an order enters the address validation queue.
 *
 * AUTOMATIC is the default: every new sales order is queued right away. MANUAL hands
 * the decision to the merchant, who will wire a flow action of this plugin
 * into a flow of their own.
 */
final class EnqueueMode
{
    public const AUTOMATIC = 'automatic';

    public const MANUAL = 'manual';
}
