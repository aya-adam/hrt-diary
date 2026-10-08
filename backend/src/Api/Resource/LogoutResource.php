<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\AuthOutput;
use App\State\LogoutStateProcessor;

#[ApiResource(
    shortName: 'Logout',
    operations: [],
    graphQlOperations: [
        new Mutation(
            name: 'auth',
            args: [],
            output: AuthOutput::class,
            processor: LogoutStateProcessor::class,
        ),
    ],
)]
class LogoutResource {}