<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Providers;

use EnderecoPlentyOneClient\Crons\ProcessAddressQueue;
use EnderecoPlentyOneClient\Crons\SweepQueue;
use EnderecoPlentyOneClient\Enums\Procedure;
use EnderecoPlentyOneClient\EventProcedures\EnqueueOrderProcedure;
use EnderecoPlentyOneClient\FlowActions\EnqueueOrderFlowAction;
use EnderecoPlentyOneClient\Listeners\MarkOrderForQueue;
use EnderecoPlentyOneClient\Services\OrderPropertyTypeRegistry;
use EnderecoPlentyOneClient\Services\PluginLogger;
use EnderecoPlentyOneClient\Services\PluginTranslator;
use Plenty\Log\Exceptions\ReferenceTypeException;
use Plenty\Log\Services\ReferenceContainer;
use Plenty\Modules\Cron\Services\CronContainer;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\Flow\Services\PluginFlowRegistrationService;
use Plenty\Modules\Order\Events\OrderCreated;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;

class EnderecoPlentyOneClientServiceProvider extends ServiceProvider
{
    public function boot(
        ReferenceContainer $referenceContainer,
        Dispatcher $dispatcher,
        CronContainer $cronContainer
    ): void {
        $this->registerPropertyTypeRegistry();

        $this->registerLogReferences($referenceContainer);

        $dispatcher->listen(OrderCreated::class, MarkOrderForQueue::class);

        // Not configurable - see ProcessAddressQueue.
        $cronContainer->add(CronContainer::EVERY_FIVE_MINUTES, ProcessAddressQueue::class);

        // Not configurable - see SweepQueue.
        $cronContainer->add(CronContainer::DAILY, SweepQueue::class);

        $this->registerFlowIntegration();
    }

    /**
     * Registers plentyFlow's "queue order" action if the platform has it,
     * otherwise the legacy EventProcedures mechanism - never both.
     *
     * The flow documentation never names PluginFlowRegistrationService's
     * namespace. It lives in Flow\Services; Flow\Contracts, used here before,
     * does not exist ("class ... not found"), which made every boot fall back
     * to the legacy registration without a trace (measured 2026-09-24 on
     * system *****).
     */
    private function registerFlowIntegration(): void
    {
        try {
            if ($this->registerModern()) {
                return;
            }

            $this->registerLegacy();
        } catch (\Throwable) {
            return;
        }
    }

    private function registerModern(): bool
    {
        try {
            $service = pluginApp(PluginFlowRegistrationService::class);
            $service->registerAction(pluginApp(EnqueueOrderFlowAction::class));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Shows up in Flow Studio's action palette under "Plugin (Legacy)". */
    private function registerLegacy(): void
    {
        $service = pluginApp(EventProceduresService::class);
        $translator = pluginApp(PluginTranslator::class);

        $service->registerProcedure(
            Procedure::MODULE,
            ProcedureEntry::EVENT_TYPE_ORDER,
            $translator->transAllLanguages(Procedure::ENQUEUE_ORDER),
            EnqueueOrderProcedure::class . '@enqueue'
        );
    }

    /** Without this the logger cannot attach the order number to an entry. */
    private function registerLogReferences(ReferenceContainer $container): void
    {
        try {
            $container->add([
                PluginLogger::ORDER_REFERENCE => PluginLogger::ORDER_REFERENCE,
            ]);
        } catch (ReferenceTypeException) {
            // Already registered by an earlier boot() call - nothing to do.
        }
    }

    /**
     * Shares one OrderPropertyTypeRegistry per request: the name-to-id
     * mapping it caches never changes at runtime, so its several consumers
     * do not each need their own copy.
     */
    private function registerPropertyTypeRegistry(): void
    {
        try {
            $this->getApplication()->singleton(OrderPropertyTypeRegistry::class);
        } catch (\Throwable) {
            return;
        }
    }
}
