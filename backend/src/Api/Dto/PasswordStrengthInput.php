<?php

namespace App\Api\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class PasswordStrengthInput
{
    #[Assert\NotBlank]
    public string $password = '';
}
