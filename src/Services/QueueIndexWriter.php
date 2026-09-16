<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Models\QueueEntry;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

/**
 * Writes the plugin's queue table.
 */
final class QueueIndexWriter
{
    public function __construct(
        private DataBase $database,
        private QueueIndexReader $reader,
        private PluginLogger $logger,
    ) {
    }

    /** Adds the order unless it is listed already; false only on failure. */
    public function add(int $orderId, string $state): bool
    {
        try {
            if ($this->reader->entry($orderId) !== null) {
                return true;
            }

            $entry = pluginApp(QueueEntry::class);
            $entry->orderId = $orderId;
            $entry->state = $state;
            $entry->createdAt = time();

            $this->database->save($entry);
        } catch (\Throwable $exception) {
            $this->logger->exception(LogCode::QUEUE_ENTRY_ADD_FAILED, $exception, [], $orderId);

            return false;
        }

        return true;
    }

    public function remove(int $orderId): void
    {
        try {
            $entry = $this->reader->entry($orderId);

            if ($entry === null) {
                return;
            }

            $this->database->delete($entry);
        } catch (\Throwable $exception) {
            $this->logger->exception(LogCode::QUEUE_ENTRY_REMOVE_FAILED, $exception, [], $orderId);
        }
    }

    /** Moves the row to the next stage; silently ignores an unlisted order. */
    public function moveTo(int $orderId, string $state): void
    {
        try {
            $entry = $this->reader->entry($orderId);

            if ($entry === null) {
                return;
            }

            $entry->state = $state;

            $this->database->save($entry);
        } catch (\Throwable $exception) {
            $this->logger->exception(LogCode::QUEUE_ENTRY_MOVE_FAILED, $exception, ['state' => $state], $orderId);
        }
    }
}
