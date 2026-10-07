<?php

namespace App\Factory;

use App\Entity\UserEntity;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UserEntity>
 */
final class UserFactory extends PersistentObjectFactory
{
    public const string DEFAULT_PASSWORD = 'password';

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[\Override]
    public static function class(): string
    {
        return UserEntity::class;
    }

    public function withGeneratedPassword(): self
    {
        return $this->with(['password' => self::faker()->password()]);
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
            'email' => self::faker()->safeEmail(),
            'password' => self::DEFAULT_PASSWORD,
            'roles' => [],
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function(UserEntity $user, array $attributes): void {
                $user->setPassword($this->passwordHasher->hashPassword($user, $attributes['password']));
            })
        ;
    }
}
