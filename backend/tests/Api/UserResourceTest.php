<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use Override;

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
        $this->assertSame($this->getIriFromResource($user), $body['data']['users'][0]['id']);
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
            'id' => $this->getIriFromResource($user),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'User')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($user), $body['data']['user']['id']);
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
            'id' => $this->getIriFromResource($user2),
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
            'id' => $this->getIriFromResource($user),
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
            mutation CreateUser($email: String!, $plainPassword: String!) {
                createUser(input: { email: $email, plainPassword: $plainPassword }) {
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
            'plainPassword' => UserFactory::DEFAULT_PASSWORD,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createUser']['user']['id'], User::class);
        $this->assertSame($variables['email'], $body['data']['createUser']['user']['email']);
        $this->assertSame(['ROLE_USER'], $body['data']['createUser']['user']['roles']);
        $this->assertMatchesDate($body['data']['createUser']['user']['createdAt']);
        $this->assertMatchesDate($body['data']['createUser']['user']['updatedAt']);
    }

    public function testPostReturns422WhenEmailIsMissing(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($plainPassword: String!) {
                createUser(input: { plainPassword: $plainPassword }) {
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
            'plainPassword' => UserFactory::DEFAULT_PASSWORD,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlError($body, 'Field createUserInput.email of required type String! was not provided.');
    }

    public function testPostReturns422WhenEmailIsBlank(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $plainPassword: String!) {
                createUser(input: { email: $email, plainPassword: $plainPassword }) {
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
            'plainPassword' => UserFactory::DEFAULT_PASSWORD,
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
            mutation CreateUser($email: String!, $plainPassword: String!) {
                createUser(input: { email: $email, plainPassword: $plainPassword }) {
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
            'plainPassword' => UserFactory::DEFAULT_PASSWORD,
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
            mutation CreateUser($email: String!, $plainPassword: String!) {
                createUser(input: { email: $email, plainPassword: $plainPassword }) {
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
            'plainPassword' => UserFactory::DEFAULT_PASSWORD,
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

        $this->assertGraphQlError($body, 'Field createUserInput.plainPassword of required type String! was not provided.');
    }

    public function testPostReturns422WhenPasswordIsBlank(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $plainPassword: String!) {
                createUser(input: { email: $email, plainPassword: $plainPassword }) {
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
            'plainPassword' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'plainPassword', ['This value should not be blank.', 'This value is too short. It should have 8 characters or more.']);
        $this->assertNull($body['data']['createUser']);
    }

    public function testPostReturns422WhenPasswordIsTooShort(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateUser($email: String!, $plainPassword: String!) {
                createUser(input: { email: $email, plainPassword: $plainPassword }) {
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
            'plainPassword' => 'pass',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'plainPassword', 'This value is too short. It should have 8 characters or more.');
        $this->assertNull($body['data']['createUser']);
    }

    public function testPatchUpdatesUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $passwordBefore = UserFactory::find($user->getId())->getPassword();

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user),
            'email' => 'updated_user@example.org',
            'plainPassword' => 'new_password',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($user), $body['data']['updateUser']['user']['id']);
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
        $tokenRespository = repository(RefreshToken::class);

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
            'id' => $this->getIriFromResource($user),
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
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user),
            'email' => 'updated_user@example.org',
            'plainPassword' => 'new_password',
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
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user2),
            'email' => 'updated_user@example.org',
            'plainPassword' => 'new_password',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, "Item \"{$this->getIriFromResource($user2)}\" not found.");
        $this->assertGraphQlDebugMessage($body, "Item \"{$this->getIriFromResource($user2)}\" not found.");
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns422WhenEmailIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user),
            'email' => '',
            'plainPassword' => 'new_password',
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
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user),
            'email' => 'updated_user{at}example.org',
            'plainPassword' => 'new_password',
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
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user1),
            'email' => $user2->getEmail(),
            'plainPassword' => 'new_password',
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
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user),
            'email' => 'updated_user@example.org',
            'plainPassword' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'plainPassword', 'This value is too short. It should have 8 characters or more.');
        $this->assertNull($body['data']['updateUser']);
    }

    public function testPatchReturns422WhenPasswordIsTooShort(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateUser($id: ID!, $email: String!, $plainPassword: String!) {
                updateUser(input: { id: $id, email: $email, plainPassword: $plainPassword }) {
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
            'id' => $this->getIriFromResource($user),
            'email' => 'updated_user@example.org',
            'plainPassword' => 'pass',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'plainPassword', 'This value is too short. It should have 8 characters or more.');
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
            'id' => $this->getIriFromResource($user),
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
            'id' => $this->getIriFromResource($user2),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, "Item \"{$this->getIriFromResource($user2)}\" not found.");
        $this->assertGraphQlDebugMessage($body, "Item \"{$this->getIriFromResource($user2)}\" not found.");
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
            'id' => $this->getIriFromResource($user),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteUser')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['deleteUser']);
    }
}
