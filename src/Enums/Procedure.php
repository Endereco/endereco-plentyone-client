<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * The flow actions ("procedures") the plugin offers in Flow Studio.
 *
 * Most values are translation keys - the merchant-facing name/help text
 * shown in the action palette, resolved through PluginTranslator. The
 * module name groups the entries in that palette. ENQUEUE_ORDER_ID is the
 * odd one out: not a translation key, a fixed identifier the modern
 * plentyFlow API registers the action under.
 */
final class Procedure
{
    /** Groups our actions in the palette under "Plugin (Legacy)". */
    public const MODULE = 'EnderecoPlentyOneClient';

    public const ENQUEUE_ORDER = 'procedure.enqueueOrder';

    /** Identifier of the same action in the modern plentyFlow API. */
    public const ENQUEUE_ORDER_ID = 'EnderecoPlentyOneClientEnqueueOrder';

    public const ENQUEUE_ORDER_HELP = 'procedure.enqueueOrderHelp';

    /** Material icon shown next to the action in the palette. */
    public const ICON = 'location_on';
}
