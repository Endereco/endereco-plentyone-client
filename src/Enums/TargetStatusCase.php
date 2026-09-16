<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * The six cases a merchant can configure a target order status for.
 */
final class TargetStatusCase
{
    public const CORRECT = 'correct';

    public const MINOR_CORRECTION = 'minor_correction';

    public const SUGGESTION_AVAILABLE = 'suggestion_available';

    public const MULTIPLE_VARIANTS = 'multiple_variants';

    public const NOT_FOUND = 'not_found';

    public const GAVE_UP = 'gave_up';
}
