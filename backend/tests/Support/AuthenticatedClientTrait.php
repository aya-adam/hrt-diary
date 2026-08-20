<?php

namespace App\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use RuntimeException;

trait AuthenticatedClientTrait
{
    private static function createAuthenticatedClient(User $user, string $password = 'password'): Client
    {
        /** @disregard P1013 Undefined method */
        $client = static::createClient();

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
