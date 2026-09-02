<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\LabResult;
use App\Factory\LabResultFactory;
use App\Factory\PostFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;

class LabResultResourceTest extends ApiTestCase
{
    use AuthenticatedClientTrait;

    /**
     * If you're using RecreateDatabaseTrait, RefreshDatabaseTrait, ReloadDatabaseTrait from theofidry/AliceBundle, you
     * probably need to set this property to false in your test class to avoid recreating the database on each client creation.
     *
     * - `null` triggers a deprecation message and always boots the kernel
     * - `false` does not boot the kernel if it's already booted
     * - `true` always boots the kernel without any deprecation message
     */
    protected static ?bool $alwaysBootKernel = true;

    public function testGetCollectionReturnsOwnedLabResultsAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        LabResultFactory::createMany(20);
        LabResultFactory::createMany(20, ['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('GET', '/lab_results');

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

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        LabResultFactory::createMany(20);

        self::createClient()->request('GET', '/lab_results');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsOwnedLabResultsAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('GET', "/lab_results/{$labResult->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testGetItemReturns404ForNonOwnerAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => UserFactory::createOne()]);

        self::createAuthenticatedClient($user)->request('GET', "/lab_results/{$labResult->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $labResult = LabResultFactory::createOne();

        self::createClient()->request('GET', "/lab_results/{$labResult->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostCreatesLabResult(): void
    {
        $user = UserFactory::createOne();

        $response = self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'notes' => 'Test notes',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'notes' => "Test notes",
            'drawnAt' => "2024-07-03T00:08:05+00:00"
        ]);
        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testPostCreatesPublishedLabResult(): void
    {
        $user = UserFactory::createOne();

        $response = self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'notes' => 'Test notes',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'notes' => "Test notes",
            'drawnAt' => "2024-07-03T00:08:05+00:00"
        ]);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testPostCreatesLabResultAssignedToPosts(): void
    {
        $user = UserFactory::createOne();
        $posts = PostFactory::createMany(3, ['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'posts' => [
                "/posts/{$posts[0]->getId()->toRfc4122()}",
                "/posts/{$posts[1]->getId()->toRfc4122()}",
                "/posts/{$posts[2]->getId()->toRfc4122()}",
            ],
            'notes' => 'Test notes',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'posts' => [
                "/posts/{$posts[0]->getId()->toRfc4122()}",
                "/posts/{$posts[1]->getId()->toRfc4122()}",
                "/posts/{$posts[2]->getId()->toRfc4122()}",
            ],
            'notes' => "Test notes",
            'drawnAt' => "2024-07-03T00:08:05+00:00"
        ]);
        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testPostCreatesPublidhedLabResultAssignedToPosts(): void
    {
        $user = UserFactory::createOne();
        $posts = PostFactory::createMany(3, ['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'posts' => [
                "/posts/{$posts[0]->getId()->toRfc4122()}",
                "/posts/{$posts[1]->getId()->toRfc4122()}",
                "/posts/{$posts[2]->getId()->toRfc4122()}",
            ],
            'notes' => 'Test notes',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LabResult',
            '@type' => 'LabResult',
            'posts' => [
                "/posts/{$posts[0]->getId()->toRfc4122()}",
                "/posts/{$posts[1]->getId()->toRfc4122()}",
                "/posts/{$posts[2]->getId()->toRfc4122()}",
            ],
            'notes' => "Test notes",
            'drawnAt' => "2024-07-03T00:08:05+00:00"
        ]);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testPostReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('POST', '/lab_results', ['json' => [
            'notes' => 'Test notes',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostReturns422WhenPostsAreNotOwned(): void
    {
        $user = UserFactory::createOne();
        $posts = PostFactory::createMany(3);

        self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'posts' => [
                "/posts/{$posts[0]->getId()->toRfc4122()}",
                "/posts/{$posts[1]->getId()->toRfc4122()}",
                "/posts/{$posts[2]->getId()->toRfc4122()}",
            ],
            'notes' => 'Test notes',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'posts[0]',
                'message' => 'This value should be of type Post.',
            ]],
        ]);
    }

    public function testPostReturns422WhenDrawnAtIsMissing(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'notes' => 'Test notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'drawnAt',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenDrawnAtIsBlank(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'notes' => 'Test notes',
            'drawnAt' => '',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'drawnAt',
                'message' => 'This value should be of type string.',
            ]],
        ]);
    }

    public function testPostReturns422WhenDrawnAtIsNotDateValue(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'notes' => 'Test notes',
            'drawnAt' => 'invalid_datetime',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'drawnAt',
                'message' => 'This value should be of type string.',
            ]],
        ]);
    }

    public function testPostReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'notes' => '',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'notes',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/lab_results', ['json' => [
            'notes' => '',
            'drawnAt' => '2024-07-03T00:08:05+00:00',
            'published' => 'true',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'published',
                'message' => 'This value should be of type bool|null.',
            ]],
        ]);
    }

    public function testPatchUpdatesLabResult(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => 'Updated Test Notes',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
            ],
        ]);

        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testPatchUpdatesLabResultToPublic(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => 'Updated Test Notes',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
                'published' => true,
            ],
        ]);

        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testPatchUpdatesLabResultToPrivate(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::new()->published()->create(['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => 'Updated Test Notes',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
                'published' => false,
            ],
        ]);

        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/lab_results\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(LabResult::class);
    }

    public function testPatchReturns401ForAnonymousUser(): void
    {
        $labResult = LabResultFactory::createOne();

        self::createClient()->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => 'Updated Test Notes',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => 'Updated Test Notes',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testPatchReturns422WhenPostsAreNotOwned(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);
        $posts = PostFactory::createMany(3);

        self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'posts' => [
                    "/posts/{$posts[0]->getId()->toRfc4122()}",
                    "/posts/{$posts[1]->getId()->toRfc4122()}",
                    "/posts/{$posts[2]->getId()->toRfc4122()}",
                ],
                'notes' => 'Updated Test Notes',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'posts[0]',
                'message' => 'This value should be of type Post.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenDrawnAtIsBlank(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => 'Updated Test Notes',
                'drawnAt' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'drawnAt',
                'message' => 'This value should be of type string.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenDrawnAtIsNotDateValue(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => 'Updated Test Notes',
                'drawnAt' => 'invalid_datetime',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'drawnAt',
                'message' => 'This value should be of type string.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => '',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'notes',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/lab_results/{$labResult->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'notes' => '',
                'drawnAt' => '2024-07-03T00:08:05+00:00',
                'published' => 'true',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'published',
                'message' => 'This value should be of type bool|null.',
            ]],
        ]);
    }

    public function testDeleteRemovesLabResult(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('DELETE', "/lab_results/{$labResult->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne();

        self::createAuthenticatedClient($user)->request('DELETE', "/lab_results/{$labResult->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testDeleteReturns401ForAnonymousUser(): void
    {
        $labResult = LabResultFactory::createOne();

        self::createClient()->request('DELETE', "/lab_results/{$labResult->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }
}
