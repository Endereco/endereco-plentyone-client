<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Crons;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\ProcessState;
use EnderecoPlentyOneClient\Services\OrderCheckService;
use EnderecoPlentyOneClient\Services\PluginLogger;
use EnderecoPlentyOneClient\Services\PluginSettings;
use EnderecoPlentyOneClient\Services\QueueBatch;
use EnderecoPlentyOneClient\Services\QueueIndexReader;
use Plenty\Modules\Cron\Contracts\CronHandler;

/**
 * Works off the address validation queue on PlentyONE's 5-minute cron
 * raster, the platform's shortest interval.
 *
 * Each order runs in its own try/catch, so one bad order does not cost the
 * batch; run() itself is wrapped too, so a batch-query failure ends up in
 * the log instead of a silent cron death.
 *
 * Respects PluginSettings::processesQueue() - see there for what STOPPED
 * and DRAINING mean for this cron.
 */
final class ProcessAddressQueue extends CronHandler
{
    /** Maximum orders per run. */
    private const BATCH_SIZE = 50;

    public function __construct(
        private QueueBatch $batch,
        private QueueIndexReader $indexReader,
        private OrderCheckService $checkService,
        private PluginSettings $settings,
        private PluginLogger $logger,
    ) {
    }

    /** handle() is the method PlentyONE calls on a cron handler. */
    public function handle(): void
    {
        try {
            $this->run();
        } catch (\Throwable $exception) {
            $this->logger->exception(LogCode::QUEUE_RUN_FAILED, $exception);
        }
    }

    private function run(): void
    {
        if (! $this->settings->processesQueue()) {
            $this->logger->info(LogCode::QUEUE_STOPPED);

            return;
        }

        $orderIds = $this->batch->pending(self::BATCH_SIZE);

        if ($orderIds === []) {
            $this->logger->info(LogCode::QUEUE_EMPTY);

            return;
        }

        foreach ($orderIds as $orderId) {
            $this->checkOne($orderId);
        }

        $this->logger->info(LogCode::QUEUE_RUN_FINISHED, [
            'orders processed' => count($orderIds),
        ]);

        if ($this->indexReader->count(ProcessState::ENQUEUED) === 0) {
            $this->logger->info(LogCode::QUEUE_EMPTY);
        }
    }

    private function checkOne(int $orderId): void
    {
        try {
            $this->checkService->process($orderId);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::API_CALL_FAILED,
                $exception,
                [],
                $orderId
            );
        }
    }
}
