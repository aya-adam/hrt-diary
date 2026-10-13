<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\UserEntity;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class AuthOutput
{
    #[ApiProperty(identifier: true)]
    public readonly Uuid $id;

    public readonly string $message;

    public readonly ?string $accessTokenExpiresAt;

    public readonly ?string $userId;

    public readonly ?string $userIdentifier;

    public function __construct(string $message, ?Cookie $accessTokenCookie = null, ?UserEntity $user = null)
    {
        $this->id = Uuid::v6();
        $this->message = $message;
        $this->accessTokenExpiresAt = $accessTokenCookie
            ? \DateTimeImmutable::createFromTimestamp($accessTokenCookie->getExpiresTime())->format('c')
            : null;
        $this->userId = $user
            ? sprintf('/users/%s', $user->getId()->toRfc4122())
            : null;
        $this->userIdentifier = $user?->getUserIdentifier();
    }
}
