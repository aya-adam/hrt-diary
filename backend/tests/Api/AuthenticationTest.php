<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use Override;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Clock\ClockAwareTrait;

class AuthenticationTest extends ApiTestCase
{
    use AuthenticatedClientTrait, ClockAwareTrait, GraphQlTestTrait, RegexTrait;

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

        $query = <<<'GQL'
            mutation AuthLogin($email: String!, $password: String!) {
                authLogin(input: { email: $email, password: $password }) {
                    login {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'email' => $user->getUserIdentifier(),
            'password' => UserFactory::DEFAULT_PASSWORD,
        ];

        $response = $this->executeGraphQl($client, $query, $variables, 'AuthLogin');

        $body = $response->toArray(false);
        $headers = $response->getHeaders(false);

        $cookies = [];
        foreach ($headers['set-cookie'] as $cookieString) {
            $cookie = Cookie::fromString($cookieString);
            $cookies[$cookie->getName()] = $cookie;
        }

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNotNull($cookies[self::COOKIE_NAME_ACCESS_TOKEN]);
        $this->assertNotNull($cookies[self::COOKIE_NAME_REFRESH_TOKEN]);
        $this->assertFalse($cookies[self::COOKIE_NAME_ACCESS_TOKEN]->isExpired());
        $this->assertFalse($cookies[self::COOKIE_NAME_REFRESH_TOKEN]->isExpired());
        $this->assertNotNull($cookies[self::COOKIE_NAME_ACCESS_TOKEN]->getValue());
        $this->assertNotNull($cookies[self::COOKIE_NAME_REFRESH_TOKEN]->getValue());
    }

    public function testAuthenticationReturnsAuthenticatedUserData(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation AuthLogin($email: String!, $password: String!) {
                authLogin(input: { email: $email, password: $password }) {
                    login {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'email' => $user->getUserIdentifier(),
            'password' => UserFactory::DEFAULT_PASSWORD,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'AuthLogin')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($user), $body['data']['authLogin']['login']['userId']);
        $this->assertSame($user->getUserIdentifier(), $body['data']['authLogin']['login']['userIdentifier']);
    }

    public function testAuthenticationReturnsCookieInformation(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation AuthLogin($email: String!, $password: String!) {
                authLogin(input: { email: $email, password: $password }) {
                    login {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'email' => $user->getUserIdentifier(),
            'password' => UserFactory::DEFAULT_PASSWORD,
        ];

        $response = $this->executeGraphQl($client, $query, $variables, 'AuthLogin');

        $body = $response->toArray(false);
        $headers = $response->getHeaders(false);

        $cookies = [];
        foreach ($headers['set-cookie'] as $cookieString) {
            $cookie = Cookie::fromString($cookieString);
            $cookies[$cookie->getName()] = $cookie;
        }

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame(\DateTimeImmutable::createFromTimestamp($cookies[self::COOKIE_NAME_ACCESS_TOKEN]->getExpiresTime())->format('c'), $body['data']['authLogin']['login']['accessTokenExpiresAt']);
    }

    public function testAuthenticationReturns401WhenPasswordInvalid(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation AuthLogin($email: String!, $password: String!) {
                authLogin(input: { email: $email, password: $password }) {
                    login {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'email' => $user->getUserIdentifier(),
            'password' => 'invalid_password',
        ];

        $response = $this->executeGraphQl($client, $query, $variables, 'AuthLogin');

        $body = $response->toArray(false);
        $headers = $response->getHeaders(false);

        $this->assertGraphQlErrorStatus($body, 401);
        $this->assertGraphQlError($body, 'Invalid credentials.');
        $this->assertNull($headers['set-cookie']);
    }

    public function testTokenRefreshReAuthenticatesUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $client->getCookieJar()->expire(self::COOKIE_NAME_ACCESS_TOKEN);

        $query = <<<'GQL'
            mutation AuthTokenRefresh {
                authTokenRefresh(input: {  }) {
                    tokenRefresh {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $response = $this->executeGraphQl($client, $query, [], 'AuthTokenRefresh');

        $body = $response->toArray(false);
        $headers = $response->getHeaders(false);

        $cookies = [];
        foreach ($headers['set-cookie'] as $cookieString) {
            $cookie = Cookie::fromString($cookieString);
            $cookies[$cookie->getName()] = $cookie;
        }

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNotNull($cookies[self::COOKIE_NAME_ACCESS_TOKEN]);
        $this->assertNotNull($cookies[self::COOKIE_NAME_REFRESH_TOKEN]);
        $this->assertFalse($cookies[self::COOKIE_NAME_ACCESS_TOKEN]->isExpired());
        $this->assertFalse($cookies[self::COOKIE_NAME_REFRESH_TOKEN]->isExpired());
        $this->assertNotNull($cookies[self::COOKIE_NAME_ACCESS_TOKEN]->getValue());
        $this->assertNotNull($cookies[self::COOKIE_NAME_REFRESH_TOKEN]->getValue());
    }

    public function testTokenRefreshReturns401WhenRefreshTokenIsInvalid(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $refreshTokenCookie = $client->getCookieJar()->get(self::COOKIE_NAME_REFRESH_TOKEN);
        $client->getCookieJar()->expire(self::COOKIE_NAME_REFRESH_TOKEN);

        $newRefreshTokenCookie = new Cookie(
            $refreshTokenCookie->getName(),
            'invalid_cookie_value',
            $refreshTokenCookie->getExpiresTime(),
            $refreshTokenCookie->getPath(),
            $refreshTokenCookie->getDomain(),
            $refreshTokenCookie->isSecure(),
            $refreshTokenCookie->isHttpOnly(),
            false,
            $refreshTokenCookie->getSameSite()
        );

        $client->getCookieJar()->set($newRefreshTokenCookie);

        $query = <<<'GQL'
            mutation AuthTokenRefresh {
                authTokenRefresh(input: {  }) {
                    tokenRefresh {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $response = $this->executeGraphQl($client, $query, [], 'AuthTokenRefresh');

        $body = $response->toArray(false);
        $headers = $response->getHeaders(false);

        $cookies = [];
        foreach ($headers['set-cookie'] as $cookieString) {
            $cookie = Cookie::fromString($cookieString);
            $cookies[$cookie->getName()] = $cookie;
        }

        $this->assertGraphQlErrorStatus($body, 401);
        $this->assertGraphQlError($body, 'Refresh token is invalid or has expired.');
        $this->assertNotNull($cookies[self::COOKIE_NAME_ACCESS_TOKEN]);
        $this->assertNotNull($cookies[self::COOKIE_NAME_REFRESH_TOKEN]);
        $this->assertTrue($cookies[self::COOKIE_NAME_ACCESS_TOKEN]->isExpired());
        $this->assertTrue($cookies[self::COOKIE_NAME_REFRESH_TOKEN]->isExpired());
        $this->assertSame('deleted', $cookies[self::COOKIE_NAME_ACCESS_TOKEN]->getValue());
        $this->assertSame('deleted', $cookies[self::COOKIE_NAME_REFRESH_TOKEN]->getValue());
    }
    
    public function testTokenRefreshReturns401WhenRefreshTokenIsExpired(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $client->getCookieJar()->expire(self::COOKIE_NAME_REFRESH_TOKEN);

        $query = <<<'GQL'
            mutation AuthTokenRefresh {
                authTokenRefresh(input: {  }) {
                    tokenRefresh {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $response = $this->executeGraphQl($client, $query, [], 'AuthTokenRefresh');

        $body = $response->toArray(false);
        $headers = $response->getHeaders(false);

        $this->assertGraphQlErrorStatus($body, 401);
        $this->assertGraphQlError($body, 'No refresh token cookie present.');
        $this->assertNull($headers['set-cookie']);
    }

    public function testLogoutInvalidatesTokens(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation AuthLogout {
                authLogout(input: {  }) {
                    logout {
                        id
                        userId
                        userIdentifier
                        accessTokenExpiresAt
                        message
                    }
                }
            }
        GQL;

        $response = $this->executeGraphQl($client, $query, [], 'AuthLogout');

        $body = $response->toArray(false);
        $headers = $response->getHeaders(false);

        $cookies = [];
        foreach ($headers['set-cookie'] as $cookieString) {
            $cookie = Cookie::fromString($cookieString);
            $cookies[$cookie->getName()] = $cookie;
        }

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame('Successfully logged out.', $body['data']['authLogout']['logout']['message']);
        $this->assertNull($body['data']['authLogout']['logout']['userId']);
        $this->assertNull($body['data']['authLogout']['logout']['userIdentifier']);
        $this->assertNull($body['data']['authLogout']['logout']['accessTokenExpiresAt']);
        $this->assertNotNull($cookies[self::COOKIE_NAME_ACCESS_TOKEN]);
        $this->assertNotNull($cookies[self::COOKIE_NAME_REFRESH_TOKEN]);
        $this->assertTrue($cookies[self::COOKIE_NAME_ACCESS_TOKEN]->isExpired());
        $this->assertTrue($cookies[self::COOKIE_NAME_REFRESH_TOKEN]->isExpired());
        $this->assertSame('deleted', $cookies[self::COOKIE_NAME_ACCESS_TOKEN]->getValue());
        $this->assertSame('deleted', $cookies[self::COOKIE_NAME_REFRESH_TOKEN]->getValue());
    }
}
