<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/** Whether new orders are queued and whether the cron works through the queue. */
final class ProcessingMode
{
    /** Normal operation: new orders get queued, the cron works through it. */
    public const RUNNING = 'running';

    /** No new orders queued; the queue keeps being worked through. */
    public const DRAINING = 'draining';

    /** Nothing is queued, nothing is processed - a full freeze. */
    public const STOPPED = 'stopped';
}
