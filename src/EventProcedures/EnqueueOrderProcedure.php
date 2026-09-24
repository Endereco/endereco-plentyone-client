<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\EventProcedures;

use EnderecoPlentyOneClient\Services\EnqueueOrderFromFlow;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;

/**
 * The flow action "queue order for address validation" - legacy
 * registration via EventProceduresService, the fallback when the modern
 * plentyFlow registration is not available (see ServiceProvider).
 *
 * Deliberately thin: adapts EventProceduresTriggered to a plain order id
 * and hands off to EnqueueOrderFromFlow, shared with EnqueueOrderFlowAction
 * so a switch to the modern API needs no logic change.
 */
final class EnqueueOrderProcedure
{
    public function __construct(
        private EnqueueOrderFromFlow $handler,
    ) {
    }

    /** A procedure returns nothing - it acts. */
    public function enqueue(EventProceduresTriggered $event): void
    {
        $this->handler->handle($event->getOrder()->id);
    }
}
