<?php

namespace App\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use RuntimeException;

trait AuthenticatedClientTrait
{
    private static function createAuthenticatedClient(User $user, string $password = 'password', array $kernelOptions = [], array $defaultOptions = []): Client
    {
        /**
         * @var \ApiPlatform\Symfony\Bundle\Test\Client $client
         * @disregard P1013 Undefined method
        */
        $client = static::createClient($kernelOptions, $defaultOptions);

        $response = $client->request('POST', '/auth/login', ['json' => [
            'username' => $user->getUserIdentifier(),
            'password' => $password,
        ]]);

        if (($statusCode = $response->getStatusCode()) !== 200) {
            throw new RuntimeException(sprintf(
                'Failed to authenticate test user "%s" (HTTP %d): %s',
                $user->getUserIdentifier(),
                $statusCode,
                $response->getContent(false),
            ));
        }

        return $client;
    }
}
