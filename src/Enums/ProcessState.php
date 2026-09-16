<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Where an order stands in the address validation process.
 */
final class ProcessState
{
    public const ENQUEUED = 'enqueued';

    /** Endereco has answered; waiting to be closed. */
    public const CHECKED = 'checked';

    /** The plugin's own processing, not the order's PlentyONE status. */
    public const FINISHED = 'finished';
}
