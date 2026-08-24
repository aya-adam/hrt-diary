<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\LabResult;
use App\Entity\User;
use App\Factory\LabResultFactory;
use App\Factory\PostFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use Override;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LabResultResourceTest extends ApiTestCase
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

    public function testPostLabResult(): void
    {
        $posts = PostFactory::createMany(2, ['user' => $this->user]);

        $response = $this->client->request('POST', '/lab_results', ['json' => [
            'posts' => ['/posts/' . $posts[0]->getId()->toRfc4122()],
            'notes' => 'My first note on my first labs',
            'published' => false,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'posts' => ['/posts/' . $posts[0]->getId()->toRfc4122()],
            'notes' => 'My first note on my first labs',
        ]);
        $this->assertArrayNotHasKey('publishedAt', $response->toArray());
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);

        $response = $this->client->request('POST', '/lab_results', ['json' => [
            'posts' => ['/posts/' . $posts[1]->getId()->toRfc4122()],
            'notes' => 'My second note on my second labs',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'posts' => ['/posts/' . $posts[1]->getId()->toRfc4122()],
            'notes' => 'My second note on my second labs',
        ]);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);

        $response = $this->client->request('POST', '/lab_results', ['json' => [
            'notes' => 'My third note on my third labs without posts',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'posts' => [],
            'notes' => 'My third note on my third labs without posts',
        ]);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testGetLabResultCollection(): void
    {
        LabResultFactory::createMany(20, ['user' => $this->user]);
        LabResultFactory::createMany(20);

        $response = $this->client->request('GET', '/lab_results');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@id' => '/lab_results',
            '@type' => 'Collection',
            'totalItems' => 20,
        ]);
        $this->assertCount(20, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(LabResult::class);
    }

    public function testGetLabResult(): void
    {
        $labResult = LabResultFactory::createOne([
            'posts' => PostFactory::createMany(2, ['user' => $this->user]),
            'notes' => 'Lab notes',
            'user' => $this->user,
        ]);

        $response = $this->client->request('GET', '/lab_results/' . $labResult->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'posts' => array_map(fn($post) => '/posts/' . $post->getId()->toRfc4122(), $labResult->getPosts()->toArray()),
            'notes' => 'Lab notes',
        ]);
        $this->assertCount($labResult->getPosts()->count(), $response->toArray()['posts']);
        $this->assertArrayNotHasKey('publishedAt', $response->toArray());
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testUpdateLabResult(): void
    {
        $labResult = LabResultFactory::createOne(['user' => $this->user]);
        $posts = PostFactory::createMany(2, ['user' => $this->user]);

        $response = $this->client->request('PATCH', '/lab_results/' . $labResult->getId()->toRfc4122(), [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'posts' => array_map(fn($post) => '/posts/' . $post->getId()->toRfc4122(), $posts),
                'notes' => 'My new lab notes',
                'published' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'posts' => array_map(fn($post) => '/posts/' . $post->getId()->toRfc4122(), $posts),
            'notes' => 'My new lab notes',
        ]);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testDeletePost(): void
    {
        $labResult = LabResultFactory::createOne(['user' => $this->user]);

        $this->client->request('DELETE', '/lab_results/' . $labResult->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(204);
    }
}
