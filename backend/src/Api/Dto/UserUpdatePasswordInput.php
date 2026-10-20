<?php

namespace App\Api\Dto;

use App\Entity\UserEntity;
use App\ObjectMapper\UserPasswordTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\IsNotNull;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\PasswordStrength;

#[Map(target: UserEntity::class)]
final class UserUpdatePasswordInput
{
    #[Assert\NotBlank]
    #[SecurityAssert\UserPassword(message: 'Wrong value for your current password.')]
    #[Map(if: false)]
    public ?string $oldPassword = null;
    
    #[Assert\NotBlank]
    #[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_STRONG)]
    #[Map(if: new IsNotNull(), target: 'password', transform: UserPasswordTransformer::class)]
    public ?string $newPassword = null;
}
