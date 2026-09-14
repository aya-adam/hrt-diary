<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Hormone;
use App\Entity\ReferenceRange;
use App\Entity\Unit;
use App\Entity\User;
use App\Factory\ReferenceRangeFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\repository;

class ReferenceRangeResourceTest extends ApiTestCase
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

    /**
     * Public means, that the object is available to all authenticated users, not anonym users.
     */
    public function testGetCollectionReturnsOwnedAndPublicReferenceRangesAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        ReferenceRangeFactory::createMany(4, ['user' => $user]);

        $response = self::createAuthenticatedClient($user)->request('GET', '/reference_ranges');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ReferenceRange',
            '@id' => '/reference_ranges',
            '@type' => 'Collection',
            'totalItems' => 6,
        ]);
        $this->assertCount(6, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(ReferenceRange::class);
    }

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('GET', '/reference_ranges');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsOwnedReferenceRangeAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('GET', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(ReferenceRange::class);
    }

    /**
     * Public means, that the object is available to all authenticated users, not anonym users.
     */
    public function testGetItemReturnsPublicReferenceRangeAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::find(['user' => null]);

        self::createAuthenticatedClient($user)->request('GET', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(ReferenceRange::class);
    }

    public function testGetItemReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::createMany(2);
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user2]);

        self::createAuthenticatedClient($user1)->request('GET', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $referenceRange = ReferenceRangeFactory::find(['user' => null]);

        self::createClient()->request('GET', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostCreatesReferenceRange(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        $response = self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ReferenceRange',
            '@type' => 'ReferenceRange',
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]);
        $this->assertMatchesRegularExpression('/^\/reference_ranges\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(ReferenceRange::class);
    }

    public function testPostWithoutUnitCreatesReferenceRangeWithFallbackUnit(): void
    {
        $user = UserFactory::createOne();
        $hormone = repository(Hormone::class)->random();

        $response = self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ReferenceRange',
            '@type' => 'ReferenceRange',
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$hormone->getDefaultUnit()->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]);
        $this->assertMatchesRegularExpression('/^\/reference_ranges\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(ReferenceRange::class);
    }

    public function testPostReturns401ForAnonymousUser(): void
    {
        $hormone = repository(Hormone::class)->random();

        self::createClient()->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostReturns422WhenHormoneIsInvalidIri(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toHex()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'hormone',
                'message' => 'This value should be of type Hormone.',
            ]],
        ]);
    }

    public function testPostReturns422WhenHormoneIsMissing(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'hormone',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenHormoneIsBlank(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => '',
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'hormone',
                'message' => 'This value should be of type Hormone.',
            ]],
        ]);
    }

    public function testPostReturns422WhenUnitIsInvalidIri(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toHex()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'unit',
                'message' => 'This value should be of type Unit.',
            ]],
        ]);
    }

    public function testPostReturns422WhenUnitIsBlank(): void
    {
        $user = UserFactory::createOne();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => '',
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'unit',
                'message' => 'This value should be of type Unit.',
            ]],
        ]);
    }

    public function testPostReturns422WhenNameIsMissing(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => '',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => faker()->text(1000),
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'name',
                'message' => 'This value is too long. It should have 255 characters or less.',
            ]],
        ]);
    }

    public function testPostReturns422WhenLowerLimitIsMissing(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'lowerLimit',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenLowerLimitIsBlank(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'lowerLimit',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenLowerLimitIsNotNumeric(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => 'non_numeric_value',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'lowerLimit',
                'message' => 'This value should be of type numeric.',
            ]],
        ]);
    }

    public function testPostReturns422WhenLowerLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '12345.678',
            'upperLimit' => '4567.89',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'lowerLimit',
                'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
            ]],
        ]);
    }

    public function testPostReturns422WhenUpperLimitIsMissing(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'upperLimit',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenUpperLimitIsBlank(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'upperLimit',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenUpperLimitIsNotNumeric(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => 'non_numeric_value',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'upperLimit',
                'message' => 'This value should be of type numeric.',
            ]],
        ]);
    }

    public function testPostReturns422WhenUpperLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '45678.912',
            'notes' => 'Test reference range notes',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'upperLimit',
                'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
            ]],
        ]);
    }

    public function testPostReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/reference_ranges', ['json' => [
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My test reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => '',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'notes',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchUpdatedReferenceRange(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ReferenceRange',
            '@type' => 'ReferenceRange',
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'My updated reference range',
            'lowerLimit' => '1234.56',
            'upperLimit' => '4567.89',
            'notes' => 'My updated reference range notes',
        ]);
        $this->assertMatchesRegularExpression('/^\/reference_ranges\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(ReferenceRange::class);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::createMany(2);
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user2]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user1)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    /**
     * Public means, that the object is available to all authenticated users, not anonym users.
     */
    public function testPatchReturns403ForPublicReferenceRangeAsRegularUser(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = repository(ReferenceRange::class)->random(['user' => null]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testPatchReturns401ForAnonymousUser(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createClient()->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPatchReturns422WhenHormoneIsInvalidIri(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toHex()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'hormone',
                'message' => 'This value should be of type Hormone.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenHormoneIsBlank(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => '',
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'hormone',
                'message' => 'This value should be of type Hormone.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenUnitIsInvalidIri(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toHex()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'unit',
                'message' => 'This value should be of type Unit.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenUnitIsBlank(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => '',
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'unit',
                'message' => 'This value should be of type Unit.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => '',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => faker()->text(1000),
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'name',
                'message' => 'This value is too long. It should have 255 characters or less.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenLowerLimitIsBlank(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'lowerLimit',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenLowerLimitIsNotNumeric(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => 'non_numeric_value',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'lowerLimit',
                'message' => 'This value should be of type numeric.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenLowerLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '12345.678',
                'upperLimit' => '4567.89',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'lowerLimit',
                'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
            ]],
        ]);
    }

    public function testPatchReturns422WhenUpperLimitIsBlank(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'upperLimit',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenUpperLimitIsNotNumeric(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => 'non_numeric_value',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'upperLimit',
                'message' => 'This value should be of type numeric.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenUpperLimitIsAnInvalidNumericValue(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '45678.912',
                'notes' => 'My updated reference range notes',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'upperLimit',
                'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
            ]],
        ]);
    }

    public function testPatchReturns422WhenNotesIsBlank(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        $hormone = repository(Hormone::class)->random();
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'My updated reference range',
                'lowerLimit' => '1234.56',
                'upperLimit' => '4567.89',
                'notes' => '',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'notes',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testDeleteRemovesReferenceRange(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        self::createAuthenticatedClient($user)->request('DELETE', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::createMany(2);
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user2]);

        self::createAuthenticatedClient($user1)->request('DELETE', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    /**
     * Public means, that the object is available to all authenticated users, not anonym users.
     */
    public function testDeleteReturns403ForPublicReferenceRangeAsRegularUser(): void
    {
        $referenceRange = repository(ReferenceRange::class)->random(['user' => null]);

        self::createAuthenticatedClient(UserFactory::createOne())->request('DELETE', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testDeleteReturns401ForAnonymousUser(): void
    {
        $user = UserFactory::createOne();
        $referenceRange = ReferenceRangeFactory::createOne(['user' => $user]);

        self::createClient()->request('DELETE', "/reference_ranges/{$referenceRange->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }
}
