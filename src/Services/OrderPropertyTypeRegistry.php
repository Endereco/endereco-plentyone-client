<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use Plenty\Modules\Order\Property\Contracts\OrderPropertyRepositoryContract;

/**
 * Resolves the numeric type ids PlentyONE assigned to the plugin's order
 * properties.
 *
 * The ids are system specific, so they are looked up by name and never
 * hard-coded.
 */
final class OrderPropertyTypeRegistry
{
    /**
     * Cache for the request: bound as a singleton in the ServiceProvider,
     * so this loads once and every consumer shares the result.
     *
     * @var array<string, int>|null
     */
    private ?array $typeIds = null;

    public function __construct(
        private OrderPropertyRepositoryContract $properties,
    ) {
    }

    /**
     * Type id per property name. A name missing from the result means the type
     * does not exist yet; callers must handle that instead of creating it.
     *
     * @return array<string, int>
     */
    public function typeIds(): array
    {
        if ($this->typeIds === null) {
            $this->typeIds = $this->load();
        }

        return $this->typeIds;
    }

    /** @return array<string, int> */
    private function load(): array
    {
        $typeIds = [];

        foreach ($this->properties->getTypes([]) as $type) {
            foreach ($type->names as $name) {
                if (in_array($name->name, OrderPropertyName::ALL, true)) {
                    $typeIds[$name->name] = $type->id;
                }
            }
        }

        return $typeIds;
    }
}
