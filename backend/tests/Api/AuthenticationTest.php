<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use Override;
use Symfony\Component\BrowserKit\Cookie;

class AuthenticationTest extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected const string COOKIE_NAME_ACCESS_TOKEN = 'access_token';
    protected const string COOKIE_NAME_REFRESH_TOKEN = 'refresh_token';

    protected User $user;

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

        parent::setUp();
    }

    public function testAuthenticationAuthenticatesUser(): void
    {
        $client = self::createClient();

        $client->request('POST', '/auth/login', ['json' => [
            'username' => 'user@example.org',
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHasCookie(self::COOKIE_NAME_ACCESS_TOKEN);
        $this->assertResponseHasCookie(self::COOKIE_NAME_REFRESH_TOKEN);
        $this->assertFalse($client->getCookieJar()->get(self::COOKIE_NAME_ACCESS_TOKEN)->isExpired());
        $this->assertFalse($client->getCookieJar()->get(self::COOKIE_NAME_REFRESH_TOKEN)->isExpired());
        $this->assertNotSame('', $client->getCookieJar()->get(self::COOKIE_NAME_REFRESH_TOKEN)->getValue());
        $this->assertNotSame('', $client->getCookieJar()->get(self::COOKIE_NAME_REFRESH_TOKEN)->getValue());
    }

    public function testAuthenticationReturnsAuthenticatedUser(): void
    {
        $response = self::createClient()->request('POST', '/auth/login', ['json' => [
            'username' => 'user@example.org',
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]]);

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

    public function testAuthenticationReturns401WhenPasswordInvalid(): void
    {
        self::createClient()->request('POST', '/auth/login', ['json' => [
            'username' => 'user@example.org',
            'password' => 'wrong_password',
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'Invalid credentials.',
        ]);
    }

    public function testTokenRefreshReAuthenticatesUser(): void
    {
        $client = self::createAuthenticatedClient($this->user);

        $client->getCookieJar()->expire(self::COOKIE_NAME_ACCESS_TOKEN);

        $response = $client->request('POST', '/auth/refresh');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHasCookie(self::COOKIE_NAME_ACCESS_TOKEN);
        $this->assertResponseHasCookie(self::COOKIE_NAME_REFRESH_TOKEN);
        $this->assertFalse($client->getCookieJar()->get(self::COOKIE_NAME_ACCESS_TOKEN)->isExpired());
        $this->assertFalse($client->getCookieJar()->get(self::COOKIE_NAME_REFRESH_TOKEN)->isExpired());
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

    public function testTokenRefreshReturns401WhenRefreshTokenIsInvalid(): void
    {
        $client = self::createAuthenticatedClient($this->user);

        $client->getCookieJar()->expire(self::COOKIE_NAME_ACCESS_TOKEN);
        $client->getCookieJar()->expire(self::COOKIE_NAME_REFRESH_TOKEN);

        $client->request('POST', '/auth/refresh');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'Missing JWT Refresh Token',
        ]);
    }

    public function testLogoutInvalidatesTokens(): void
    {
        $client = self::createAuthenticatedClient($this->user);

        $client->request('POST', '/auth/logout');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseCookieValueSame(self::COOKIE_NAME_ACCESS_TOKEN, '');
        $this->assertResponseCookieValueSame(self::COOKIE_NAME_REFRESH_TOKEN, '');
    }

    public function testLogoutReturns401WhenAccessTokenIsInvalid(): void
    {
        $client = self::createAuthenticatedClient($this->user);

        $client->getCookieJar()->expire(self::COOKIE_NAME_ACCESS_TOKEN);
        $client->getCookieJar()->expire(self::COOKIE_NAME_REFRESH_TOKEN);

        $client->request('POST', '/auth/logout');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }
}
