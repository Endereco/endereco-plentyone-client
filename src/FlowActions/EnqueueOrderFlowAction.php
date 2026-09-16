<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\FlowActions;

use EnderecoPlentyOneClient\Enums\Procedure;
use EnderecoPlentyOneClient\Services\EnqueueOrderFromFlow;
use EnderecoPlentyOneClient\Services\PluginTranslator;
use Plenty\Modules\Flow\DataModels\ConfigForm\FormField;
use Plenty\Modules\Flow\Models\Filter;
use Plenty\Modules\Flow\Models\Input;
use Plenty\Modules\Flow\Models\Output;
use Plenty\Modules\Flow\StepActions\Definitions\Models\Plugin\PluginFlowStepActionDefinition;

/**
 * The flow action "queue order for address validation" - modern
 * registration via PluginFlowRegistrationService, not yet confirmed
 * available on this platform (see ServiceProvider); kept ready for when it
 * is.
 *
 * Deliberately thin: adapts the modern Input/Output shape to a plain order
 * id and hands off to EnqueueOrderFromFlow, shared with EnqueueOrderProcedure
 * so a switch to the modern API needs no logic change.
 */
final class EnqueueOrderFlowAction extends PluginFlowStepActionDefinition
{
    public function __construct(
        private EnqueueOrderFromFlow $handler,
        private PluginTranslator $translator,
    ) {
    }

    public function getIdentifier(): string
    {
        return Procedure::ENQUEUE_ORDER_ID;
    }

    public function getName(): string
    {
        return $this->translator->trans(Procedure::ENQUEUE_ORDER);
    }

    public function getDescription(): string
    {
        return $this->translator->trans(Procedure::ENQUEUE_ORDER_HELP);
    }

    /** Shown to the AI assistant that builds flows from a description. */
    public function getAIDescription(): string
    {
        return $this->translator->trans(Procedure::ENQUEUE_ORDER_HELP);
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
     * @param list<Input>                              $inputs
     * @param array<string, bool|float|int|string|null> $configFields
     * @param array<string, bool|float|int|string|null> $extraParams
     *
     * @return list<Output>
     */
    public function performTask(
        array $inputs,
        array $configFields,
        ?Filter $filter = null,
        array $extraParams = []
    ): array {
        $outputs = [];

        foreach ($inputs as $input) {
            if ($input->type === Procedure::OBJECT_ORDER) {
                $this->handler->handle($input->value);
            }

            $output = pluginApp(Output::class);
            $output->type = $input->type;
            $output->value = $input->value;
            $outputs[] = $output;
        }

        return $outputs;
    }
}
