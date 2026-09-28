<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\AuthPayload;
use App\State\LoginStateProcessor;

#[ApiResource(
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
class Login {
    public string $email = '';
    public string $password = '';
}