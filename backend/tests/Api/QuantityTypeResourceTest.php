<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Api\Resource\QuantityTypeResource;
use App\Entity\QuantityTypeEntity;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use Override;

use function Zenstruck\Foundry\Persistence\repository;

class QuantityTypeResourceTest extends ApiTestCase
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
    protected function setUp(): void
    {
        parent::setUp();

        UserFactory::createMany(10);
    }

    public function testGetCollectionReturnsQuantityTypes(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query QuantityTypes {
                quantityTypes {
                    id
                    label
                    slug
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'QuantityTypes')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(3, $body['data']['quantityTypes']);

        foreach ($body['data']['quantityTypes'] as $unit) {
            $this->assertMatchesIri($unit['id'], QuantityTypeResource::class);
            $this->assertIsString($unit['label']);
            $this->assertIsString($unit['slug']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query QuantityTypes {
                quantityTypes {
                    id
                    label
                    slug
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'QuantityTypes')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['quantityTypes']);
    }

    public function testGetItemReturnsQuantityType(): void
    {
        $user = UserFactory::random();
        $quantityType = repository(QuantityTypeEntity::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query QuantityType($id: ID!) {
                quantityType(id: $id) {
                    id
                    label
                    slug
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/quantity_types/%s', $quantityType->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'QuantityType')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame(sprintf('/quantity_types/%s', $quantityType->getId()->toRfc4122()), $body['data']['quantityType']['id']);
        $this->assertSame($quantityType->getLabel(), $body['data']['quantityType']['label']);
        $this->assertSame($quantityType->getSlug(), $body['data']['quantityType']['slug']);
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $quantityType = repository(QuantityTypeEntity::class)->random();

        $client = self::createClient();

        $query = <<<'GQL'
            query QuantityType($id: ID!) {
                quantityType(id: $id) {
                    id
                    label
                    slug
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/quantity_types/%s', $quantityType->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'QuantityType')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['quantityType']);
    }

    public function testPostReturns405(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateQuantityType($label: String!) {
                createQuantityType(input: { label: $label }) {
                    quantityType {
                        id
                        label
                        slug
                    }
                }
            }
        GQL;

        $variables = [
            'label' => 'New quantity type label',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateQuantityType')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }

    public function testPatchReturns405(): void
    {
        $user = UserFactory::random();
        $quantityType = repository(QuantityTypeEntity::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateQuantityType($id: ID!, $label: String!) {
                updateQuantityType(input: { id: $id, label: $label }) {
                    quantityType {
                        id
                        label
                        slug
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/quantity_types/%s', $quantityType->getId()->toRfc4122()),
            'label' => 'Updated quantity type label',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateQuantityType')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }

    public function testDeleteReturns405(): void
    {
        $user = UserFactory::random();
        $quantityType = repository(QuantityTypeEntity::class)->random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteQuantityType($id: ID!) {
                deleteQuantityType(input: { id: $id }) {
                    quantityType {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/quantity_types/%s', $quantityType->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteQuantityType')->toArray(false);

        $this->assertGraphQlHasErrors($body);
        $this->assertNull($body['data']);
    }
}
