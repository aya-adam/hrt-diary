<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;

final class RefreshTokenDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['RefreshTokenResponse'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'format' => 'uuid'],
                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com'],
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: 'RefreshToken',
            summary: 'Refresh the access token',
            post: new Model\Operation(
                operationId: 'postRefreshToken',
                tags: ['Authentication'],
                responses: [
                    '200' => new Model\Response(
                        description: 'The access token was refreshed successfully. New `access_token` and `refresh_token` HttpOnly cookies are set on the response, and the currently authenticated user is returned in the body.',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/RefreshTokenResponse'],
                            ],
                        ]),
                        headers: new \ArrayObject([
                            'Set-Cookie' => [
                                'description' => 'Two cookies are set: `access_token` (short-lived) and `refresh_token` (long-lived), both HttpOnly.',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'example' => [
                                        'access_token=eyJ...; Path=/; HttpOnly; Secure; SameSite=Strict',
                                        'refresh_token=eyJ...; Path=/; HttpOnly; Secure; SameSite=Strict',
                                    ],
                                ],
                            ],
                        ])
                    ),
                    '401' => new Model\Response(
                        description: 'The `refresh_token` cookie is missing, invalid, expired or has already been used.',
                    ),
                ],
                summary: 'Refresh the access token using the refresh token cookie.',
                description: <<<'DESCRIPTION'
                    Reads the `refresh_token` HttpOnly cookie sent by the browser, validates it,
                    and — if valid — issues a new pair of tokens. Both `access_token` and
                    `refresh_token` are set again as HttpOnly cookies on the response (token
                    rotation), and the currently authenticated user is returned as JSON.

                    This endpoint does not accept the refresh token as a request body or
                    header parameter: it is read exclusively from the `refresh_token` cookie,
                    which must be sent automatically by the browser (`credentials: 'include'`
                    on the client side).
                    DESCRIPTION,
                parameters: [
                    new Model\Parameter(
                        name: 'refresh_token',
                        in: 'cookie',
                        description: 'The refresh token issued at login / previous refresh, stored as an HttpOnly cookie.',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                ],
            )
        );

        $openApi->getPaths()->addPath('/auth/refresh', $pathItem);

        return $openApi->withComponents($openApi->getComponents()->withSchemas($schemas));
    }
}
