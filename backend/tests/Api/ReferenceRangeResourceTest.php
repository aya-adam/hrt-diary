<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\HormoneEntity;
use App\Entity\ReferenceRangeEntity;
use App\Entity\UnitEntity;
use App\Factory\ReferenceRangeFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use Override;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\repository;

class ReferenceRangeResourceTest extends ApiTestCase
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

        foreach (UserFactory::all() as $user) {
            ReferenceRangeFactory::createMany(3, ['user' => $user]);
        }
    }

    public function testGetCollectionReturnsOwnedAndAppOwnedReferenceRangesAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query ReferenceRanges {
                referenceRanges {
                    id
                    name
                    lowerLimit
                    upperLimit
                    notes
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'ReferenceRanges')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(5, $body['data']['referenceRanges']);

        foreach ($body['data']['referenceRanges'] as $referenceRange) {
            $this->assertMatchesIri($referenceRange['id'], ReferenceRangeEntity::class);
            $this->assertIsString($referenceRange['name']);
            $this->assertIsNumeric($referenceRange['lowerLimit']);
            $this->assertIsNumeric($referenceRange['upperLimit']);
            $this->assertArrayHasKey('notes', $referenceRange);
            $this->assertMatchesDate($referenceRange['createdAt']);
            $this->assertMatchesDate($referenceRange['updatedAt']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query ReferenceRanges {
                referenceRanges {
                    id
                    name
                    lowerLimit
                    upperLimit
                    notes
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'ReferenceRanges')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['referenceRanges']);
    }

    public function testGetItemReturnsOwnedReferenceRangeAsRegularUser(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query ReferenceRange($id: ID!) {
                referenceRange(id: $id) {
                    id
                    name
                    lowerLimit
                    upperLimit
                    notes
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'ReferenceRange')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($referenceRange), $body['data']['referenceRange']['id']);
        $this->assertSame($referenceRange->getName(), $body['data']['referenceRange']['name']);
        $this->assertSame($referenceRange->getLowerLimit(), $body['data']['referenceRange']['lowerLimit']);
        $this->assertSame($referenceRange->getUpperLimit(), $body['data']['referenceRange']['upperLimit']);
        $this->assertSame($referenceRange->getNotes(), $body['data']['referenceRange']['notes']);
        $this->assertSame($referenceRange->getCreatedAt()->format('c'), $body['data']['referenceRange']['createdAt']);
        $this->assertSame($referenceRange->getUpdatedAt()->format('c'), $body['data']['referenceRange']['updatedAt']);
    }

    public function testGetItemReturnsNullForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $referenceRange = ReferenceRangeFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            query ReferenceRange($id: ID!) {
                referenceRange(id: $id) {
                    id
                    name
                    lowerLimit
                    upperLimit
                    notes
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'ReferenceRange')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull($body['data']['referenceRange']);
    }

    public function testGetItemReturnsForAnonymousUser(): void
    {
        $referenceRange = ReferenceRangeFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            query ReferenceRange($id: ID!) {
                referenceRange(id: $id) {
                    id
                    name
                    lowerLimit
                    upperLimit
                    notes
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'ReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['referenceRange']);
    }

    public function testPostCreatesReferenceRange(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        hormone {
                            id    
                            name
                            commonName
                        }
                        unit {
                            id
                            symbol
                            label 
                        }
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $hormone = repository(HormoneEntity::class)->random();
        $unit = repository(UnitEntity::class)->random();

        $variables = [
            'hormone' => $this->getIriFromResource($hormone),
            'unit' => $this->getIriFromResource($unit),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createReferenceRange']['referenceRange']['id'], ReferenceRangeEntity::class);
        $this->assertSame('New reference range name', $body['data']['createReferenceRange']['referenceRange']['name']);
        $this->assertSame('1234.56', $body['data']['createReferenceRange']['referenceRange']['lowerLimit']);
        $this->assertSame('9876.54', $body['data']['createReferenceRange']['referenceRange']['upperLimit']);
        $this->assertSame('New reference range notes', $body['data']['createReferenceRange']['referenceRange']['notes']);
        $this->assertMatchesDate($body['data']['createReferenceRange']['referenceRange']['createdAt']);
        $this->assertMatchesDate($body['data']['createReferenceRange']['referenceRange']['updatedAt']);

        $this->assertSame($this->getIriFromResource($hormone), $body['data']['createReferenceRange']['referenceRange']['hormone']['id']);
        $this->assertSame($hormone->getName(), $body['data']['createReferenceRange']['referenceRange']['hormone']['name']);
        $this->assertSame($hormone->getCommonName(), $body['data']['createReferenceRange']['referenceRange']['hormone']['commonName']);

        $this->assertSame($this->getIriFromResource($unit), $body['data']['createReferenceRange']['referenceRange']['unit']['id']);
        $this->assertSame($unit->getSymbol(), $body['data']['createReferenceRange']['referenceRange']['unit']['symbol']);
        $this->assertSame($unit->getLabel(), $body['data']['createReferenceRange']['referenceRange']['unit']['label']);
    }

    // public function testPostWithoutUnitCreatesReferenceRangeWithFallbackUnit(): void
    // {
    //     $user = UserFactory::random();

    //     $client = self::createAuthenticatedClient($user);

    //     $query = <<<'GQL'
    //         mutation CreateReferenceRange(
    //             $hormone: String!
    //             $name: String!
    //             $lowerLimit: String!
    //             $upperLimit: String!
    //         ) {
    //             createReferenceRange(
    //                 input: {
    //                     hormone: $hormone
    //                     name: $name
    //                     lowerLimit: $lowerLimit
    //                     upperLimit: $upperLimit
    //                 }
    //             ) {
    //                 referenceRange {
    //                     id
    //                     unit {
    //                         id
    //                         symbol
    //                         label 
    //                     }
    //                 }
    //             }
    //         }
    //     GQL;

    //     $variables = [
    //         'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
    //         'name' => 'New reference range name',
    //         'lowerLimit' => '1234.56',
    //         'upperLimit' => '9876.54',
    //     ];

    //     $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

    //     $this->assertGraphQlHasNoErrors($body);
    //     $this->assertMatchesIri($body['data']['createReferenceRange']['referenceRange']['unit']['id'], UnitEntity::class);
    //     $this->assertArrayHasKey('unit', $body['data']['createReferenceRange']['referenceRange']);
    //     $this->assertArrayHasKey('symbol', $body['data']['createReferenceRange']['referenceRange']['unit']);
    //     $this->assertArrayHasKey('label', $body['data']['createReferenceRange']['referenceRange']['unit']);
    // }

    public function testPostReturnsForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenHormoneIsInvalidIri(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $hormone = repository(HormoneEntity::class)->random();

        $variables = [
            'hormone' => $hormone->getId()->toRfc4122(),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, sprintf('Invalid IRI "%s".', $hormone->getId()->toRfc4122()));
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenHormoneIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Field createReferenceRangeInput.hormone of required type String! was not provided.');
    }

    public function testPostReturns422WhenHormoneIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => '',
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Invalid IRI "".');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenUnitIsInvalidIri(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $unit = repository(UnitEntity::class)->random();

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $unit->getId()->toRfc4122(),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, sprintf('Invalid IRI "%s".', $unit->getId()->toRfc4122()));
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenUnitIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => '',
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Invalid IRI "".');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenNameIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Field createReferenceRangeInput.name of required type String! was not provided.');
    }

    public function testPostReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => '',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => faker()->text(1000),
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value is too long. It should have 255 characters or less.');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenLowerLimitIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Field createReferenceRangeInput.lowerLimit of required type String! was not provided.');
    }

    public function testPostReturns422WhenLowerLimitIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'lowerLimit', ['This value should not be blank.', 'This value should be of type numeric.']);
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenLowerLimitIsNotNumeric(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => 'non_numeric_value',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'lowerLimit', ['This value should be of type numeric.', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).']);
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenLowerLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '12345.678',
            'upperLimit' => '9876.54',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'lowerLimit', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenUpperLimitIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Field createReferenceRangeInput.upperLimit of required type String! was not provided.');
    }

    public function testPostReturns422WhenUpperLimitIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'upperLimit', ['This value should not be blank.', 'This value should be of type numeric.']);
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenUpperLimitIsNotNumeric(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => 'non_numeric_value',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'upperLimit', ['This value should be of type numeric.', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).']);
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenUpperLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.543',
            'notes' => 'New reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'upperLimit', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPostReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateReferenceRange(
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                createReferenceRange(
                    input: {
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'New reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'notes', 'This value should not be blank.');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testPatchUpdatedReferenceRange(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        hormone {
                            id
                            name
                            commonName
                        }
                        unit {
                            id
                            symbol
                            label
                        }
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $hormone = repository(HormoneEntity::class)->random();
        $unit = repository(UnitEntity::class)->random();

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource($hormone),
            'unit' => $this->getIriFromResource($unit),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updateReferenceRange']['referenceRange']['id'], ReferenceRangeEntity::class);
        $this->assertSame('Updated reference range name', $body['data']['updateReferenceRange']['referenceRange']['name']);
        $this->assertSame('1234.56', $body['data']['updateReferenceRange']['referenceRange']['lowerLimit']);
        $this->assertSame('9876.54', $body['data']['updateReferenceRange']['referenceRange']['upperLimit']);
        $this->assertSame('Updated reference range notes', $body['data']['updateReferenceRange']['referenceRange']['notes']);
        $this->assertMatchesDate($body['data']['updateReferenceRange']['referenceRange']['createdAt']);
        $this->assertMatchesDate($body['data']['updateReferenceRange']['referenceRange']['updatedAt']);

        $this->assertSame($this->getIriFromResource($hormone), $body['data']['updateReferenceRange']['referenceRange']['hormone']['id']);
        $this->assertSame($hormone->getName(), $body['data']['updateReferenceRange']['referenceRange']['hormone']['name']);
        $this->assertSame($hormone->getCommonName(), $body['data']['updateReferenceRange']['referenceRange']['hormone']['commonName']);

        $this->assertSame($this->getIriFromResource($unit), $body['data']['updateReferenceRange']['referenceRange']['unit']['id']);
        $this->assertSame($unit->getSymbol(), $body['data']['updateReferenceRange']['referenceRange']['unit']['symbol']);
        $this->assertSame($unit->getLabel(), $body['data']['updateReferenceRange']['referenceRange']['unit']['label']);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $referenceRange = ReferenceRangeFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns404ForAppOwnedReferenceRangeAsRegularUser(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => null]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns403ForAnonymousUser(): void
    {
        $referenceRange = ReferenceRangeFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenHormoneIsInvalidIri(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $hormone = repository(HormoneEntity::class)->random();

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $hormone->getId()->toRfc4122(),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, sprintf('Invalid IRI "%s".', $hormone->getId()->toRfc4122()));
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenHormoneIsBlank(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => '',
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Invalid IRI "".');
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenUnitIsInvalidIri(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $unit = repository(UnitEntity::class)->random();

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $unit->getId()->toRfc4122(),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, sprintf('Invalid IRI "%s".', $unit->getId()->toRfc4122()));
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenUnitIsBlank(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => '',
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlError($body, 'Invalid IRI "".');
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => '',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => faker()->text(1000),
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value is too long. It should have 255 characters or less.');
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenLowerLimitIsBlank(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'lowerLimit', ['This value should not be blank.', 'This value should be of type numeric.']);
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenLowerLimitIsNotNumeric(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => 'non_numeric_value',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'lowerLimit', ['This value should be of type numeric.', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).']);
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenLowerLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.567',
            'upperLimit' => '9876.54',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'lowerLimit', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).');
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenUpperLimitIsBlank(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'upperLimit', ['This value should not be blank.', 'This value should be of type numeric.']);
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenUpperLimitIsNotNumeric(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => 'non_numeric_value',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'upperLimit', ['This value should be of type numeric.', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).']);
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenUpperLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.543',
            'notes' => 'Updated reference range notes',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'upperLimit', 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).');
        $this->assertNull($body['data']['updateReferenceRange']);
    }

    public function testPatchReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateReferenceRange(
                $id: ID!
                $hormone: String!
                $unit: String!
                $name: String!
                $lowerLimit: String!
                $upperLimit: String!
                $notes: String
            ) {
                updateReferenceRange(
                    input: {
                        id: $id
                        hormone: $hormone
                        unit: $unit
                        name: $name
                        lowerLimit: $lowerLimit
                        upperLimit: $upperLimit
                        notes: $notes
                    }
                ) {
                    referenceRange {
                        id
                        name
                        lowerLimit
                        upperLimit
                        notes
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
            'hormone' => $this->getIriFromResource(repository(HormoneEntity::class)->random()),
            'unit' => $this->getIriFromResource(repository(UnitEntity::class)->random()),
            'name' => 'Updated reference range name',
            'lowerLimit' => '1234.56',
            'upperLimit' => '9876.54',
            'notes' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'notes', 'This value should not be blank.');
        $this->assertNull($body['data']['createReferenceRange']);
    }

    public function testDeleteRemovesReferenceRange(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteReferenceRange($id: ID!) {
                deleteReferenceRange(input: { id: $id }) {
                    referenceRange {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteReferenceRange')->toArray(false);

        $this->assertNull(ReferenceRangeFactory::repository()->find($referenceRange->getId()));
        $this->assertGraphQlHasNoErrors($body);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $referenceRange = ReferenceRangeFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation DeleteReferenceRange($id: ID!) {
                deleteReferenceRange(input: { id: $id }) {
                    referenceRange {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertNull($body['data']['deleteReferenceRange']);
    }

    public function testDeleteReturns404ForAppOwnedReferenceRangeAsRegularUser(): void
    {
        $user = UserFactory::random();
        $referenceRange = ReferenceRangeFactory::random(['user' => null]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteReferenceRange($id: ID!) {
                deleteReferenceRange(input: { id: $id }) {
                    referenceRange {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "%s" not found.', $this->getIriFromResource($referenceRange)));
        $this->assertNull($body['data']['deleteReferenceRange']);
    }

    public function testDeleteReturns403ForAnonymousUser(): void
    {
        $referenceRange = ReferenceRangeFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation DeleteReferenceRange($id: ID!) {
                deleteReferenceRange(input: { id: $id }) {
                    referenceRange {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($referenceRange),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteReferenceRange')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['deleteReferenceRange']);
    }
}
