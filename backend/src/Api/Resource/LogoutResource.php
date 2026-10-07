<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Api\Dto\AuthOutput;
use App\Controller\LogoutController;
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