<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Api\Resource\AdministrationCycleResource;
use App\Factory\AdministrationCycleFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use DateTimeInterface;
use Override;

use function Zenstruck\Foundry\faker;

class AdministrationCycleResourceTest extends ApiTestCase
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

        UserFactory::createMany(3);

        foreach (UserFactory::all() as $user) {
            AdministrationCycleFactory::createMany(3, ['user' => $user]);
        }
    }

    public function testGetCollectionReturnsOwnedAdministrationCyclesAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query AdministrationCycles {
                administrationCycles {
                    id
                    name
                    startingDate
                    locations
                    startingIndex
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'AdministrationCycles')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(3, $body['data']['administrationCycles']);

        foreach ($body['data']['administrationCycles'] as $administrationCycle) {
            $this->assertMatchesIri($administrationCycle['id'], AdministrationCycleResource::class);
            $this->assertArrayHasKey('name', $administrationCycle);
            $this->assertArrayHasKey('startingDate', $administrationCycle);
            $this->assertIsArray($administrationCycle['locations']);
            $this->assertIsInt($administrationCycle['startingIndex']);
            $this->assertMatchesDate($administrationCycle['createdAt']);
            $this->assertMatchesDate($administrationCycle['updatedAt']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query AdministrationCycles {
                administrationCycles {
                    id
                    name
                    startingDate
                    locations
                    startingIndex
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'AdministrationCycles')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['administrationCycles']);
    }

    public function testGetItemReturnsOwnedAdministrationCycleAsRegularUser(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query AdministrationCycle($id: ID!) {
                administrationCycle(id: $id) {
                    id
                    name
                    startingDate
                    locations
                    startingIndex
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'AdministrationCycle')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['administrationCycle']['id'], AdministrationCycleResource::class);
        $this->assertSame($administrationCycle->getName(), $body['data']['administrationCycle']['name']);
        $this->assertSame($administrationCycle->getStartingDate()->format('Y-m-d'), $body['data']['administrationCycle']['startingDate']);
        $this->assertSame($administrationCycle->getLocations(), $body['data']['administrationCycle']['locations']);
        $this->assertSame($administrationCycle->getStartingIndex(), $body['data']['administrationCycle']['startingIndex']);
        $this->assertSame($administrationCycle->getCreatedAt()->format(DateTimeInterface::ATOM), $body['data']['administrationCycle']['createdAt']);
        $this->assertSame($administrationCycle->getUpdatedAt()->format(DateTimeInterface::ATOM), $body['data']['administrationCycle']['updatedAt']);
    }

    public function testGetItemReturnsNullForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            query AdministrationCycle($id: ID!) {
                administrationCycle(id: $id) {
                    id
                    name
                    startingDate
                    locations
                    startingIndex
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'AdministrationCycle')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull($body['data']['administrationCycle']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $administrationCycle = AdministrationCycleFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            query AdministrationCycle($id: ID!) {
                administrationCycle(id: $id) {
                    id
                    name
                    startingDate
                    locations
                    startingIndex
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'AdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['administrationCycles']);
    }

    public function testPostCreatesAdministrationCycle(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createAdministrationCycle']['administrationCycle']['id'], AdministrationCycleResource::class);
        $this->assertSame('Test Administration Cycle Name', $body['data']['createAdministrationCycle']['administrationCycle']['name']);
        $this->assertSame('2025-10-28', $body['data']['createAdministrationCycle']['administrationCycle']['startingDate']);
        $this->assertSame(['Item 1', 'Item 2', 'Item 3'], $body['data']['createAdministrationCycle']['administrationCycle']['locations']);
        $this->assertSame(0, $body['data']['createAdministrationCycle']['administrationCycle']['startingIndex']);
        $this->assertMatchesDate($body['data']['createAdministrationCycle']['administrationCycle']['createdAt']);
        $this->assertMatchesDate($body['data']['createAdministrationCycle']['administrationCycle']['updatedAt']);
    }

    public function testPostReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenNameIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: {startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => '',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => faker()->text(1000),
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value is too long. It should have 100 characters or less.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingDateIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingDate', 'This value should not be blank.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingDateIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingDate', 'This value should not be blank.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingDateIsNonDateValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => 'invalid_value',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingDate', 'Invalid value. Enter a date in the Y-m-d format.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenLocationsIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'locations', 'This value should not be blank.');
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'Value cannot be validated. Validator needs the \'locations\' property.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenLocationsIsEmptyArray(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => [],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'locations', 'This value should not be blank.');
        $this->assertGraphQlFieldViolation($body, 'locations', 'You must specify at least one location.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenLocationsIsNotArray(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: String!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => 'invalid_value',
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$locations" of type "String!" used in position expecting type "Iterable".');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenLocationsIsArrayOfInvalidValues(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => [100, 'Item 2', ['Item 2.1', 'Item 2.2']],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'locations[0]', 'This value should be of type string.');
        $this->assertGraphQlFieldViolation($body, 'locations[2]', 'This value should be of type string.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingIndexIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'This value should not be blank.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingIndexIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'This value should not be blank.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingIndexIsNegativeIntValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => -10,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'This value should be either positive or zero.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingIndexIsNonIntValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: String!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => '2',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$startingIndex" of type "String!" used in position expecting type "Int".');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPostReturns422WhenStartingIndexIsGreaterThanLocationsArrayLength(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Item 1', 'Item 2', 'Item 3'],
            'startingIndex' => 5,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'This value cannot be greater, than the array length of \'locations\'.');
        $this->assertNull($body['data']['createAdministrationCycle']);
    }

    public function testPatchUpdatesAdministrationCycle(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                updateAdministrationCycle(input: { id: $id, name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'name' => 'Updated Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Updated Item 1', 'Updated Item 2', 'Updated Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updateAdministrationCycle']['administrationCycle']['id'], AdministrationCycleResource::class);
        $this->assertSame('Updated Administration Cycle Name', $body['data']['updateAdministrationCycle']['administrationCycle']['name']);
        $this->assertSame('2025-10-28', $body['data']['updateAdministrationCycle']['administrationCycle']['startingDate']);
        $this->assertSame(['Updated Item 1', 'Updated Item 2', 'Updated Item 3'], $body['data']['updateAdministrationCycle']['administrationCycle']['locations']);
        $this->assertSame(0, $body['data']['updateAdministrationCycle']['administrationCycle']['startingIndex']);
        $this->assertMatchesDate($body['data']['updateAdministrationCycle']['administrationCycle']['createdAt']);
        $this->assertMatchesDate($body['data']['updateAdministrationCycle']['administrationCycle']['updatedAt']);
    }

    public function testPatchReturns403ForAnonymousUser(): void
    {
        $administrationCycle = AdministrationCycleFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                updateAdministrationCycle(input: { id: $id, name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'name' => 'Updated Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Updated Item 1', 'Updated Item 2', 'Updated Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
                updateAdministrationCycle(input: { id: $id, name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'name' => 'Updated Administration Cycle Name',
            'startingDate' => '2025-10-28',
            'locations' => ['Updated Item 1', 'Updated Item 2', 'Updated Item 3'],
            'startingIndex' => 0,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/administration_cycles/%s" not found.', $administrationCycle->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/administration_cycles/%s" not found.', $administrationCycle->getId()->toRfc4122()));
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $name: String!) {
                updateAdministrationCycle(input: { id: $id, name: $name }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'name' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $name: String!) {
                updateAdministrationCycle(input: { id: $id, name: $name }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'name' => faker()->text(1000),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value is too long. It should have 100 characters or less.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenStartingDateIsBlank(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $startingDate: String!) {
                updateAdministrationCycle(input: { id: $id, startingDate: $startingDate }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'startingDate' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingDate', 'This value should not be blank.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenStartingDateIsNonDateValue(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $startingDate: String!) {
                updateAdministrationCycle(input: { id: $id, startingDate: $startingDate }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'startingDate' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingDate', 'Invalid value. Enter a date in the Y-m-d format.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenLocationsIsEmptyArray(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $locations: Iterable!) {
                updateAdministrationCycle(input: { id: $id, locations: $locations }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'locations' => [],
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'locations', 'This value should not be blank.');
        $this->assertGraphQlFieldViolation($body, 'locations', 'You must specify at least one location.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenLocationsIsNotArray(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $locations: String!) {
                updateAdministrationCycle(input: { id: $id, locations: $locations }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'locations' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$locations" of type "String!" used in position expecting type "Iterable".');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenLocationsIsArrayOfInvalidValues(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $locations: Iterable!) {
                updateAdministrationCycle(input: { id: $id, locations: $locations }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'locations' => [100, 'Updated Item 2', ['Updated Item 2.1']],
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'locations[0]', 'This value should be of type string.');
        $this->assertGraphQlFieldViolation($body, 'locations[2]', 'This value should be of type string.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenStartingIndexIsNegativeIntValue(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $startingIndex: Int!) {
                updateAdministrationCycle(input: { id: $id, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'startingIndex' => -10,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'This value should be either positive or zero.');
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'Value cannot be validated. Validator needs the \'locations\' property.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenStartingIndexIsNonIntValue(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $startingIndex: String!) {
                updateAdministrationCycle(input: { id: $id, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'startingIndex' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$startingIndex" of type "String!" used in position expecting type "Int".');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testPatchReturns422WhenStartingIndexIsGreaterThanLocationsArrayLength(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateAdministrationCycle($id: ID!, $locations: Iterable!, $startingIndex: Int!) {
                updateAdministrationCycle(input: { id: $id, locations: $locations, startingIndex: $startingIndex }) {
                    administrationCycle {
                        id
                        name
                        startingDate
                        locations
                        startingIndex
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
            'locations' => ['Updated Item 1', 'Updated Item 2', 'Updated Item 3'],
            'startingIndex' => 5,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'startingIndex', 'This value cannot be greater, than the array length of \'locations\'.');
        $this->assertNull($body['data']['updateAdministrationCycle']);
    }

    public function testDeleteRemovesAdministrationCycle(): void
    {
        $user = UserFactory::random();
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteAdministrationCycle($id: ID!) {
                deleteAdministrationCycle(input: { id: $id }) {
                    administrationCycle {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteAdministrationCycle')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull(AdministrationCycleFactory::repository()->find($administrationCycle->getId()));
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $administrationCycle = AdministrationCycleFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation DeleteAdministrationCycle($id: ID!) {
                deleteAdministrationCycle(input: { id: $id }) {
                    administrationCycle {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/administration_cycles/%s" not found.', $administrationCycle->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/administration_cycles/%s" not found.', $administrationCycle->getId()->toRfc4122()));
        $this->assertNull($body['data']['deleteAdministrationCycle']);
    }

    public function testDeleteReturns403ForAnonymousUser(): void
    {
        $administrationCycle = AdministrationCycleFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation DeleteAdministrationCycle($id: ID!) {
                deleteAdministrationCycle(input: { id: $id }) {
                    administrationCycle {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/administration_cycles/%s', $administrationCycle->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteAdministrationCycle')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['deleteAdministrationCycle']);
    }
}
