<?php

namespace App\Factory;

use App\Entity\Post;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Post>
 */
final class PostFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Post::class;
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
            'title' => ucwords(self::faker()->words(self::faker()->numberBetween(3, 5), true)),
            'excerpt' => self::faker()->text(255),
            'text' => self::faker()->paragraphs(self::faker()->numberBetween(1, 5), true),
            'publishedAt' => null,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Post $post): void {})
        ;
    }
}
