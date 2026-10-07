<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class AuthOutput
{
    #[ApiProperty(identifier: true)]
    public readonly Uuid $id;

    public readonly ?string $userId;

    public readonly ?string $userIdentifier;

    public readonly ?string $accessTokenExpiresAt;

    public readonly string $message;

    public function __construct(string $message, ?string $userIri = null, ?string $userIdentifier = null, ?Cookie $accessTokenCookie = null)
    {
        $this->id = Uuid::v6();
        $this->userId = $userIri;
        $this->userIdentifier = $userIdentifier;
        $this->accessTokenExpiresAt = $accessTokenCookie
            ? \DateTimeImmutable::createFromTimestamp($accessTokenCookie->getExpiresTime())->format('c')
            : null;
        $this->message = $message;
    }
}
