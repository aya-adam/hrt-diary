<?php

namespace App\Factory;

use App\Entity\LabResultEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<LabResultEntity>
 */
final class LabResultFactory extends PersistentObjectFactory
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
        return LabResultEntity::class;
    }

    public function published(): self
    {
        return $this->with(['publishedAt' => new \DateTimeImmutable()]);
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
            'notes' => self::faker()->paragraphs(self::faker()->numberBetween(1, 3), true),
            'publishedAt' => null,
            'drawnAt' => self::faker()->dateTimeBetween('-1 month'),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(LabResult $labResult): void {})
        ;
    }
}
