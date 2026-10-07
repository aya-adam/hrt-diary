<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\AuthOutput;
use App\Api\Dto\LoginInput;
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
            input: LoginInput::class,
            output: AuthOutput::class,
            processor: LoginStateProcessor::class,
        ),
    ],
)]
class LoginResource {}
