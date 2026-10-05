<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class PasswordStrengthPayload
{
    /**
     * Any stable, non-null value works; it only exists to satisfy the normalizer.
     */
    #[ApiProperty(identifier: true)]
    public readonly Uuid $id;

    public readonly int $estimatedStrength;

    public readonly string $message;

    public function __construct(int $estimatedStrength, string $message = '')
    {
        $this->id = Uuid::v6();
        $this->estimatedStrength = $estimatedStrength;
        $this->message = $message;
    }
}
