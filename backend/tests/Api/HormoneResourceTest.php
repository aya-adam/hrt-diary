<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Hormone;
use App\Entity\Unit;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;

class HormoneResourceTest extends ApiTestCase
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

    public function testGetCollectionReturnsHormones(): void
    {
        $user = UserFactory::createOne();

        $response = self::createAuthenticatedClient($user)->request('GET', '/hormones');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(['totalItems' => 8]);
        $this->assertCount(8, $response->toArray()['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Hormone::class);
    }

    public function testGetCollectionReturns401ForAnonymousUser(): void
    {
        self::createClient()->request('GET', '/hormones');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonEquals([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetItemReturnsUnit(): void
    {
        $hormoneRespository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Hormone::class);
        $hormone = $hormoneRespository->findAll()[0];

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('GET', "/hormones/{$hormone->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceItemJsonSchema(Hormone::class);
    }

    public function testGetItemReturns401ForAnonymousUser(): void
    {
        $hormoneRespository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Hormone::class);
        $hormone = $hormoneRespository->findAll()[0];

        self::createClient()->request('GET', "/hormones/{$hormone->getId()->toRfc4122()}");

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
        $unit = $unitRespository->findAll()[0];

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('POST', '/hormones', ['json' => [
            'defaultUnit' => "/units/{$unit->getId()->toRfc4122()}",
            'name' => 'Estron',
            'commonName' => 'E1',
        ]]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testPatchReturns405(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $hormoneRespository = $em->getRepository(Hormone::class);
        $hormone = $hormoneRespository->findAll()[0];

        $unitRespository = $em->getRepository(Unit::class);
        $unit = $unitRespository->findAll()[0];

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('PATCH', "/hormones/{$hormone->getId()->toRfc4122()}", [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => [
                'defaultUnit' => "/units/{$unit->getId()->toRfc4122()}",
                'name' => 'Estron',
                'commonName' => 'E1',
            ]
        ]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testDeleteReturns405(): void
    {
        $hormoneRespository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Hormone::class);
        $hormone = $hormoneRespository->findAll()[0];

        $user = UserFactory::createOne();

        self::createAuthenticatedClient($user)->request('DELETE', "/hormones/{$hormone->getId()->toRfc4122()}");

        $this->assertResponseStatusCodeSame(405);
    }
}
