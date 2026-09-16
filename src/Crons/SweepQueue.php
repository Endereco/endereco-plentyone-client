<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Crons;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Services\PluginLogger;
use EnderecoPlentyOneClient\Services\QueueSweepService;
use Plenty\Modules\Cron\Contracts\CronHandler;

/**
 * Not configurable - there is nothing for a merchant to decide here (see
 * ServiceProvider for the interval). See QueueSweepService for what it cleans up.
 */
final class SweepQueue extends CronHandler
{
    public function __construct(
        private QueueSweepService $sweepService,
        private PluginLogger $logger,
    ) {
    }

    /** handle() is the method PlentyONE calls on a cron handler. */
    public function handle(): void
    {
        try {
            $this->sweepService->sweep();
        } catch (\Throwable $exception) {
            $this->logger->exception(LogCode::QUEUE_ORPHAN_SWEEP_FAILED, $exception);
        }
    }
}
