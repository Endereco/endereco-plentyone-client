<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\CheckOutcome;
use EnderecoPlentyOneClient\Enums\OrderTag;

/**
 * Swaps the tags of an order to match the outcome of a check.
 *
 * Removes every tag belonging to any OTHER outcome, not just adds the
 * current ones. "Address validation pending" is always removed too - it is
 * set at enqueue time (see OrderQueueService) and nothing else takes it off.
 */
final class TagUpdater
{
    private const TAGS_BY_OUTCOME = [
        CheckOutcome::CORRECT => [OrderTag::ADDRESS_VALID],
        CheckOutcome::CORRECTED => [
            OrderTag::ADDRESS_VALID,
            OrderTag::ADDRESS_CORRECTED,
        ],
        CheckOutcome::ISSUE => [OrderTag::ADDRESS_ISSUE],
        CheckOutcome::FAILED => [OrderTag::ADDRESS_VALIDATION_FAILED],
    ];

    public function __construct(
        private TagService $tags,
    ) {
    }

    public function update(int $orderId, string $outcome): bool
    {
        $keep = self::TAGS_BY_OUTCOME[$outcome] ?? [];

        $remove = array_values(array_unique(array_merge(
            [OrderTag::ADDRESS_VALIDATION_PENDING],
            array_diff($this->allOutcomeTags(), $keep)
        )));

        return $this->tags->applyToOrder($orderId, $remove, $keep);
    }

    /** @return list<string> */
    private function allOutcomeTags(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::TAGS_BY_OUTCOME))));
    }
}
