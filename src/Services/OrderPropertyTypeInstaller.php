<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\Language;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use Plenty\Modules\Order\Property\Contracts\OrderPropertyRepositoryContract;

/**
 * Creates the plugin's order property types.
 *
 * Deploy-time only. Creating a type systemwide while handling an order would
 * add a new type for every single order.
 */
final class OrderPropertyTypeInstaller
{
    /** We always read/write property values as plain strings. */
    private const CAST = 'string';

    /**
     * Sort position wherever Plenty lists property types. Undocumented
     * beyond "the position for sorting"; chosen to sort after PlentyONE's
     * own types, unverified. Confirmed NOT to control the display order
     * among the plugin's own types, whether set at creation or updated
     * afterwards - that turned out to depend on creation order instead,
     * which cannot be corrected after the fact once a type holds real
     * order data (deleteType() then fails on a foreign key, live-confirmed).
     * Get OrderPropertyName::ALL right before a type is ever created.
     */
    private const POSITION = 500;

    public function __construct(
        private OrderPropertyRepositoryContract $properties,
        private OrderPropertyTypeRegistry $registry,
    ) {
    }

    /**
     * Creates the type if it is missing and returns its id.
     *
     * @param value-of<OrderPropertyName::ALL> $name
     */
    public function ensure(string $name): int
    {
        $existing = $this->registry->typeIds()[$name] ?? null;

        if ($existing !== null) {
            return $existing;
        }

        $type = $this->properties->createType([
            'position' => self::POSITION,
            'isErasable' => true,
            'cast' => self::CAST,
            'names' => $this->names($name),
        ]);

        return $type->id;
    }

    /**
     * One name row per language, all carrying the same technical key.
     * OrderPropertyType has no separate field for a stable lookup key, so
     * OrderPropertyTypeRegistry matches on this name string instead. Every
     * language must get the same string, or that match would have to check
     * several possible values instead of one.
     *
     * @return list<array<string, string>>
     */
    private function names(string $name): array
    {
        $names = [];

        foreach (Language::ALL as $language) {
            $names[] = ['lang' => $language, 'name' => $name];
        }

        return $names;
    }
}
