<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Migrations;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Services\OrderPropertyTypeInstaller;
use EnderecoPlentyOneClient\Services\PluginLogger;

/**
 * Creates the order property types the plugin needs - one per
 * OrderPropertyName. Types that exist are found by name and left alone.
 *
 * Display order in the backend follows creation order, not
 * OrderPropertyName::ALL directly, and cannot be corrected afterwards once a
 * type holds real data - get the list order right before first deploy (see
 * OrderPropertyTypeInstaller).
 *
 * A runOnBuild class runs once per class name; renaming forces a fresh run.
 * Observed live: a new or renamed runOnBuild class must be the first entry
 * in plugin.json's runOnBuild list, or it never runs.
 */
final class CreateEnderecoPropertyTypes20260902
{
    public function run(): void
    {
        $installer = pluginApp(OrderPropertyTypeInstaller::class);
        $logger = pluginApp(PluginLogger::class);

        try {
            $created = [];

            foreach (OrderPropertyName::ALL as $name) {
                $created[] = $name . '=' . $installer->ensure($name);
            }

            $logger->report(
                LogCode::MASTER_DATA_CREATED,
                [
                'propertyTypes' => implode(', ', $created),
                ]
            );
        } catch (\Throwable $exception) {
            $logger->exception(LogCode::MASTER_DATA_FAILED, $exception);
        }
    }
}
