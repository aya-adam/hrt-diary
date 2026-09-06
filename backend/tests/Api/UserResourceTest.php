<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;

use function Zenstruck\Foundry\Persistence\repository;

class UserResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsOwnedUserAsRegularUser(): void
    {
        $users = UserFactory::createSequence(
            function () {
                foreach (range(1, 10) as $i) {
                    yield ['email' => "user_{$i}@example.org"];
                }
            }
        );

        $response = self::createAuthenticatedClient($users[0])->request('GET', '/users');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/User',
            '@id' => '/users',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);
        $this->assertCount(1, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(User::class);
    }

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('GET', '/users');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsOwnedUserAsRegularUser(): void
    {
        $user = UserFactory::createOne(['email' => 'user@example.org']);

        $response = self::createAuthenticatedClient($user)->request('GET', "/users/{$user->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/User',
            '@type' => 'User',
            'email' => 'user@example.org',
        ]);
        $this->assertMatchesRegularExpression('/^\/users\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testGetItemReturns404ForNonOwnerAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $otherUser = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('GET', "/users/{$otherUser->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $user = UserFactory::createOne();

        self::createClient()->request('GET', "/users/{$user->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostCreatesUserAsAnonymousUser(): void
    {
        $response = self::createClient()->request('POST', '/users', ['json' => [
            'email' => 'user@example.org',
            'plainPassword' => 'password',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/User',
            '@type' => 'User',
            'email' => 'user@example.org',
        ]);
        $this->assertMatchesRegularExpression('/^\/users\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testPostReturns403ForRegularUser(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/users', ['json' => [
            'email' => 'user@example.org',
            'plainPassword' => 'password',
        ]]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testPostReturns422WhenEmailIsMissing(): void
    {
        self::createClient()->request('POST', '/users', ['json' => [
            'plainPassword' => 'password',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'email',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenEmailIsBlank(): void
    {
        self::createClient()->request('POST', '/users', ['json' => [
            'email' => '',
            'plainPassword' => 'password',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'email',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenEmailIsInvalid(): void
    {
        self::createClient()->request('POST', '/users', ['json' => [
            'email' => 'user(at)example.org',
            'plainPassword' => 'password',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'email',
                'message' => 'This value is not a valid email address.',
            ]],
        ]);
    }

    public function testPostReturns422WhenEmailIsAlreadyInUse(): void
    {
        UserFactory::createOne(['email' => 'user@example.org']);

        self::createClient()->request('POST', '/users', ['json' => [
            'email' => 'user@example.org',
            'plainPassword' => 'password',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'email',
                'message' => 'This email is already in use.',
            ]],
        ]);
    }

    public function testPostReturns422WhenPasswordIsMissing(): void
    {
        self::createClient()->request('POST', '/users', ['json' => [
            'email' => 'user@example.org',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'plainPassword',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenPasswordIsBlank(): void
    {
        self::createClient()->request('POST', '/users', ['json' => [
            'email' => 'user@example.org',
            'plainPassword' => '',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'plainPassword',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenPasswordIsTooShort(): void
    {
        self::createClient()->request('POST', '/users', ['json' => [
            'email' => 'user@example.org',
            'plainPassword' => 'pass',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'plainPassword',
                'message' => 'This value is too short. It should have 8 characters or more.',
            ]],
        ]);
    }

    public function testPatchUpdateUserEmail(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'email' => 'new_email@example.org',
            ]
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(['email' => 'new_email@example.org']);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testPatchUpdatesPersistedRefreshTokenAfterEmailUpdate(): void
    {
        $tokenRespository = repository(RefreshToken::class);

        $user = UserFactory::createOne(['email' => 'old_email@example.org']);

        $client = self::createAuthenticatedClient($user);

        $tokenBefore = $tokenRespository->findOneBy(['username' => 'old_email@example.org']);
        $this->assertNotNull($tokenBefore);
        $this->assertSame('old_email@example.org', $tokenBefore->getUsername());

        $client->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'email' => 'new_email@example.org',
            ]
        ]);

        $tokenAfter = $tokenRespository->findOneBy(['username' => 'new_email@example.org']);
        $this->assertNotNull($tokenAfter);
        $this->assertSame('new_email@example.org', $tokenAfter->getUsername());

        $tokenOld = $tokenRespository->findOneBy(['username' => 'old_email@example.org']);
        $this->assertNull($tokenOld);
    }

    public function testPatchUpdatePassword(): void
    {
        $userRepository = repository(User::class);

        $user = UserFactory::createOne();

        $passwordBefore = $userRepository->find($user->getId())->getPassword();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'plainPassword' => 'my_new_user_password',
            ]
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(User::class);

        $passwordAfter = $userRepository->find($user->getId())->getPassword();
        $this->assertNotEquals($passwordBefore, $passwordAfter);
    }

    public function testPatchReturns401ForAnonymousUser(): void
    {
        $user = UserFactory::createOne();

        self::createClient()->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'email' => 'new_user@example.org',
                'plainPassword' => 'new_password',
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
        $otherUser = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$otherUser->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'email' => 'new_user@example.org',
                'plainPassword' => 'new_password',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testPatchReturns422WhenEmailIsBlank(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'email' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'email',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenEmailIsInvalid(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'email' => 'user(at)example.org',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'email',
                'message' => 'This value is not a valid email address.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenEmailIsAlreadyInUse(): void
    {
        UserFactory::createOne(['email' => 'user@example.org']);
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'email' => 'user@example.org',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'email',
                'message' => 'This email is already in use.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenPasswordIsBlank(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'plainPassword' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'plainPassword',
                'message' => 'This value is too short. It should have 8 characters or more.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenPasswordIsTooShort(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/users/{$user->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'plainPassword' => 'pass',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'plainPassword',
                'message' => 'This value is too short. It should have 8 characters or more.',
            ]],
        ]);
    }

    public function testDeleteRemovesUser(): void
    {
        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('DELETE', "/users/{$user->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $otherUser = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('DELETE', "/users/{$otherUser->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testDeleteReturns401ForAnonymousUser(): void
    {
        $user = UserFactory::createOne();

        self::createClient()->request('DELETE', "/users/{$user->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }
}
