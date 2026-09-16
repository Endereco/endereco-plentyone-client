<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * What the endereco API said about the address - makes no statement about how
 * the order is handled afterwards.
 */
final class CheckFinding
{
    /** Correct as it stands. */
    public const CORRECT = 'correct';

    /** One unambiguous, minor repair is available. */
    public const CORRECTABLE = 'correctable';

    /** One repair is available, but not a minor one - not applied automatically. */
    public const MAJOR_CORRECTION = 'major_correction';

    /** Several candidates - cannot be applied automatically. */
    public const MULTIPLE_VARIANTS = 'multiple_variants';

    /** No candidate at all. */
    public const NOT_FOUND = 'not_found';
}
