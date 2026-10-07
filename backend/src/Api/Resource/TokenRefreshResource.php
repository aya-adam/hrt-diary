<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\AuthPayload;
use App\State\TokenRefreshStateProcessor;

#[ApiResource(
    shortName: 'TokenRefresh',
    operations: [],
    graphQlOperations: [
        new Mutation(
            name: 'auth',
            args: [],
            output: AuthPayload::class,
            processor: TokenRefreshStateProcessor::class,
        ),
    ],
)]
class TokenRefreshResource {}
