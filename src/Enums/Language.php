<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/** The languages the plugin ships translations for. */
final class Language
{
    public const GERMAN = 'de';

    public const ENGLISH = 'en';

    public const ALL = [self::GERMAN, self::ENGLISH];
}
