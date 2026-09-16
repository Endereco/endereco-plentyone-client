<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\Language;
use EnderecoPlentyOneClient\Enums\LogCode;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Tag\Contracts\TagRepositoryContract;

/**
 * Puts the plugin's tags on orders and takes them off again.
 *
 * Tags to be added are created in the system if they do not exist yet; tags
 * to be removed are looked up but never created.
 */
final class TagService
{
    public function __construct(
        private TagRepositoryContract $tags,
        private OrderRepositoryContract $orders,
        private OrderTagPayload $payload,
        private TagInstaller $installer,
        private PluginTranslator $translator,
        private PluginLogger $logger,
    ) {
    }

    /**
     * @param list<string> $remove translation keys of tags to take off
     * @param list<string> $add    translation keys of tags to put on
     */
    public function applyToOrder(
        int $orderId,
        array $remove,
        array $add
    ): bool {
        $addIds = [];

        foreach ($add as $tag) {
            $tagId = $this->findId($tag) ?? $this->create($tag, $orderId);

            if ($tagId !== null) {
                $addIds[] = $tagId;
            }
        }

        $removeIds = [];

        foreach ($remove as $tag) {
            $tagId = $this->findId($tag);

            if ($tagId !== null) {
                $removeIds[] = $tagId;
            }
        }

        return $this->write($orderId, $addIds, $removeIds);
    }

    /** null on failure - the order is left without that tag rather than failing the whole update. */
    private function create(string $tag, int $orderId): ?int
    {
        try {
            return $this->installer->create($tag);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::TAG_CREATE_FAILED,
                $exception,
                ['tag' => $tag],
                $orderId
            );

            return null;
        }
    }

    /**
     * The tag is looked up by its English label, which is also what the
     * installer writes into tagName - the backend displays that field.
     */
    public function findId(string $tag): ?int
    {
        $label = $this->translator->trans($tag, [], Language::ENGLISH);

        try {
            return $this->tags->getTagByName($label)->id;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param list<int> $addIds
     * @param list<int> $removeIds
     */
    private function write(
        int $orderId,
        array $addIds,
        array $removeIds
    ): bool {
        $payload = $this->payload->build($orderId, $addIds, $removeIds);

        try {
            $this->orders->update($orderId, ['tags' => $payload]);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::TAG_MISSING,
                $exception,
                ['tags' => count($payload)],
                $orderId
            );

            return false;
        }

        $this->logger->info(
            LogCode::TAGS_UPDATED,
            ['added' => implode(', ', $addIds), 'removed' => implode(', ', $removeIds)],
            $orderId
        );

        return true;
    }
}
