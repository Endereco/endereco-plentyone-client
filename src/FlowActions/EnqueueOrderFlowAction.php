<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\FlowActions;

use EnderecoPlentyOneClient\Enums\Procedure;
use EnderecoPlentyOneClient\Services\EnqueueOrderFromFlow;
use EnderecoPlentyOneClient\Services\PluginTranslator;
use Plenty\Modules\Flow\Models\Filter;
use Plenty\Modules\Flow\Models\Output;
use Plenty\Modules\Flow\StepActions\Definitions\Models\Plugin\PluginFlowStepActionDefinition;

/**
 * The flow action "queue order for address validation" - modern
 * registration via PluginFlowRegistrationService (see ServiceProvider).
 * Shaped after the ChangeOrderStatusAction example of the plentyFlow
 * documentation, the only reference for the abstract class - there are no
 * interface stubs for it.
 *
 * Deliberately thin: adapts the modern Input/Output shape to a plain order
 * id and hands off to EnqueueOrderFromFlow, shared with EnqueueOrderProcedure.
 *
 * No constructor and no own properties: the parent already declares a
 * protected $translator, and a conflicting declaration is a fatal error at
 * class load - which boot() hits on every request, taking the whole system
 * down (measured 2026-09-24). Dependencies are resolved where they are needed.
 */
final class EnqueueOrderFlowAction extends PluginFlowStepActionDefinition
{
    public function getIdentifier(): string
    {
        return Procedure::ENQUEUE_ORDER_ID;
    }

    public function getName(): string
    {
        return $this->translator()->trans(Procedure::ENQUEUE_ORDER);
    }

    public function getDescription(): string
    {
        return $this->translator()->trans(Procedure::ENQUEUE_ORDER_HELP);
    }

    public function getTooltip(): string
    {
        return $this->translator()->trans(Procedure::ENQUEUE_ORDER_HELP);
    }

    /** Shown to the AI assistant that builds flows from a description. */
    public function getAIDescription(): string
    {
        return $this->translator()->trans(Procedure::ENQUEUE_ORDER_HELP);
    }

    public function getPluginName(): string
    {
        return Procedure::MODULE;
    }

    public function getIcon(): string
    {
        return Procedure::ICON;
    }

    public function getPathIcon(): string
    {
        return Procedure::ICON;
    }

    /** The action needs no configuration of its own. */
    public function getUIConfigFields(): array
    {
        return [];
    }

    /**
     * Queues every order the flow handed in and passes it on unchanged, so
     * a following step still works on the same orders.
     *
     * $inputs is keyed by object type; for plugin actions that is always the
     * order type (getObjectType()), each entry an Input carrying the order id
     * as its value.
     */
    public function performTask(
        array $inputs,
        array $configFields,
        ?Filter $filter = null,
        array $extraParams = []
    ): array {
        $handler = pluginApp(EnqueueOrderFromFlow::class);
        $objectType = $this->getObjectType();

        $outputs = [];
        foreach ($inputs[$objectType] ?? [] as $input) {
            $orderId = (int) $input->value;

            // Logs its own failures and never throws - see EnqueueOrderFromFlow.
            $handler->handle($orderId);

            $outputs[] = pluginApp(Output::class, [
                'name' => $objectType,
                'value' => (string) $orderId,
            ]);
        }

        return $outputs;
    }

    private function translator(): PluginTranslator
    {
        return pluginApp(PluginTranslator::class);
    }
}
