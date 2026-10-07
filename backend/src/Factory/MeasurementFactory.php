<?php

namespace App\Factory;

use App\Entity\HormoneEntity;
use App\Entity\MeasurementEntity;
use App\Entity\UnitEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\Persistence\repository;

/**
 * @extends PersistentObjectFactory<MeasurementEntity>
 */
final class MeasurementFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct() {}

    #[\Override]
    public static function class(): string
    {
        return MeasurementEntity::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'hormone' => repository(HormoneEntity::class)->random(),
            'labResult' => LabResultFactory::new(),
            'unit' => repository(UnitEntity::class)->random(),
            'value' => number_format(self::faker()->randomFloat(2, 0, 9999.99), 2, '.', ''),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Measurement $measurement): void {})
        ;
    }
}
