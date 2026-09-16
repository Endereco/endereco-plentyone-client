<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CheckFinding;
use EnderecoPlentyOneClient\Enums\CheckOutcome;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Enums\ProcessState;
use EnderecoPlentyOneClient\Enums\TargetStatusCase;

/**
 * Closes out an answered check: first the index row is removed - its job of
 * helping the cron find work is done the moment a decision is made - then a
 * correction is applied if warranted, the result properties are written,
 * tags are updated, a comment is written (unless PluginSettings::
 * writesComments() is off), and the order is put on its resolved target
 * status.
 *
 * Every collaborator called here already guards its own failure internally,
 * so none of this needs its own try/catch; a call added later that can
 * throw has to protect itself the same way.
 */
final class CheckResultWriter
{
    public function __construct(
        private CorrectionApplier $corrections,
        private PackstationRecognitionResolver $packstation,
        private OutcomeResolver $outcomes,
        private OrderPropertyPayload $values,
        private TagUpdater $tags,
        private OrderPropertyWriter $properties,
        private StatusResolver $statusResolver,
        private StatusWriter $statusWriter,
        private OrderCommentWriter $comments,
        private QueueIndexWriter $indexWriter,
        private PluginSettings $settings,
    ) {
    }

    public function write(
        int $orderId,
        AddressDto $original,
        CheckResult $result
    ): void {
        $this->indexWriter->remove($orderId);

        $applied = $this->corrections->apply($orderId, $original, $result);
        $packstationFlags = $this->packstation->resolve($result);

        $outcome = $this->outcomes->resolve($result, $applied);

        $this->writeProperties(
            $orderId,
            $this->values->checked($original, $result, $outcome, $applied)
        );

        $this->tags->update($orderId, $outcome);

        $case = $this->targetStatusCase($result->finding(), $applied);

        if ($this->settings->writesComments()) {
            $this->comments->write($orderId, $case, $result, $packstationFlags);
        }

        $target = $this->statusResolver->resolveTarget($orderId, $case);
        $this->statusWriter->writeTarget($orderId, $target);
    }

    /**
     * The circuit breaker gave up: max attempts reached, the service never
     * answered. Releases the order - this is not a problem with the
     * address, it is our side failing to ask.
     */
    public function giveUp(int $orderId, AddressDto $original, string $attempts): void
    {
        $this->indexWriter->remove($orderId);

        $this->writeProperties($orderId, $this->values->gaveUp($original, $attempts));

        $this->tags->update($orderId, CheckOutcome::FAILED);
        if ($this->settings->writesComments()) {
            $this->comments->writeGaveUp($orderId);
        }

        $target = $this->statusResolver->resolveTarget($orderId, TargetStatusCase::GAVE_UP);
        $this->statusWriter->writeTarget($orderId, $target);
    }

    private function targetStatusCase(string $finding, bool $applied): string
    {
        return match (true) {
            $finding === CheckFinding::CORRECT => TargetStatusCase::CORRECT,
            $finding === CheckFinding::CORRECTABLE && $applied => TargetStatusCase::MINOR_CORRECTION,
            $finding === CheckFinding::CORRECTABLE => TargetStatusCase::SUGGESTION_AVAILABLE,
            $finding === CheckFinding::MAJOR_CORRECTION => TargetStatusCase::SUGGESTION_AVAILABLE,
            $finding === CheckFinding::MULTIPLE_VARIANTS => TargetStatusCase::MULTIPLE_VARIANTS,
            $finding === CheckFinding::NOT_FOUND => TargetStatusCase::NOT_FOUND,
        };
    }

    /**
     * Falls back to a bare FINISHED on failure, so the order never gets
     * stuck as still "in progress" (see OrderProcessingState).
     *
     * @param array<string, string> $values
     */
    private function writeProperties(int $orderId, array $values): void
    {
        if ($this->properties->write($orderId, $values)) {
            return;
        }

        $this->properties->write($orderId, [
            OrderPropertyName::PROCESS_STATE => ProcessState::FINISHED,
        ]);
    }
}
