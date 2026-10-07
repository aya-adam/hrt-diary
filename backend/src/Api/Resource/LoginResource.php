<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\AuthPayload;
use App\State\LoginStateProcessor;

#[ApiResource(
    shortName: 'Login',
    operations: [],
    graphQlOperations: [
        new Mutation(
            name: 'auth',
            args: [
                'email' => ['type' => 'String!'],
                'password' => ['type' => 'String!'],
            ],
            output: AuthPayload::class,
            processor: LoginStateProcessor::class,
        ),
    ],
)]
class LoginResource {
    public string $email = '';
    public string $password = '';
}