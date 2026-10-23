<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\LoginInput;
use App\State\AuthStateProcessor;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Auth',
    security: 'is_granted(\'PUBLIC_ACCESS\')',
    operations: [],
    graphQlOperations: [
        new Mutation(
            name: 'get',
            args: [
                'email' => ['type' => 'String!'],
                'password' => ['type' => 'String!'],
            ],
            input: LoginInput::class,
            processor: AuthStateProcessor::class,
        ),
        new Mutation(
            name: 'refresh',
            args: [],
            processor: AuthStateProcessor::class,
        ),
        new Mutation(
            name: 'revoke',
            args: [],
            processor: AuthStateProcessor::class,
        ),
    ],
)]
final class AuthResource
{
    #[ApiProperty(identifier: true)]
    public readonly Uuid $id;

    public readonly ?string $message;

    public readonly ?string $accessTokenExpiresAt;

    public readonly ?UserResource $user;

    public function __construct(string $message = '', ?Cookie $accessTokenCookie = null, ?UserResource $userResource = null)
    {
        $this->id = Uuid::v6();
        $this->message = $message;
        $this->accessTokenExpiresAt = $accessTokenCookie
            ? \DateTimeImmutable::createFromTimestamp($accessTokenCookie->getExpiresTime())->format('c')
            : null;
        $this->user = $userResource;
    }
}
