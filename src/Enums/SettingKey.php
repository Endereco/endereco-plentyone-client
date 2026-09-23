<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Keys of the plugin's own settings.
 *
 * The value is the key from config.json without the plugin name; the settings
 * service prefixes it. Reading a stored setting always yields a string, also
 * for a check box - "true" and "false" as text.
 */
final class SettingKey
{
    public const API_ENDPOINT = 'api.endpoint';

    public const API_KEY = 'api.key';

    public const ENQUEUE_MODE = 'queue.enqueueMode';

    /**
     * One of the ProcessingMode values. For a real update/uninstall, not a
     * day-to-day setting.
     */
    public const PROCESSING_MODE = 'queue.processingMode';

    /** Whether a correction is written to the order without being asked. */
    public const APPLY_CORRECTION = 'validation.applyCorrection';

    /** Whether a result comment is written to the order at all. */
    public const WRITE_COMMENTS = 'validation.writeComments';

    /** Backend user id the plugin's own order comments are attributed to. */
    public const COMMENT_USER_ID = 'validation.commentUserId';

    /**
     * Order status while an order is enqueued for address validation. Empty,
     * unparseable or nonexistent all fall back to OrderStatus::HOLD.
     */
    public const START_STATUS = 'status.startStatus';

    /**
     * One target-status setting per TargetStatusCase, except SUGGESTION_AVAILABLE
     * and MULTIPLE_VARIANTS, which share this one. "0" - also the fallback for
     * an unset value - means: restore the status the order had before it was
     * queued.
     */
    public const TARGET_STATUS_CORRECT = 'status.targetCorrect';

    public const TARGET_STATUS_MINOR_CORRECTION = 'status.targetMinorCorrection';

    public const TARGET_STATUS_SUGGESTION_AVAILABLE = 'status.targetSuggestionAvailable';

    public const TARGET_STATUS_NOT_FOUND = 'status.targetNotFound';

    public const TARGET_STATUS_GAVE_UP = 'status.targetGaveUp';
}
