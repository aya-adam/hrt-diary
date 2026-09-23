<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\RegexTrait;
use Override;

class AuthenticationTest extends ApiTestCase
{
    use AuthenticatedClientTrait, RegexTrait;

    protected const string COOKIE_NAME_ACCESS_TOKEN = 'access_token';
    protected const string COOKIE_NAME_REFRESH_TOKEN = 'refresh_token';

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
        parent::setUp();

        UserFactory::createOne();
    }

    public function testAuthenticationAuthenticatesUser(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $client->request('POST', '/auth/login', ['json' => [
            'username' => $user->getUserIdentifier(),
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
        $user = UserFactory::random();

        $response = self::createClient()->request('POST', '/auth/login', ['json' => [
            'username' => $user->getUserIdentifier(),
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains([
            'user' => [
                'email' => $user->getUserIdentifier(),
            ],
        ]);
        $this->assertSame($this->getIriFromResource($user), $response->toArray()['user']['id']);
        $this->assertMatchesDate($response->toArray()['user']['createdAt']);
        $this->assertMatchesDate($response->toArray()['user']['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testAuthenticationReturnsCookieInformation(): void
    {
        $user = UserFactory::random();

        $response = self::createClient()->request('POST', '/auth/login', ['json' => [
            'username' => $user->getUserIdentifier(),
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertMatchesRegularExpression('/^\/[^\s?#]*$/', $response->toArray()['cookies'][self::COOKIE_NAME_ACCESS_TOKEN]['path']);
        $this->assertMatchesRegularExpression('/^\/[^\s?#]*$/', $response->toArray()['cookies'][self::COOKIE_NAME_REFRESH_TOKEN]['path']);
        $this->assertMatchesRegularExpression('/^(?:0|[1-9]\d{0,9})$/', $response->toArray()['cookies'][self::COOKIE_NAME_ACCESS_TOKEN]['expiresAt']);
        $this->assertMatchesRegularExpression('/^(?:0|[1-9]\d{0,9})$/', $response->toArray()['cookies'][self::COOKIE_NAME_REFRESH_TOKEN]['expiresAt']);
    }

    public function testAuthenticationReturns401WhenPasswordInvalid(): void
    {
        $user = UserFactory::random();

        self::createClient()->request('POST', '/auth/login', ['json' => [
            'username' => $user->getUserIdentifier(),
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
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $client->getCookieJar()->expire(self::COOKIE_NAME_ACCESS_TOKEN);

        $response = $client->request('POST', '/auth/refresh');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHasCookie(self::COOKIE_NAME_ACCESS_TOKEN);
        $this->assertResponseHasCookie(self::COOKIE_NAME_REFRESH_TOKEN);
        $this->assertFalse($client->getCookieJar()->get(self::COOKIE_NAME_ACCESS_TOKEN)->isExpired());
        $this->assertFalse($client->getCookieJar()->get(self::COOKIE_NAME_REFRESH_TOKEN)->isExpired());
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains([
            'user' => [
                'email' => $user->getUserIdentifier(),
            ],
        ]);
        $this->assertSame($this->getIriFromResource($user), $response->toArray()['user']['id']);
        $this->assertMatchesDate($response->toArray()['user']['createdAt']);
        $this->assertMatchesDate($response->toArray()['user']['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testTokenRefreshReturns401WhenRefreshTokenIsInvalid(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

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
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $client->request('POST', '/auth/logout');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseCookieValueSame(self::COOKIE_NAME_ACCESS_TOKEN, '');
        $this->assertResponseCookieValueSame(self::COOKIE_NAME_REFRESH_TOKEN, '');
    }
}
