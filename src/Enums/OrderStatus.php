<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * The order statuses the plugin sets.
 *
 * PlentyONE status ids are decimals; the value is kept as a string so it can
 * also be logged verbatim, and cast where the repository wants a float.
 */
final class OrderStatus
{
    /** "3.2 | In waiting position" */
    public const HOLD = '3.2';
}
