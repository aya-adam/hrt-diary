<?php

namespace App\Api\Dto;

use App\Entity\UserEntity;
use App\ObjectMapper\UserPasswordTransformer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\IsNotNull;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\PasswordStrength;

#[Map(target: UserEntity::class)]
#[UniqueEntity(fields: 'email', entityClass: UserEntity::class, message: 'This email is already in use.')]
final class UserUpdateInput
{
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Email]
    #[Map(if: new IsNotNull())]
    public ?string $email = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_STRONG)]
    #[Map(if: new IsNotNull(), transform: UserPasswordTransformer::class)]
    public ?string $password = null;
}
