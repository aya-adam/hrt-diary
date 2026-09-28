<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class AuthPayload
{
    /**
     * Any stable, non-null value works; it only exists to satisfy the normalizer.
     */
    #[ApiProperty(identifier: true)]
    public readonly Uuid $id;

    public readonly ?string $userId;

    public readonly ?string $userIdentifier;

    public readonly ?string $accessTokenExpiresAt;

    public readonly string $message;

    public function __construct(?User $user = null, ?Cookie $accessTokenCookie = null, ?string $message = '')
    {
        $this->id = Uuid::v6();
        $this->userId = $user?->getId()->toRfc4122();
        $this->userIdentifier = $user?->getUserIdentifier();
        $this->accessTokenExpiresAt = $accessTokenCookie
            ? \DateTimeImmutable::createFromTimestamp($accessTokenCookie->getExpiresTime())->format('c')
            : null;
        $this->message = $message;
    }
}
