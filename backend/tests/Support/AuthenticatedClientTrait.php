<?php

namespace App\Tests\Support;

use ApiPlatform\Test\Client;
use App\Entity\UserEntity;
use App\Factory\UserFactory;
use RuntimeException;

trait AuthenticatedClientTrait
{
    private static function createAuthenticatedClient(UserEntity $user, string $password = UserFactory::DEFAULT_PASSWORD, array $kernelOptions = [], array $defaultOptions = []): Client
    {
        /**
         * @var \ApiPlatform\Symfony\Bundle\Test\Client $client
         * @disregard P1013 Undefined method
         */
        $client = static::createClient($kernelOptions, $defaultOptions);

        $query = <<<'GQL'
            mutation GetAuth($email: String!, $password: String!) {
                getAuth(input: { email: $email, password: $password }) {
                    auth {
                        message
                        accessTokenExpiresAt
                        user {
                            id
                            email
                        }
                    }
                }
            }
        GQL;

        $options = [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'query' => $query,
                'variables' => [
                    'email' => $user->getUserIdentifier(),
                    'password' => $password,
                ],
                'operationName' => 'GetAuth'
            ],
        ];

        $response = $client->request('POST', '/graphql', $options);
        $body = $response->toArray(false);

        if (!empty($body['errors'][0])) {
            throw new RuntimeException(sprintf(
                'Failed to authenticate test user "%s" (%s): %s',
                $user->getUserIdentifier(),
                $body['errors'][0]['message'],
                $response->getContent(false),
            ));
        }

        return $client;
    }
}
