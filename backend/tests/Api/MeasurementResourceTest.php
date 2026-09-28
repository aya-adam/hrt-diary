<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Hormone;
use App\Entity\LabResult;
use App\Entity\Measurement;
use App\Entity\ReferenceRange;
use App\Entity\Unit;
use App\Entity\User;
use App\Factory\LabResultFactory;
use App\Factory\MeasurementFactory;
use App\Factory\UserFactory;
use App\Tests\Story\MeasurementsStory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use Override;

use function Zenstruck\Foundry\Persistence\repository;

class MeasurementResourceTest extends ApiTestCase
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
            LabResultFactory::createMany(3, ['user' => $user]);
        }

        foreach (LabResultFactory::all() as $labResult) {
            MeasurementFactory::createMany(5, ['user' => $labResult->getUser(), 'labResult' => $labResult]);
        }
    }

    public function testGetCollectionReturnsOwnedMeasurementsAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Measurements {
                measurements {
                    id
                    value
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Measurements')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(15, $body['data']['measurements']);

        foreach ($body['data']['measurements'] as $measurement) {
            $this->assertMatchesIri($measurement['id'], Measurement::class);
            $this->assertIsNumeric($measurement['value']);
            $this->assertMatchesDate($measurement['drawnAt']);
            $this->assertMatchesDate($measurement['createdAt']);
            $this->assertMatchesDate($measurement['updatedAt']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query Measurements {
                measurements {
                    id
                    value
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Measurements')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['measurements']);
    }

    public function testGetItemReturnsOwnedMeasurementAsRegularUser(): void
    {
        $user = UserFactory::random();
        $measurement = MeasurementFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Measurement($id: ID!) {
                measurement(id: $id) {
                    id
                    value
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($measurement),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Measurement')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($measurement), $body['data']['measurement']['id']);
        $this->assertSame($measurement->getValue(), $body['data']['measurement']['value']);
        $this->assertSame($measurement->getDrawnAt()->format('c'), $body['data']['measurement']['drawnAt']);
        $this->assertSame($measurement->getCreatedAt()->format('c'), $body['data']['measurement']['createdAt']);
        $this->assertSame($measurement->getUpdatedAt()->format('c'), $body['data']['measurement']['updatedAt']);
    }

    public function testGetItemReturnsNullForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $measurement = MeasurementFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            query Measurement($id: ID!) {
                measurement(id: $id) {
                    id
                    value
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($measurement),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Measurement')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull($body['data']['measurement']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $measurement = MeasurementFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            query Measurement($id: ID!) {
                measurement(id: $id) {
                    id
                    value
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($measurement),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Measurement')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['measurement']);
    }

    // public function testPostCreatesMeasurement(): void
    // {
    //     $user = UserFactory::random();

    //     $labResult = LabResultFactory::random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     $client = self::createAuthenticatedClient($user);

    //     $query = <<<'GQL'
    //         mutation CreateMeasurement($unit: String!, $hormone: String!, $value: String!, $labResultId: ID!) {
    //             createMeasurement(
    //                 input: {
    //                     unit: $unit,
    //                     hormone: $hormone,
    //                     value: $value,
    //                     labResult: { connect: $labResultId }
    //             }) {
    //                 measurement {
    //                     id
    //                     value
    //                     drawnAt
    //                     createdAt
    //                     updatedAt
    //                     unit {
    //                         id
    //                     }
    //                     hormone {
    //                         id
    //                     }
    //                     referenceRange {
    //                         id
    //                     }
    //                     labResult {
    //                         id
    //                         drawnAt
    //                     }
    //                 }
    //             }
    //         }
    //     GQL;

    //     $variables = [
    //         'labResultId' => $this->getIriFromResource($labResult),
    //         'unit' => $this->getIriFromResource($unit),
    //         'hormone' => $this->getIriFromResource($hormone),
    //         'value' => '123.45',
    //     ];

    //     $body = $this->executeGraphQl($client, $query, $variables, 'CreateMeasurement')->toArray(false);

    //     dd($body);
    // }

    // public function testPostCreatesMeasurementWithReferenceRange(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();
    //     $referenceRange = repository(ReferenceRange::class)->random();

    //     $response = self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'referenceRange' => "/reference_ranges/{$referenceRange->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(201);
    //     $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/Measurement',
    //         '@type' => 'Measurement',
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'referenceRange' => "/reference_ranges/{$referenceRange->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]);
    //     $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
    //     $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
    //     $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    // }

    // public function testPostWithoutUnitCreatesMeasurementWithFallbackUnit(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $hormone = repository(Hormone::class)->random();

    //     $response = self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(201);
    //     $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/Measurement',
    //         '@type' => 'Measurement',
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$hormone->getDefaultUnit()->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]);
    //     $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
    //     $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
    //     $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    // }

    // public function testPostReturns401ForAnonymousUser(): void
    // {
    //     $labResult = repository(LabResult::class)->random();
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createClient()->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '543.75',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(401);
    //     $this->assertResponseHeaderSame('content-type', 'application/json');
    //     $this->assertJsonEquals([
    //         'code' => 401,
    //         'message' => 'JWT Token not found',
    //     ]);
    // }

    // public function testPostReturns422WhenLabResultIsNotOwned(): void
    // {
    //     [$user1, $user2] = repository(User::class)->randomSet(2);
    //     $labResult = repository(LabResult::class)->random(['user' => $user2]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user1)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'labResult',
    //             'message' => 'This value should be of type LabResult.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenLabResultIsInvalidIri(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toHex()}", // Wrong uuid format -> it should be RFC 9562/4122
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'labResult',
    //             'message' => 'This value should be of type LabResult.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenLabResultIsMissing(): void
    // {
    //     $user = repository(User::class)->random();
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'labResult',
    //             'message' => 'This value should not be blank.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenLabResultIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'labResult',
    //             'message' => 'This value should be of type LabResult.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenUnitIsInvalidIri(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toHex()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'unit',
    //             'message' => 'This value should be of type Unit.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenUnitIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'unit',
    //             'message' => 'This value should be of type Unit.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenHormoneIsInvalidIri(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toHex()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'hormone',
    //             'message' => 'This value should be of type Hormone.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenHormoneIsMissing(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'hormone',
    //             'message' => 'This value should not be blank.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenHormoneIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "",
    //         'value' => '1234.56',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'hormone',
    //             'message' => 'This value should be of type Hormone.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenValueIsMissing(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'value',
    //             'message' => 'This value should not be blank.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenValueIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'value',
    //             'message' => 'This value should not be blank.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenValueIsNotNumeric(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => 'non_numeric_value',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'value',
    //             'message' => 'This value should be of type numeric.',
    //         ]],
    //     ]);
    // }

    // public function testPostReturns422WhenValueIsAnInvalidNumericValue(): void
    // {
    //     $user = repository(User::class)->random();
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('POST', '/measurements', ['json' => [
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '12345.678',
    //     ]]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'value',
    //             'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
    //         ]],
    //     ]);
    // }

    // public function testPatchUpdatedMeasurement(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     $response = self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(200);
    //     $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/Measurement',
    //         '@type' => 'Measurement',
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]);
    //     $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
    //     $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
    //     $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    // }

    // public function testPatchRemovesReferenceRangeFromMeasurement(): void
    // {
    //     $user = repository(User::class)->random();
    //     $referenceRange = repository(ReferenceRange::class)->random(['user' => null]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     $measurement = MeasurementFactory::createOne(['user' => $user, 'referenceRange' => $referenceRange]);

    //     $response = self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'referenceRange' => null,
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(200);
    //     $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/Measurement',
    //         '@type' => 'Measurement',
    //         'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //         'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //         'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //         'value' => '1234.56',
    //     ]);
    //     $this->assertNull($response->toArray()['referenceRange']);
    //     $this->assertMatchesRegularExpression('/^\/measurements\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['@id']);
    //     $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $response->toArray()['id']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['drawnAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['createdAt']);
    //     $this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/', $response->toArray()['updatedAt']);
    //     $this->assertMatchesResourceItemJsonSchema(Measurement::class);
    // }

    // public function testPatchReturns401ForAnonymousUser(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createClient()->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(401);
    //     $this->assertResponseHeaderSame('content-type', 'application/json');
    //     $this->assertJsonEquals([
    //         'code' => 401,
    //         'message' => 'JWT Token not found',
    //     ]);
    // }

    // public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    // {
    //     [$user1, $user2] = repository(User::class)->randomSet(2);
    //     $measurement = repository(Measurement::class)->random(['user' => $user2]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user1]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user1)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(404);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    // }

    // public function testPatchReturns422WhenLabResultIsNotOwned(): void
    // {
    //     [$user1, $user2] = repository(User::class)->randomSet(2);
    //     $measurement = repository(Measurement::class)->random(['user' => $user1]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user2]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user1)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'labResult',
    //             'message' => 'This value should be of type LabResult.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenLabResultIsInvalidIri(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toHex()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'labResult',
    //             'message' => 'This value should be of type LabResult.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenLabResultIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'labResult',
    //             'message' => 'This value should be of type LabResult.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenUnitIsInvalidIri(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toHex()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'unit',
    //             'message' => 'This value should be of type Unit.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenUnitIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'unit',
    //             'message' => 'This value should be of type Unit.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenHormoneIsInvalidIri(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toHex()}",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'hormone',
    //             'message' => 'This value should be of type Hormone.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenHormoneIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "",
    //             'value' => '1234.56',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'hormone',
    //             'message' => 'This value should be of type Hormone.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenValueIsBlank(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'value',
    //             'message' => 'This value should not be blank.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenValueIsNotNumeric(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => 'non_numeric_value',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'value',
    //             'message' => 'This value should be of type numeric.',
    //         ]],
    //     ]);
    // }

    // public function testPatchReturns422WhenValueIsAnInvalidNumericValue(): void
    // {
    //     $user = repository(User::class)->random();
    //     $measurement = repository(Measurement::class)->random(['user' => $user]);
    //     $labResult = repository(LabResult::class)->random(['user' => $user]);
    //     $unit = repository(Unit::class)->random();
    //     $hormone = repository(Hormone::class)->random();

    //     self::createAuthenticatedClient($user)->request('PATCH', "/measurements/{$measurement->getId()->toRfc4122()}", [
    //         'headers' => ['content-type' => 'application/merge-patch+json'],
    //         'json' => [
    //             'labResult' => "/lab_results/{$labResult->getId()->toRfc4122()}",
    //             'unit' => "/units/{$unit->getId()->toRfc4122()}",
    //             'hormone' => "/hormones/{$hormone->getId()->toRfc4122()}",
    //             'value' => '12345.678',
    //         ],
    //     ]);

    //     $this->assertResponseStatusCodeSame(422);
    //     $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    //     $this->assertJsonContains([
    //         '@context' => '/contexts/ConstraintViolation',
    //         '@type' => 'ConstraintViolation',
    //         'violations' => [[
    //             'propertyPath' => 'value',
    //             'message' => 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).',
    //         ]],
    //     ]);
    // }

    public function testDeleteRemovesMeasurement(): void
    {
        $user = UserFactory::random();
        $measurement = MeasurementFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteMeasurement($id: ID!) {
                deleteMeasurement(input: { id: $id }) {
                    measurement {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($measurement),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteMeasurement')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull(MeasurementFactory::repository()->find($measurement->getId()));
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = repository(User::class)->randomSet(2);
        $measurement = MeasurementFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation DeleteMeasurement($id: ID!) {
                deleteMeasurement(input: { id: $id }) {
                    measurement {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($measurement),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteMeasurement')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "%s" not found.', $this->getIriFromResource($measurement)));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "%s" not found.', $this->getIriFromResource($measurement)));
        $this->assertNull($body['data']['deleteMeasurement']);
    }

    public function testDeleteReturns403ForAnonymousUser(): void
    {
        $measurement = MeasurementFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation DeleteMeasurement($id: ID!) {
                deleteMeasurement(input: { id: $id }) {
                    measurement {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($measurement),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteMeasurement')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['deleteMeasurement']);
    }
}
