<?php

namespace App\Factory;

use App\Entity\Hormone;
use App\Entity\ReferenceRange;
use App\Entity\Unit;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\Persistence\repository;

/**
 * @extends PersistentObjectFactory<ReferenceRange>
 */
final class ReferenceRangeFactory extends PersistentObjectFactory
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
        return ReferenceRange::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $lowerLimit = self::faker()->randomFloat(2, 0, 5000);

        return [
            'user' => UserFactory::new(),
            'hormone' => repository(Hormone::class)->random(),
            'unit' => repository(Unit::class)->random(),
            'name' => self::faker()->text(255),
            'lowerLimit' => number_format($lowerLimit, 2, '.', ''),
            'upperLimit' => number_format(self::faker()->randomFloat(2, $lowerLimit + 1, 9999.99), 2, '.', ''),
            'notes' => self::faker()->paragraphs(self::faker()->numberBetween(1, 3), true),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ReferenceRange $referenceRange): void {})
        ;
    }
}
