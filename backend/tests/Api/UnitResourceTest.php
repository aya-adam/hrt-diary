<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\QuantityType;
use App\Entity\Unit;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;

use function Zenstruck\Foundry\Persistence\repository;

class UnitResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsUnits(): void
    {
        $user = UserFactory::createOne();

        $response = self::createAuthenticatedClient($user)->request('GET', '/units');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(['totalItems' => 19]);
        $this->assertCount(19, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Unit::class);
    }

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('GET', '/units');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsUnit(): void
    {
        $unitRespository = repository(Unit::class);
        $unit = $unitRespository->first();

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('GET', "/units/{$unit->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(Unit::class);
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $unitRespository = repository(Unit::class);
        $unit = $unitRespository->first();

        self::createClient()->request('GET', "/units/{$unit->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostReturns405(): void
    {
        $quantityTypeRespository = repository(QuantityType::class);
        $quantityType = $quantityTypeRespository->first();

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/units', ['json' => [
            'quantityType' => "/quantity_types/{$quantityType->getId()->toRfc4122()}",
            'symbol' => 'km/h',
            'label' => 'Kilometers per hour',
        ]]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testPatchReturns405(): void
    {
        $quantityTypeRespository = repository(QuantityType::class);
        $quantityType = $quantityTypeRespository->first();

        $unitRespository = repository(Unit::class);
        $unit = $unitRespository->first();

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/units/{$unit->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'quantityType' => "/quantity_types/{$quantityType->getId()->toRfc4122()}",
                'symbol' => 'km/h',
                'label' => 'Kilometers per hour',
            ]
        ]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testDeleteReturns405(): void
    {
        $unitRespository = repository(Unit::class);
        $unit = $unitRespository->first();

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('DELETE', "/units/{$unit->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(405);
    }
}
