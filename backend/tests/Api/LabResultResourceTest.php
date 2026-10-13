<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Api\Resource\LabResultResource;
use App\Factory\LabResultFactory;
use App\Factory\PostFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use DateTimeInterface;
use Override;

use function Zenstruck\Foundry\faker;

class LabResultResourceTest extends ApiTestCase
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
            LabResultFactory::createMany(10, ['user' => $user]);
        }
    }

    public function testGetCollectionReturnsOwnedLabResultsAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query LabResults {
                labResults {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'LabResults')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(10, $body['data']['labResults']);

        foreach ($body['data']['labResults'] as $labResults) {
            $this->assertMatchesIri($labResults['id'], LabResultResource::class);
            $this->assertArrayHasKey('notes', $labResults);
            $this->assertArrayHasKey('publishedAt', $labResults);
            $this->assertMatchesDate($labResults['drawnAt']);
            $this->assertMatchesDate($labResults['createdAt']);
            $this->assertMatchesDate($labResults['updatedAt']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query LabResults {
                labResults {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'LabResults')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['labResults']);
    }

    public function testGetItemReturnsOwnedLabResultsAsRegularUser(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query LabResult($id: ID!) {
                labResult(id: $id) {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'LabResult')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame(sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()), $body['data']['labResult']['id']);
        $this->assertSame($labResult->getNotes(), $body['data']['labResult']['notes']);
        $this->assertSame($labResult->getPublishedAt(), $body['data']['labResult']['publishedAt']);
        $this->assertSame($labResult->getDrawnAt()->format(DateTimeInterface::ATOM), $body['data']['labResult']['drawnAt']);
        $this->assertSame($labResult->getCreatedAt()->format(DateTimeInterface::ATOM), $body['data']['labResult']['createdAt']);
        $this->assertSame($labResult->getUpdatedAt()->format(DateTimeInterface::ATOM), $body['data']['labResult']['updatedAt']);
    }

    public function testGetItemReturnsNullForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $labResult = LabResultFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            query LabResult($id: ID!) {
                labResult(id: $id) {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'LabResult')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull($body['data']['labResult']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $labResult = LabResultFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            query LabResult($id: ID!) {
                labResult(id: $id) {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'LabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['labResult']);
    }

    public function testPostCreatesLabResult(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean, $drawnAt: String!) {
                createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'notes' => 'New lab notes',
            'published' => false,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createLabResult']['labResult']['id'], LabResultResource::class);
        $this->assertSame('New lab notes', $body['data']['createLabResult']['labResult']['notes']);
        $this->assertNull($body['data']['createLabResult']['labResult']['publishedAt']);
        $this->assertSame($drawnAt, $body['data']['createLabResult']['labResult']['drawnAt']);
        $this->assertMatchesDate($body['data']['createLabResult']['labResult']['createdAt']);
        $this->assertMatchesDate($body['data']['createLabResult']['labResult']['updatedAt']);
    }

    public function testPostCreatesPublishedLabResult(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean, $drawnAt: String!) {
                createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'notes' => 'New lab notes',
            'published' => true,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createLabResult']['labResult']['id'], LabResultResource::class);
        $this->assertSame('New lab notes', $body['data']['createLabResult']['labResult']['notes']);
        $this->assertMatchesDate($body['data']['createLabResult']['labResult']['publishedAt']);
        $this->assertSame($drawnAt, $body['data']['createLabResult']['labResult']['drawnAt']);
        $this->assertMatchesDate($body['data']['createLabResult']['labResult']['createdAt']);
        $this->assertMatchesDate($body['data']['createLabResult']['labResult']['updatedAt']);
    }

    public function testPostReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean, $drawnAt: String!) {
                createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'notes' => 'New lab notes',
            'published' => false,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['createLabResult']);
    }

    public function testPostReturns422WhenDrawnAtIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean) {
                createLabResult(input: { notes: $notes, published: $published }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'notes' => 'New lab notes',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'drawnAt', 'This value should not be blank.');
        $this->assertNull($body['data']['createLabResult']);
    }

    public function testPostReturns422WhenDrawnAtIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean, $drawnAt: String!) {
                createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'notes' => 'New lab notes',
            'published' => false,
            'drawnAt' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'drawnAt', 'This value should not be blank.');
        $this->assertNull($body['data']['createLabResult']);
    }

    public function testPostReturns422WhenDrawnAtIsNotDateValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean, $drawnAt: String!) {
                createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'notes' => 'New lab notes',
            'published' => false,
            'drawnAt' => 'invalid_date_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'drawnAt', 'Invalid value. Enter a date in the ATOM (Y-m-d\\TH:i:sP) format.');
        $this->assertNull($body['data']['createLabResult']);
    }

    public function testPostReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean, $drawnAt: String!) {
                createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'notes' => '',
            'published' => false,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'notes', 'This value should not be blank.');
        $this->assertNull($body['data']['createLabResult']);
    }

    public function testPostReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateLabResult($notes: String, $published: Boolean, $drawnAt: String!) {
                createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'notes' => 'New lab notes',
            'published' => 'true',
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateLabResult')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$published" got invalid value "true"; Boolean cannot represent a non boolean value: "true"');
    }

    public function testPatchUpdatesLabResult(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => null,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updateLabResult']['labResult']['id'], LabResultResource::class);
        $this->assertSame('Updated lab notes', $body['data']['updateLabResult']['labResult']['notes']);
        $this->assertNull($body['data']['updateLabResult']['labResult']['publishedAt']);
        $this->assertSame($drawnAt, $body['data']['updateLabResult']['labResult']['drawnAt']);
        $this->assertMatchesDate($body['data']['updateLabResult']['labResult']['createdAt']);
        $this->assertMatchesDate($body['data']['updateLabResult']['labResult']['updatedAt']);
    }

    public function testPatchUpdatesLabResultToPublic(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => true,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updateLabResult']['labResult']['id'], LabResultResource::class);
        $this->assertSame('Updated lab notes', $body['data']['updateLabResult']['labResult']['notes']);
        $this->assertMatchesDate($body['data']['updateLabResult']['labResult']['publishedAt']);
        $this->assertSame($drawnAt, $body['data']['updateLabResult']['labResult']['drawnAt']);
        $this->assertMatchesDate($body['data']['updateLabResult']['labResult']['createdAt']);
        $this->assertMatchesDate($body['data']['updateLabResult']['labResult']['updatedAt']);
    }

    public function testPatchUpdatesLabResultToPrivate(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::new()->published()->create(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => false,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updateLabResult']['labResult']['id'], LabResultResource::class);
        $this->assertSame('Updated lab notes', $body['data']['updateLabResult']['labResult']['notes']);
        $this->assertNull($body['data']['updateLabResult']['labResult']['publishedAt']);
        $this->assertSame($drawnAt, $body['data']['updateLabResult']['labResult']['drawnAt']);
        $this->assertMatchesDate($body['data']['updateLabResult']['labResult']['createdAt']);
        $this->assertMatchesDate($body['data']['updateLabResult']['labResult']['updatedAt']);
    }

    public function testPatchReturns403ForAnonymousUser(): void
    {
        $labResult = LabResultFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => null,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['updateLabResult']);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $labResult = LabResultFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => null,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/lab_results/%s" not found.', $labResult->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/lab_results/%s" not found.', $labResult->getId()->toRfc4122()));
        $this->assertNull($body['data']['updateLabResult']);
    }

    public function testPatchReturns422WhenDrawnAtIsBlank(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => null,
            'drawnAt' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'drawnAt', 'This value should not be blank.');
        $this->assertNull($body['data']['createLabResult']);
    }

    public function testPatchReturns422WhenDrawnAtIsNotDateValue(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => null,
            'drawnAt' => 'invalid_date_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'drawnAt', 'Invalid value. Enter a date in the ATOM (Y-m-d\\TH:i:sP) format.');
        $this->assertNull($body['data']['createLabResult']);
    }

    public function testPatchReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => '',
            'published' => null,
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'notes', 'This value should not be blank.');
        $this->assertNull($body['data']['updateLabResult']);
    }

    public function testPatchReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String!) {
                updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $drawnAt = faker()->dateTimeBetween('-1 month', '-1 day')->format(DateTimeInterface::ATOM);

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
            'notes' => 'Updated lab notes',
            'published' => 'true',
            'drawnAt' => $drawnAt,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateLabResult')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$published" got invalid value "true"; Boolean cannot represent a non boolean value: "true"');
    }

    public function testDeleteRemovesLabResult(): void
    {
        $user = UserFactory::random();
        $labResult = LabResultFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteLabResult($id: ID!) {
                deleteLabResult(input: { id: $id }) {
                    labResult {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteLabResult')->toArray(false);

        $this->assertNull(PostFactory::repository()->find($labResult->getId()));
        $this->assertGraphQlHasNoErrors($body);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $labResult = LabResultFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation DeleteLabResult($id: ID!) {
                deleteLabResult(input: { id: $id }) {
                    labResult {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/lab_results/%s" not found.', $labResult->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/lab_results/%s" not found.', $labResult->getId()->toRfc4122()));
        $this->assertNull($body['data']['deleteLabResult']);
    }

    public function testDeleteReturns403ForAnonymousUser(): void
    {
        $labResult = LabResultFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation DeleteLabResult($id: ID!) {
                deleteLabResult(input: { id: $id }) {
                    labResult {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/lab_results/%s', $labResult->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteLabResult')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['updateLabResult']);
    }
}
