<?php

namespace App\Factory;

use App\Entity\AdministrationCycleEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<AdministrationCycleEntity>
 */
final class AdministrationCycleFactory extends PersistentObjectFactory
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
        return AdministrationCycleEntity::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $locations = array_map(fn($item) => ucwords($item), self::faker()->words(self::faker()->numberBetween(2, 4)));

        return [
            'locations' => $locations,
            'name' => ucwords(self::faker()->words(self::faker()->numberBetween(2, 3), true)),
            'startingDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-2 month', '-1 day')),
            'startingIndex' => self::faker()->numberBetween(0, (count($locations) - 1)),
            'user' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(AdministrationCycleEntity $administrationCycleEntity): void {})
        ;
    }
}
