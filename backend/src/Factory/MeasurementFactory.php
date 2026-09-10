<?php

namespace App\Factory;

use App\Entity\Hormone;
use App\Entity\Measurement;
use App\Entity\Unit;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

use function Zenstruck\Foundry\Persistence\repository;

/**
 * @extends PersistentObjectFactory<Measurement>
 */
final class MeasurementFactory extends PersistentObjectFactory
{
    private RepositoryDecorator $hormoneRepository;
    private RepositoryDecorator $unitRepository;

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        $this->hormoneRepository = repository(Hormone::class);
        $this->unitRepository = repository(Unit::class);
    }

    #[\Override]
    public static function class(): string
    {
        return Measurement::class;
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
            'hormone' => $this->hormoneRepository->random(),
            'labResult' => LabResultFactory::new(),
            'unit' => $this->unitRepository->random(),
            'value' => self::faker()->randomFloat(2, 0, 9999.99),
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
