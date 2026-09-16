<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\Language;
use EnderecoPlentyOneClient\Enums\Plugin;
use Plenty\Plugin\Translation\Translator;

/**
 * Translations for the plugin's own texts.
 *
 * Wraps the standard Translator and prefixes every key with the plugin
 * namespace, e.g. "EnderecoPlentyOneClient::log.correctionApplied" - "log"
 * being the .properties file under resources/lang/<language>/.
 *
 * transAllLanguages() additionally covers values PlentyONE stores one per
 * language.
 */
final class PluginTranslator
{
    public function __construct(
        private Translator $translator,
    ) {
    }

    /**
     * Turns a plugin-local key into the fully qualified key PlentyONE expects.
     */
    public function fullKey(string $key): string
    {
        return Plugin::NAMESPACE . '::' . $key;
    }

    /**
     * @param array<string, string> $replace ":name" placeholders in the
     *   translated text
     * @param value-of<Language::ALL>|null $language
     */
    public function trans(string $key, array $replace = [], ?string $language = null): string
    {
        return $this->translator->trans($this->fullKey($key), $replace, $language);
    }

    /**
     * The same text in every language the plugin ships - needed wherever
     * PlentyONE stores one value per language, e.g. a tag name.
     *
     * @return array<string, string> language code => text
     */
    public function transAllLanguages(string $key): array
    {
        $translations = [];

        foreach (Language::ALL as $language) {
            $translations[$language] = $this->trans($key, [], $language);
        }

        return $translations;
    }
}
