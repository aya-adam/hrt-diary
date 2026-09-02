<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Post;
use App\Factory\LabResultFactory;
use App\Factory\PostFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;

use function Zenstruck\Foundry\faker;

class PostResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsOwnedPostsAsRegularUser(): void
    {
        $user = UserFactory::createOne();

        PostFactory::createMany(20, ['user' => $user]);
        PostFactory::createMany(20);

        $response = self::createAuthenticatedClient($user)->request('GET', '/posts');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(['totalItems' => 20]);
        $this->assertCount(20, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Post::class);
    }

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        PostFactory::createMany(20);

        self::createClient()->request('GET', '/posts');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsOwnedPostAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('GET', "/posts/{$post->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testGetItemReturns404ForNonOwnerAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => UserFactory::createOne()]);

        self::createAuthenticatedClient($user)->request('GET', "/posts/{$post->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $post = PostFactory::createOne();

        self::createClient()->request('GET', "/posts/{$post->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostCreatesPost(): void
    {
        $user = UserFactory::createOne();

        $response = self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]);
        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testPostCreatesPublishedPost(): void
    {
        $user = UserFactory::createOne();

        $response = self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testPostCreatesPostWithLabResults(): void
    {
        $user = UserFactory::createOne();
        $labResults = LabResultFactory::createMany(3, ['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'labResults' => [
                "/lab_results/{$labResults[0]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[1]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[2]->getId()->toRfc4122()}",
            ],
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'labResults' => [
                "/lab_results/{$labResults[0]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[1]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[2]->getId()->toRfc4122()}",
            ],
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]);
        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testPostCreatesPublidhedPostWithLabResults(): void
    {
        $user = UserFactory::createOne();
        $labResults = LabResultFactory::createMany(3, ['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'labResults' => [
                "/lab_results/{$labResults[0]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[1]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[2]->getId()->toRfc4122()}",
            ],
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'labResults' => [
                "/lab_results/{$labResults[0]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[1]->getId()->toRfc4122()}",
                "/lab_results/{$labResults[2]->getId()->toRfc4122()}",
            ],
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testPostReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('POST', '/posts', ['json' => [
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
            'published' => true,
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostReturns422WhenLabResultsAreNotOwned(): void
    {
        $user = UserFactory::createOne();
        $labResult = LabResultFactory::createOne(['user' => UserFactory::createOne()]);

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'labResults' => ["/lab_results/{$labResult->getId()->toRfc4122()}"],
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResults[0]',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPostReturns422WhenTitleIsMissing(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'title',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenTitleIsBlank(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => '',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'title',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenTitleIsTooLong(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => faker()->text(1000),
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'title',
                'message' => 'This value is too long. It should have 255 characters or less.',
            ]],
        ]);
    }

    public function testPostReturns422WhenExcerptIsBlank(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => 'Test Post Title',
            'excerpt' => '',
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'excerpt',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenExcerptIsTooLong(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => 'Test Post Title',
            'excerpt' => faker()->text(1000),
            'text' => 'Test Post Text',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'excerpt',
                'message' => 'This value is too long. It should have 255 characters or less.',
            ]],
        ]);
    }

    public function testPostReturns422WhenTextIsBlank(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => '',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'text',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/posts', ['json' => [
            'title' => 'Test Post Title',
            'excerpt' => 'Test Post Excerpt',
            'text' => 'Test Post Text',
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

    public function testPatchUpdatesPost(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'New Post Title',
            'excerpt' => 'New Post Excerpt',
            'text' => 'New Post Text',
        ]);
        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testPatchUpdatesPostToPublic(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
                'published' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'New Post Title',
            'excerpt' => 'New Post Excerpt',
            'text' => 'New Post Text',
        ]);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testPatchUpdatesPostToPrivate(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::new(['user' => $user])->published()->create();

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
                'published' => false,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Post',
            '@type' => 'Post',
            'title' => 'New Post Title',
            'excerpt' => 'New Post Excerpt',
            'text' => 'New Post Text',
        ]);
        $this->assertNull($response->toArray()['publishedAt']);
        $this->assertMatchesRegularExpression('/^\/posts\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Post::class);
    }

    public function testPatchReturns401ForAnonymousUser(): void
    {
        $post = PostFactory::createOne();

        self::createClient()->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
                'published' => false,
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
        $post = PostFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testPatchReturns422WhenLabResultIsNotOwned(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);
        $labResult = LabResultFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResults' => ["/lab_results/{$labResult->getId()->toRfc4122()}"],
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResults[0]',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenTitleIsBlank(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => '',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'title',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenTitleIsTooLong(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => faker()->text(1000),
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'title',
                'message' => 'This value is too long. It should have 255 characters or less.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenExcerptIsBlank(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => '',
                'text' => 'New Post Text',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'excerpt',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenExcerptIsTooLong(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => faker()->text(1000),
                'text' => 'New Post Text',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'excerpt',
                'message' => 'This value is too long. It should have 255 characters or less.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenTextIsBlank(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'text',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('PATCH', "/posts/{$post->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'New Post Title',
                'excerpt' => 'New Post Excerpt',
                'text' => 'New Post Text',
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

    public function testDeleteRemovesPost(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('DELETE', "/posts/{$post->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $post = PostFactory::createOne(['user' => UserFactory::createOne()]);

        self::createAuthenticatedClient($user)->request('DELETE', "/posts/{$post->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testDeleteReturns401ForAnonymousUser(): void
    {
        $post = PostFactory::createOne();

        self::createClient()->request('DELETE', "/posts/{$post->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }
}
