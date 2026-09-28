<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Hormone;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;

use function Zenstruck\Foundry\Persistence\repository;

class HormoneResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsHormones(): void
    {
        $user = UserFactory::createOne();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Hormones {
                hormones {
                    id
                    name
                    commonName
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Hormones')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(8, $body['data']['hormones']);

        foreach ($body['data']['hormones'] as $hormone) {
            $this->assertMatchesIri($hormone['id'], Hormone::class);
            $this->assertArrayHasKey('name', $hormone);
            $this->assertArrayHasKey('commonName', $hormone);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query Hormones {
                hormones {
                    id
                    name
                    commonName
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Hormones')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['hormones']);
    }

    public function testGetItemReturnsUnit(): void
    {
        $user = UserFactory::createOne();
        $hormone = repository(Hormone::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Hormone($id: ID!) {
                hormone(id: $id) {
                    id
                    name
                    commonName
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($hormone),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Hormone')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($hormone), $body['data']['hormone']['id']);
        $this->assertSame($hormone->getName(), $body['data']['hormone']['name']);
        $this->assertSame($hormone->getCommonName(), $body['data']['hormone']['commonName']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $hormone = repository(Hormone::class)->random();

        $client = self::createClient();

        $query = <<<'GQL'
            query Hormone($id: ID!) {
                hormone(id: $id) {
                    id
                    name
                    commonName
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($hormone),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Hormone')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['hormone']);
    }

    public function testPostReturnsError(): void
    {
        $user = UserFactory::createOne();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateHormone($name: String!, $commonName: String!) {
                createHormone(input: { name: $name, commonName: $commonName }) {
                    hormone {
                        id
                        name
                        commonName
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Epinephrine',
            'commonName' => 'Adrenaline',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateHormone')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }

    public function testPatchReturnsError(): void
    {
        $user = UserFactory::createOne();
        $hormone = repository(Hormone::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateHormone($id: ID!, $name: String!, $commonName: String!) {
                updateHormone(input: { id: $id, name: $name, commonName: $commonName }) {
                    hormone {
                        id
                        name
                        commonName
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($hormone),
            'name' => 'Epinephrine',
            'commonName' => 'Adrenaline',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateHormone')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }

    public function testDeleteReturnsError(): void
    {
        $user = UserFactory::createOne();
        $hormone = repository(Hormone::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteHormone($id: ID!) {
                deleteHormone(input: { id: $id }) {
                    hormone {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($hormone),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteHormone')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }
}
