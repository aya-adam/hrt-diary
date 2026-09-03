<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\QuantityType;
use App\Entity\Unit;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;

class QuantityTypeResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsQuyntityTypes(): void
    {
        $user = UserFactory::createOne();

        $response = self::createAuthenticatedClient($user)->request('GET', '/quantity_types');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(['totalItems' => 3]);
        $this->assertCount(3, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(QuantityType::class);
    }

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('GET', '/quantity_types');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsQuyntityType(): void
    {
        $quantityTypeRespository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(QuantityType::class);
        $quantityType = $quantityTypeRespository->findAll()[0];

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('GET', "/quantity_types/{$quantityType->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(QuantityType::class);
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $quantityTypeRespository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(QuantityType::class);
        $quantityType = $quantityTypeRespository->findAll()[0];

        self::createClient()->request('GET', "/quantity_types/{$quantityType->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPostReturns405(): void
    {
        $unitRespository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Unit::class);
        $units = $unitRespository->findAll();

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/quantity_types', ['json' => [
            'units' => [
                "/units/{$units[0]->getId()->toRfc4122()}",
                "/units/{$units[1]->getId()->toRfc4122()}",
                "/units/{$units[2]->getId()->toRfc4122()}",
                "/units/{$units[3]->getId()->toRfc4122()}",
            ],
            'label' => 'Acceleration',
        ]]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testPatchReturns405(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $quantityTypeRespository = $em->getRepository(QuantityType::class);
        $quantityType = $quantityTypeRespository->findAll()[0];

        $unitRespository = $em->getRepository(Unit::class);
        $units = $unitRespository->findAll();

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/quantity_types/{$quantityType->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'units' => [
                    "/units/{$units[0]->getId()->toRfc4122()}",
                    "/units/{$units[1]->getId()->toRfc4122()}",
                    "/units/{$units[2]->getId()->toRfc4122()}",
                    "/units/{$units[3]->getId()->toRfc4122()}",
                ],
                'label' => 'Acceleration',
            ]
        ]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testDeleteReturns405(): void
    {
        $unitRespository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Unit::class);
        $unit = $unitRespository->findAll()[0];

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('DELETE', "/quantity_types/{$unit->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(405);
    }
}
