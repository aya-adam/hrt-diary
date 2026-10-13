<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Api\Resource\UserResource;
use App\Entity\RefreshTokenEntity;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use Override;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\repository;

class UserResourceTest extends ApiTestCase
{
    use AuthenticatedClientTrait, GraphQlTestTrait, RegexTrait;

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

        UserFactory::createMany(10);
    }

    public function testGetCollectionReturnsOwnedUserAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Users {
                users {
                    id
                    email
                    roles
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Users')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(1, $body['data']['users']);
        $this->assertSame(sprintf('/users/%s', $user->getId()->toRfc4122()), $body['data']['users'][0]['id']);
        $this->assertSame($user->getEmail(), $body['data']['users'][0]['email']);
        $this->assertSame($user->getRoles(), $body['data']['users'][0]['roles']);
        $this->assertSame($user->getCreatedAt()->format('c'), $body['data']['users'][0]['createdAt']);
        $this->assertSame($user->getUpdatedAt()->format('c'), $body['data']['users'][0]['updatedAt']);
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query Users {
                users {
                    id
                    email
                    roles
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Users')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['users']);
    }

    public function testGetItemReturnsOwnedUserAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query User($id: ID!) {
                user(id: $id) {
                    id
                    email
                    roles
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'User')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame(sprintf('/users/%s', $user->getId()->toRfc4122()), $body['data']['user']['id']);
        $this->assertSame($user->getEmail(), $body['data']['user']['email']);
        $this->assertSame($user->getRoles(), $body['data']['user']['roles']);
        $this->assertSame($user->getCreatedAt()->format('c'), $body['data']['user']['createdAt']);
        $this->assertSame($user->getUpdatedAt()->format('c'), $body['data']['user']['updatedAt']);
    }

    public function testGetItemReturnsNullForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            query User($id: ID!) {
                user(id: $id) {
                    id
                    email
                    roles
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user2->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'User')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull($body['data']['user']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            query User($id: ID!) {
                user(id: $id) {
                    id
                    email
                    roles
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'User')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['user']);
    }

    public function testPostCreatesUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $password: String!) {
                createUser(input: { email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'email' => 'test_user@example.org',
            'password' => faker()->password(20),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createUser']['user']['id'], UserResource::class);
        $this->assertSame($variables['email'], $body['data']['createUser']['user']['email']);
        $this->assertSame(['ROLE_USER'], $body['data']['createUser']['user']['roles']);
        $this->assertMatchesDate($body['data']['createUser']['user']['createdAt']);
        $this->assertMatchesDate($body['data']['createUser']['user']['updatedAt']);
    }

    public function testPostReturns422WhenEmailIsMissing(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($password: String!) {
                createUser(input: { password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'password' => faker()->password(20),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'email', 'This value should not be blank.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPostReturns422WhenEmailIsBlank(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $password: String!) {
                createUser(input: { email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'email' => '',
            'password' => faker()->password(20),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'email', 'This value should not be blank.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPostReturns422WhenEmailIsInvalid(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $password: String!) {
                createUser(input: { email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'email' => 'test_user(at)example.org',
            'password' => faker()->password(20),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'email', 'This value is not a valid email address.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPostReturns422WhenEmailIsAlreadyInUse(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $password: String!) {
                createUser(input: { email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'email' => $user->getEmail(),
            'password' => faker()->password(20),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'email', 'This email is already in use.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPostReturns422WhenPasswordIsMissing(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!) {
                createUser(input: { email: $email }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'email' => 'test_user@example.org',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'password', 'This value should not be blank.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPostReturns422WhenPasswordIsBlank(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $password: String!) {
                createUser(input: { email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'email' => 'test_user@example.org',
            'password' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'password', 'This value should not be blank.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPostReturns422WhenPasswordIsTooWeak(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $password: String!) {
                createUser(input: { email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'email' => 'test_user@example.org',
            'password' => 'password',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'password', 'The password strength is too low. Please use a stronger password.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPatchUpdatesUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $passwordBefore = UserFactory::find($user->getId())->getPassword();

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!, $password: String!) {
                updateUser(input: { id: $id, email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
            'email' => 'updated_user@example.org',
            'password' => faker()->password(20),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame(sprintf('/users/%s', $user->getId()->toRfc4122()), $body['data']['updateUser']['user']['id']);
        $this->assertSame('updated_user@example.org', $body['data']['updateUser']['user']['email']);
        $this->assertSame($user->getRoles(), $body['data']['updateUser']['user']['roles']);
        $this->assertMatchesDate($body['data']['updateUser']['user']['createdAt']);
        $this->assertMatchesDate($body['data']['updateUser']['user']['updatedAt']);

        // Check if the password was updated
        $passwordAfter = UserFactory::find($user->getId())->getPassword();
        $this->assertNotEquals($passwordBefore, $passwordAfter);
    }

    public function testPatchUpdatesPersistedRefreshTokenAfterEmailUpdate(): void
    {
        $tokenRespository = repository(RefreshTokenEntity::class);

        $user = UserFactory::random();
        $emailOld = $user->getEmail();
        $emailNew = 'updated_user@example.org';

        $client = self::createAuthenticatedClient($user);

        $tokenBefore = $tokenRespository->findOneBy(['username' => $emailOld]);
        $this->assertNotNull($tokenBefore);
        $this->assertSame($emailOld, $tokenBefore->getUsername());

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!) {
                updateUser(input: { id: $id, email: $email }) {
                    user {
                        id
                        email
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
            'email' => $emailNew,
        ];

        $this->executeGraphQl($client, $query, $variables, 'UpdateUser');

        $tokenAfter = $tokenRespository->findOneBy(['username' => $emailNew]);
        $this->assertNotNull($tokenAfter);
        $this->assertSame($emailNew, $tokenAfter->getUsername());

        $tokenOld = $tokenRespository->findOneBy(['username' => $emailOld]);
        $this->assertNull($tokenOld);
    }

    public function testPatchReturns403ForAnonymousUser(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!, $password: String!) {
                updateUser(input: { id: $id, email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
            'email' => 'updated_user@example.org',
            'password' => 'new_password',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!, $password: String!) {
                updateUser(input: { id: $id, email: $email, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user2->getId()->toRfc4122()),
            'email' => 'updated_user@example.org',
            'password' => 'new_password',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/users/%s" not found.', $user2->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/users/%s" not found.', $user2->getId()->toRfc4122()));
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns422WhenEmailIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!) {
                updateUser(input: { id: $id, email: $email }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
            'email' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'email', 'This value should not be blank.');
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns422WhenEmailIsInvalid(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!) {
                updateUser(input: { id: $id, email: $email }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
            'email' => 'updated_user{at}example.org',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'email', 'This value is not a valid email address.');
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns422WhenEmailIsAlreadyInUse(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!) {
                updateUser(input: { id: $id, email: $email }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user1->getId()->toRfc4122()),
            'email' => $user2->getEmail(),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'email', 'This email is already in use.');
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns422WhenPasswordIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $password: String!) {
                updateUser(input: { id: $id, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
            'password' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'password', 'This value should not be blank.');
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns422WhenPasswordIsTooWeak(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $password: String!) {
                updateUser(input: { id: $id, password: $password }) {
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
            'password' => 'pass',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'password', 'The password strength is too low. Please use a stronger password.');
        $this->assertNull($body['data']['updateUser']);
    }

    public function testDeleteRemovesUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteUser($id: ID!) {
                deleteUser(input: { id: $id }) {
                    user {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteUser')->toArray(false);

        $this->assertNull(UserFactory::repository()->find($user->getId()));
        $this->assertGraphQlHasNoErrors($body);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation DeleteUser($id: ID!) {
                deleteUser(input: { id: $id }) {
                    user {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user2->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/users/%s" not found.', $user2->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/users/%s" not found.', $user2->getId()->toRfc4122()));
        $this->assertNull($body['data']['deleteUser']);
    }

    public function testDeleteReturns403ForAnonymousUser(): void
    {
        $user = UserFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation DeleteUser($id: ID!) {
                deleteUser(input: { id: $id }) {
                    user {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/users/%s', $user->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['deleteUser']);
    }
}
