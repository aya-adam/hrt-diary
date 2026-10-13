<?php

namespace App\Factory;

use App\Entity\EventEntity;
use DateInterval;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<EventEntity>
 */
final class EventFactory extends PersistentObjectFactory
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
        return EventEntity::class;
    }

    public function isCritical(): self
    {
        return $this->with(['critical' => true]);
    }

    public function withRepeatInterval(?int $days = null): self
    {
        $days = $days ?? self::faker()->numberBetween(1, 3);

        return $this->with(['repeatInterval' => \DateInterval::createFromDateString($days === 1 ? $days . ' day' : $days . ' days')]);
    }

    public function withEarlyNotification(?int $days = null): self
    {
        $days = $days ?? self::faker()->numberBetween(7, 14);

        return $this->with(['earlyNotification' => \DateInterval::createFromDateString($days === 1 ? $days . ' day' : $days . ' days')]);
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
            'critical' => self::faker()->boolean(25),
            'date' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('+1 month', '+3 months')),
            'name' => ucwords(self::faker()->words(self::faker()->numberBetween(3, 5), true)),
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
            // ->afterInstantiate(function(EventEntity $event): void {})
        ;
    }
}
