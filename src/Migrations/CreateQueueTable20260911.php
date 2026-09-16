<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Migrations;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Models\QueueEntry;
use EnderecoPlentyOneClient\Services\PluginLogger;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

/**
 * Brings the queue's table into the shape the model describes.
 *
 * createTable() does nothing if the table already exists, not even add a
 * missing column. updateTable() adds missing columns to an existing table.
 * Both calls are needed: a plugin's table survives uninstall (no hook runs
 * then), so a fresh install can inherit a table from an earlier version of
 * the model. Logged separately so a failure identifies which call broke.
 *
 * A runOnBuild class runs once per class name; renaming forces a fresh run.
 * Observed live: a new or renamed runOnBuild class must be the first entry
 * in plugin.json's runOnBuild list, or it never runs.
 */
final class CreateQueueTable20260911
{
    public function run(Migrate $migrate): void
    {
        $logger = pluginApp(PluginLogger::class);

        try {
            $migrate->createTable(QueueEntry::class);
            $logger->report(LogCode::TABLE_CREATED);
        } catch (\Throwable $exception) {
            $logger->exception(LogCode::TABLE_CREATE_FAILED, $exception);
        }

        try {
            $migrate->updateTable(QueueEntry::class);
            $logger->report(LogCode::TABLE_UPDATED);
        } catch (\Throwable $exception) {
            $logger->exception(LogCode::TABLE_UPDATE_FAILED, $exception);
        }
    }
}
