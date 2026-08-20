<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AuthenticationTest extends ApiTestCase
{
    use AuthenticatedClientTrait, ResetDatabase, Factories;

    protected const string ACCESS_TOKEN_COOKIE_NAME = 'access_token';
    protected const string REFRESH_TOKEN_COOKIE_NAME = 'refresh_token';

    /**
     * If you're using RecreateDatabaseTrait, RefreshDatabaseTrait, ReloadDatabaseTrait from theofidry/AliceBundle, you
     * probably need to set this property to false in your test class to avoid recreating the database on each client creation.
     *
     * - `null` triggers a deprecation message and always boots the kernel
     * - `false` does not boot the kernel if it's already booted
     * - `true` always boots the kernel without any deprecation message
     */
    protected static ?bool $alwaysBootKernel = true;

    public function testSuccessfullAuthentication(): void
    {
        UserFactory::createOne(['email' => 'user@example.org', 'password' => '0password0']);

        $response = static::createClient()->request('POST', '/auth/login', ['json' => [
            'username' => 'user@example.org',
            'password' => '0password0',
        ]]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains([
            'email' => 'user@example.org',
        ]);
        $this->assertMatchesRegularExpression('#^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$#', $response->toArray()['id']);
        $this->assertResponseHasCookie(self::ACCESS_TOKEN_COOKIE_NAME);
        $this->assertResponseHasCookie(self::REFRESH_TOKEN_COOKIE_NAME);
    }

    public function testInvalidAuthentication(): void
    {
        UserFactory::createOne(['email' => 'user@example.org', 'password' => '0password0']);

        static::createClient()->request('POST', '/auth/login', ['json' => [
            'username' => 'user@example.org',
            'password' => 'invalid',
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'Invalid credentials.',
        ]);
        $this->assertResponseNotHasCookie(self::ACCESS_TOKEN_COOKIE_NAME);
        $this->assertResponseNotHasCookie(self::REFRESH_TOKEN_COOKIE_NAME);
    }

    public function testSuccessfullTokenRefresh(): void
    {
        $user = UserFactory::createOne(['email' => 'user@example.org']);
        $client = self::createAuthenticatedClient($user);

        $client->getCookieJar()->expire(self::ACCESS_TOKEN_COOKIE_NAME);

        $client->request('GET', '/users/' . $user->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);

        $response = $client->request('POST', '/auth/refresh');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains([
            'email' => 'user@example.org',
        ]);
        $this->assertMatchesRegularExpression('#^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$#', $response->toArray()['id']);
        $this->assertResponseHasCookie(self::ACCESS_TOKEN_COOKIE_NAME);
        $this->assertResponseHasCookie(self::REFRESH_TOKEN_COOKIE_NAME);

        $response = $client->request('GET', '/users/' . $user->getId()->toRfc4122());

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

    public function testInvalidTokenRefresh(): void
    {
        $user = UserFactory::createOne();
        $client = self::createAuthenticatedClient($user);

        $client->getCookieJar()->expire(self::ACCESS_TOKEN_COOKIE_NAME);
        $client->getCookieJar()->expire(self::REFRESH_TOKEN_COOKIE_NAME);

        $client->request('GET', '/users/' . $user->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);

        $client->request('POST', '/auth/refresh');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'Missing JWT Refresh Token',
        ]);
        $this->assertResponseNotHasCookie(self::ACCESS_TOKEN_COOKIE_NAME);
        $this->assertResponseNotHasCookie(self::REFRESH_TOKEN_COOKIE_NAME);

        $client->request('GET', '/users/' . $user->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testLogout(): void
    {
        $client = self::createAuthenticatedClient(UserFactory::createOne());

        $client->request('POST', '/auth/logout');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'message' => 'Successfully logged out',
        ]);
        $this->assertResponseHasCookie(self::ACCESS_TOKEN_COOKIE_NAME);
        $this->assertResponseHasCookie(self::REFRESH_TOKEN_COOKIE_NAME);
        $this->assertNull($client->getCookieJar()->get(self::ACCESS_TOKEN_COOKIE_NAME));
        $this->assertNull($client->getCookieJar()->get(self::REFRESH_TOKEN_COOKIE_NAME));
    }
}
