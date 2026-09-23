<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Controller\LogoutController;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/auth/logout',
            controller: LogoutController::class,
            name: 'logout',
            read: false,
            write: false,
            output: false,
            openapi: new OpenApiOperation(
                tags: ['Authentication'],
                summary: 'Invalidates the access_token / refresh_token cookies',
            ),
        )
    ],
    graphQlOperations: [],
)]
class Logout {}