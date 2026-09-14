<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Hormone;
use App\Entity\LabResult;
use App\Entity\Measurement;
use App\Entity\ReferenceRange;
use App\Entity\Unit;
use App\Entity\User;
use App\Factory\MeasurementFactory;
use App\Tests\Story\MeasurementsStory;
use App\Tests\Support\AuthenticatedClientTrait;
use Zenstruck\Foundry\Attribute\WithStory;

use function Zenstruck\Foundry\Persistence\repository;

#[WithStory(MeasurementsStory::class)]
class MeasurementResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsOwnedMeasurementsAsRegularUser(): void
    {
        $user = repository(User::class)->first();
        $expectedTotal = MeasurementsStory::NUM_OF_LABRESULTS_PER_USER * MeasurementsStory::NUM_OF_MEASUREMENTS_PER_LABRESULT;

        $response = self::createAuthenticatedClient($user)->request('GET', '/measurements');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Measurement',
            '@id' => '/measurements',
            '@type' => 'Collection',
            'totalItems' => $expectedTotal,
        ]);
        $this->assertCount($expectedTotal, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Measurement::class);
    }

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('GET', '/measurements');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsOwnedMeasurementAsRegularUser(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);

        self::createAuthenticatedClient($user)->request('GET', "/measurements/{$measurement->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    }

    public function testGetItemReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = repository(User::class)->randomSet(2);
        $measurement = repository(Measurement::class)->random(['user' => $user2]);

        self::createAuthenticatedClient($user1)->request('GET', "/measurements/{$measurement->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $measurement = repository(Measurement::class)->random();

        self::createClient()->request('GET', "/measurements/{$measurement->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostCreatesMeasurement(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        $response = self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Measurement',
            '@type' => 'Measurement',
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]);
        $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    }
    
    public function testPostCreatesMeasurementWithReferenceRange(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();
        $referenceRange = repository(ReferenceRange::class)->random();

        $response = self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'referenceRange' => "/reference_ranges/{$referenceRange->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Measurement',
            '@type' => 'Measurement',
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'referenceRange' => "/reference_ranges/{$referenceRange->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]);
        $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    }

    public function testPostWithoutUnitCreatesMeasurementWithFallbackUnit(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $hormone = repository(Hormone::class)->random();

        $response = self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Measurement',
            '@type' => 'Measurement',
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$hormone->getDefaultUnit()->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]);
        $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    }

    public function testPostReturns401ForAnonymousUser(): void
    {
        $labResult = repository(LabResult::class)->random();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createClient()->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '543.75',
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostReturns422WhenLabResultIsNotOwned(): void
    {
        [$user1, $user2] = repository(User::class)->randomSet(2);
        $labResult = repository(LabResult::class)->random(['user' => $user2]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user1)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResult',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPostReturns422WhenLabResultIsInvalidIri(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toHex()}", // Wrong uuid format -> it should be RFC 9562/4122
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResult',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPostReturns422WhenLabResultIsMissing(): void
    {
        $user = repository(User::class)->random();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResult',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenLabResultIsBlank(): void
    {
        $user = repository(User::class)->random();
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResult',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPostReturns422WhenUnitIsInvalidIri(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toHex()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
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
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
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

    public function testPostReturns422WhenHormoneIsInvalidIri(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toHex()}",
            'value' => '1234.56',
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
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'value' => '1234.56',
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
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "",
            'value' => '1234.56',
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

    public function testPostReturns422WhenValueIsMissing(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'value',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenValueIsBlank(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'value',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPostReturns422WhenValueIsNotNumeric(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => 'non_numeric_value',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'value',
                'message' => 'This value should be of type numeric.',
            ]],
        ]);
    }

    public function testPostReturns422WhenValueIsAnInvalidNumericValue(): void
    {
        $user = repository(User::class)->random();
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '12345.678',
        ]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'value',
                'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
            ]],
        ]);
    }

    public function testPatchUpdatedMeasurement(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Measurement',
            '@type' => 'Measurement',
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]);
        $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    }
    
    public function testPatchRemovesReferenceRangeFromMeasurement(): void
    {
        $user = repository(User::class)->random();
        $referenceRange = repository(ReferenceRange::class)->random(['user' => null]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();
        
        $measurement = MeasurementFactory::createOne(['user' => $user, 'referenceRange' => $referenceRange]);

        $response = self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'referenceRange' => null,
                'value' => '1234.56',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Measurement',
            '@type' => 'Measurement',
            'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
            'unit' => "/units/{$unit->getId()->toRfc4122()}",
            'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
            'value' => '1234.56',
        ]);
        $this->assertNull($response->toArray()['referenceRange']);
        $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
        $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
        $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    }

    public function testPatchReturns401ForAnonymousUser(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createClient()->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = repository(User::class)->randomSet(2);
        $measurement = repository(Measurement::class)->random(['user' => $user2]);
        $labResult = repository(LabResult::class)->random(['user' => $user1]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user1)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testPatchReturns422WhenLabResultIsNotOwned(): void
    {
        [$user1, $user2] = repository(User::class)->randomSet(2);
        $measurement = repository(Measurement::class)->random(['user' => $user1]);
        $labResult = repository(LabResult::class)->random(['user' => $user2]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user1)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResult',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenLabResultIsInvalidIri(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toHex()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResult',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenLabResultIsBlank(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'labResult',
                'message' => 'This value should be of type LabResult.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenUnitIsInvalidIri(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toHex()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
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
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '1234.56',
            ],
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

    public function testPatchReturns422WhenHormoneIsInvalidIri(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toHex()}",
                'value' => '1234.56',
            ],
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
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "",
                'value' => '1234.56',
            ],
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

    public function testPatchReturns422WhenValueIsBlank(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'value',
                'message' => 'This value should not be blank.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenValueIsNotNumeric(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => 'non_numeric_value',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'value',
                'message' => 'This value should be of type numeric.',
            ]],
        ]);
    }

    public function testPatchReturns422WhenValueIsAnInvalidNumericValue(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);
        $labResult = repository(LabResult::class)->random(['user' => $user]);
        $unit = repository(Unit::class)->random();
        $hormone = repository(Hormone::class)->random();

        self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
                'unit' => "/units/{$unit->getId()->toRfc4122()}",
                'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
                'value' => '12345.678',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'violations' => [[
                'propertyPath' => 'value',
                'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
            ]],
        ]);
    }

    public function testDeleteRemovesMeasurement(): void
    {
        $user = repository(User::class)->random();
        $measurement = repository(Measurement::class)->random(['user' => $user]);

        self::createAuthenticatedClient($user)->request('DELETE', "/measurements/{$measurement->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = repository(User::class)->randomSet(2);
        $measurement = repository(Measurement::class)->random(['user' => $user2]);

        self::createAuthenticatedClient($user1)->request('DELETE', "/measurements/{$measurement->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testDeleteReturns401ForAnonymousUser(): void
    {
        $measurement = repository(Measurement::class)->random();

        self::createClient()->request('DELETE', "/measurements/{$measurement->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }
}
