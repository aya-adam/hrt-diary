<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Post;
use App\Entity\User;
use App\Factory\PostFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use Override;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class PostTest extends ApiTestCase
{
    use AuthenticatedClientTrait, ResetDatabase, Factories;

    private ?Client $client = null;
    private ?User $user = null;

    /**
     * If you're using RecreateDatabaseTrait, RefreshDatabaseTrait, ReloadDatabaseTrait from theofidry/AliceBundle, you
     * probably need to set this property to false in your test class to avoid recreating the database on each client creation.
     *
     * - `null` triggers a deprecation message and always boots the kernel
     * - `false` does not boot the kernel if it's already booted
     * - `true` always boots the kernel without any deprecation message
     */
    protected static ?bool $alwaysBootKernel = true;

    #[Override]
    public function setUp(): void
    {
        $this->user = UserFactory::createOne(['email' => 'user@example.org']);
        $this->client = self::createAuthenticatedClient($this->user, UserFactory::DEFAULT_PASSWORD, [], [
            'headers' => [
                'accept' => 'application/ld+json',
                'content-type' => 'application/ld+json'
            ]
        ]);

        parent::setUp();
    }

    public function testPostPost(): void
    {
        $response = $this->client->request('POST', '/posts', ['json' => [
            'title' => 'My first post',
            'excerpt' => 'Excerpt of my first post',
            'text' => 'Text of my first post',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'My first post',
            'excerpt' => 'Excerpt of my first post',
            'text' => 'Text of my first post',
        ]);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);

        $response = $this->client->request('POST', '/posts', ['json' => [
            'title' => 'My second post',
            'excerpt' => 'Excerpt of my second post',
            'text' => 'Text of my second post',
            'published' => false,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'My second post',
            'excerpt' => 'Excerpt of my second post',
            'text' => 'Text of my second post',
        ]);
        $this->assertArrayNotHasKey('publishedAt', $response->toArray());
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testGetPostCollection(): void
    {
        PostFactory::createMany(20, ['user' => $this->user]);
        PostFactory::createMany(20);

        $response = $this->client->request('GET', '/posts');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@id' => '/posts',
            '@type' => 'Collection',
            'totalItems' => 20,
        ]);
        $this->assertCount(20, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Post::class);
    }

    public function testGetPost(): void
    {
        $post = PostFactory::createOne([
            'title' => 'Some post',
            'excerpt' => 'The posts excerpt',
            'text' => 'The posts text',
            'user' => $this->user
        ]);
        $response = $this->client->request('GET', '/posts/' . $post->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'Some post',
            'excerpt' => 'The posts excerpt',
            'text' => 'The posts text',
        ]);
        $this->assertArrayNotHasKey('publishedAt', $response->toArray());
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testUpdatePost(): void
    {
        $post = PostFactory::createOne(['user' => $this->user]);

        $response = $this->client->request('PATCH', '/posts/' . $post->getId()->toRfc4122(), [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'My new post title',
                'excerpt' => 'My new post excerpt',
                'text' => 'My new post text',
                'published' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'My new post title',
            'excerpt' => 'My new post excerpt',
            'text' => 'My new post text',
        ]);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testDeletePost(): void
    {
        $post = PostFactory::createOne(['user' => $this->user]);

        $this->client->request('DELETE', '/posts/' . $post->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(204);
    }
}
