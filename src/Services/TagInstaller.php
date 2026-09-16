<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\Language;
use EnderecoPlentyOneClient\Enums\TagColor;
use Plenty\Modules\Tag\Contracts\TagRepositoryContract;

/**
 * Creates one of the plugin's tags, the first time it's actually needed -
 * not at deploy time, since a runOnBuild migration runs before
 * translations are loaded, and the backend shows a tag by its tagName.
 *
 * Two things make a tag useless if missed: an "order" availability, or it
 * cannot be put on an order at all; a name per language, or it stays
 * invisible in the backend even though the API returns it.
 */
final class TagInstaller
{
    /** Availability that makes a tag selectable on an order. */
    private const AVAILABILITY_ORDER = 'order';

    public function __construct(
        private TagRepositoryContract $tags,
        private PluginTranslator $translator,
    ) {
    }

    /** Creates the tag and returns its id. */
    public function create(string $tag): int
    {
        $created = $this->tags->createTag([
            'tagName' => $this->translator->trans($tag, [], Language::ENGLISH),
            'color' => TagColor::BY_TAG[$tag] ?? TagColor::NEUTRAL,
            'names' => $this->names($tag),
            'availabilities' => [
                ['tagType' => self::AVAILABILITY_ORDER],
            ],
        ]);

        return $created->id;
    }

    /**
     * One name row per language. Field names are tagLang/tagName.
     *
     * @return list<array<string, string>>
     */
    private function names(string $tag): array
    {
        $names = [];
        $labels = $this->translator->transAllLanguages($tag);

        foreach ($labels as $lang => $label) {
            $names[] = ['tagLang' => $lang, 'tagName' => $label];
        }

        return $names;
    }
}
