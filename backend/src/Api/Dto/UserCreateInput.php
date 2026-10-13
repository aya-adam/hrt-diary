<?php

namespace App\Api\Dto;

use App\Entity\UserEntity;
use App\ObjectMapper\UserPasswordTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[Map(target: UserEntity::class)]
#[UniqueEntity(fields: 'email', entityClass: UserEntity::class, message: 'This email is already in use.')]
final class UserCreateInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_STRONG)]
    #[Map(transform: UserPasswordTransformer::class)]
    public ?string $password = null;
}
