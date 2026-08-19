<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final class LoginResponseDecorator implements OpenApiFactoryInterface
{
    private const PATH = '/auth/login';

    public function __construct(private readonly OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        $pathItem = $paths->getPath(self::PATH);

        if (null === $pathItem || !$pathItem->getPost() instanceof Operation) {
            // Nothing to rewrite — path missing or method changed upstream.
            return $openApi;
        }

        $operation = $pathItem->getPost();

        $newOperation = $operation
            ->withResponses([
                '200' => new Response(
                    description: 'The user was authenticated successfully. `access_token` and `refresh_token` HttpOnly cookies are set on the response, and the currently authenticated user is returned in the body.',
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
                '401' => new Response(
                    description: 'Invalid credentials.',
                ),
            ])
            ->withSummary('Log in and receive access_token / refresh_token cookies.')
            ->withDescription(<<<'DESCRIPTION'
                Authenticates the user with the given `username` and `password`. On
                success, `access_token` and `refresh_token` are set as HttpOnly cookies
                on the response (they are not returned in the JSON body), and the
                currently authenticated user is returned as JSON.
                DESCRIPTION);

        $newPathItem = $pathItem->withPost($newOperation);
        $paths->addPath(self::PATH, $newPathItem);

        return $openApi;
    }
}
