<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Unit;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;

use function Zenstruck\Foundry\Persistence\repository;

class UnitResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsUnits(): void
    {
        $user = UserFactory::createOne();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Units {
                units {
                    id
                    symbol
                    label
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Units')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(19, $body['data']['units']);

        foreach ($body['data']['units'] as $unit) {
            $this->assertMatchesIri($unit['id'], Unit::class);
            $this->assertIsString($unit['symbol']);
            $this->assertIsString($unit['label']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query Units {
                units {
                    id
                    symbol
                    label
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Units')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['units']);
    }

    public function testGetItemReturnsUnit(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Unit($id: ID!) {
                unit(id: $id) {
                    id
                    symbol
                    label
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($unit),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Unit')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($unit), $body['data']['unit']['id']);
        $this->assertSame($unit->getSymbol(), $body['data']['unit']['symbol']);
        $this->assertSame($unit->getLabel(), $body['data']['unit']['label']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $unit = repository(Unit::class)->random();

        $client = self::createClient();

        $query = <<<'GQL'
            query Unit($id: ID!) {
                unit(id: $id) {
                    id
                    symbol
                    label
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($unit),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Unit')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['unit']);
    }

    public function testPostReturnsError(): void
    {
        $user = UserFactory::createOne();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateUnit($symbol: String!, $label: String!) {
                createUnit(input: { symbol: $symbol, label: $label }) {
                    unit {
                        id
                        symbol
                        label
                    }
                }
            }
        GQL;

        $variables = [
            'symbol' => 'km/h',
            'label' => 'Kilometers per hour',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateUnit')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }

    public function testPatchReturnsError(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateUnit($id: ID!, $symbol: String!, $label: String!) {
                updateUnit(input: { id: $id, symbol: $symbol, label: $label }) {
                    unit {
                        id
                        symbol
                        label
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($unit),
            'symbol' => 'km/h',
            'label' => 'Kilometers per hour',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateUnit')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }

    public function testDeleteReturnsError(): void
    {
        $user = UserFactory::createOne();
        $unit = repository(Unit::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteUnit($id: ID!) {
                deleteUnit(input: { id: $id }) {
                    unit {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($unit),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteUnit')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }
}
