<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Colours for the tag chips in the backend.
 *
 * Green for a confirmed address, blue where the plugin changed data by
 * itself, amber where something is open, red for a failed check, grey for
 * a pending one.
 */
final class TagColor
{
    public const POSITIVE = '#2F9E44';

    public const INFO = '#1971C2';

    public const WARNING = '#F08C00';

    public const ERROR = '#C92A2A';

    public const NEUTRAL = '#868E96';

    /** Colour per tag; anything not listed here gets NEUTRAL. */
    public const BY_TAG = [
        OrderTag::ADDRESS_VALID => self::POSITIVE,
        OrderTag::ADDRESS_CORRECTED => self::INFO,
        OrderTag::ADDRESS_ISSUE => self::WARNING,
        OrderTag::ADDRESS_VALIDATION_FAILED => self::ERROR,
    ];
}
