<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Every log code the plugin writes.
 *
 * The value is the translation key inside resources/lang/<lang>/log.properties
 * A code without a translation is silently dropped by PlentyONE below error level,
 * so this class and the properties files have to stay in sync.
 */

final class LogCode
{
    // --- queueing an order ----------------------------------------------------

    public const ORDER_SKIPPED_NOT_SALES_ORDER = 'log.orderSkippedNotSalesOrder';

    public const ORDER_SKIPPED_MANUAL_MODE = 'log.orderSkippedManualMode';

    public const ORDER_SKIPPED_NO_ADDRESS = 'log.orderSkippedNoAddress';

    public const ORDER_QUEUED = 'log.orderQueued';

    /** Queued again - the plugin had already finished this order once. */
    public const ORDER_REENQUEUED = 'log.orderReenqueued';

    public const ORDER_ALREADY_QUEUED = 'log.orderAlreadyQueued';

    /** enqueueFromListener() refused - the order was already handled once, not just queued. */
    public const ORDER_ALREADY_HANDLED = 'log.orderAlreadyHandled';

    public const ORDER_QUEUE_FAILED = 'log.orderQueueFailed';

    /** writeStart() undone - enqueueing failed after the status was already changed. */
    public const START_STATUS_REVERTED = 'log.startStatusReverted';

    public const ORDER_SKIPPED_NOT_ACCEPTING = 'log.orderSkippedNotAccepting';

    /** Configured start status does not exist, falling back to OrderStatus::HOLD. */
    public const START_STATUS_MISSING = 'log.startStatusMissing';

    // --- checking an address -----------------------------------------------------

    /** Order has no delivery address. */
    public const ADDRESS_MISSING = 'log.addressMissing';

    /** The service could not be asked at all - transport/HTTP/RPC error. */
    public const API_CALL_FAILED = 'log.apiCallFailed';

    public const ORDER_CHECKED = 'log.orderChecked';

    /** Circuit breaker: max attempts reached, endereco stayed unreachable. */
    public const ORDER_GAVE_UP = 'log.orderGaveUp';

    // --- writing the result ------------------------------------------------------

    public const CORRECTION_APPLIED = 'log.correctionApplied';

    /** Correction available but "apply automatically" is switched off. */
    public const CORRECTION_KEPT_BACK = 'log.correctionKeptBack';

    public const CORRECTION_FAILED = 'log.correctionFailed';

    public const STATUS_RESTORED = 'log.statusRestored';

    public const TARGET_STATUS_SET = 'log.targetStatusSet';

    public const STATUS_KEPT = 'log.statusKept';

    public const STATUS_WRITE_FAILED = 'log.statusWriteFailed';

    public const TARGET_STATUS_MISSING = 'log.targetStatusMissing';

    public const TAGS_UPDATED = 'log.tagsUpdated';

    public const TAG_MISSING = 'log.tagMissing';

    public const TAG_CREATE_FAILED = 'log.tagCreateFailed';

    public const COMMENT_WRITTEN = 'log.commentWritten';

    public const COMMENT_FAILED = 'log.commentFailed';

    public const COMMENT_USER_MISSING = 'log.commentUserMissing';

    // --- order properties ------------------------------------------------------

    public const PROPERTY_WRITE_FAILED = 'log.propertyWriteFailed';

    public const PROPERTY_TYPE_MISSING = 'log.propertyTypeMissing';

    // --- master data, created at deploy time ------------------------------------

    public const TABLE_CREATED = 'log.tableCreated';

    public const TABLE_CREATE_FAILED = 'log.tableCreateFailed';

    public const TABLE_UPDATED = 'log.tableUpdated';

    public const TABLE_UPDATE_FAILED = 'log.tableUpdateFailed';

    public const MASTER_DATA_CREATED = 'log.masterDataCreated';

    public const MASTER_DATA_FAILED = 'log.masterDataFailed';

    // --- working off the queue -------------------------------------------------

    public const QUEUE_STOPPED = 'log.queueStopped';

    public const QUEUE_EMPTY = 'log.queueEmpty';

    public const QUEUE_RUN_FINISHED = 'log.queueRunFinished';

    public const QUEUE_RUN_FAILED = 'log.queueRunFailed';

    public const QUEUE_ENTRY_STALE = 'log.queueEntryStale';

    public const QUEUE_ENTRY_REMOVE_FAILED = 'log.queueEntryRemoveFailed';

    public const QUEUE_ENTRY_ADD_FAILED = 'log.queueEntryAddFailed';

    public const QUEUE_ENTRY_MOVE_FAILED = 'log.queueEntryMoveFailed';

    // --- sweeping for orphaned queue rows ---------------------------------------

    /** Row removed for sitting in the queue too long, regardless of state. */
    public const QUEUE_ENTRY_TOO_OLD = 'log.queueEntryTooOld';

    /** A CHECKED row whose order was already FINISHED (or gone) - see CheckResultWriter. */
    public const QUEUE_ORPHAN_REMOVED = 'log.queueOrphanRemoved';

    public const QUEUE_ORPHAN_SWEEP_FINISHED = 'log.queueOrphanSweepFinished';

    public const QUEUE_ORPHAN_SWEEP_FAILED = 'log.queueOrphanSweepFailed';
}
