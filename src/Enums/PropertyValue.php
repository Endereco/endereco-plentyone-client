<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Values the plugin writes into its order properties.
 *
 * An order property is always a string, also for a counter - reading one back
 * never yields an int.
 */
final class PropertyValue
{
    public const ZERO = '0';

    public const TRUE = 'true';

    public const FALSE = 'false';

    /** Not an empty string: that would not overwrite an existing value. */
    public const NONE = 'none';

    /** The "checked" fields when there are several suggestions to choose from. */
    public const MULTIPLE = 'multiple';
}
