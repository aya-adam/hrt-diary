<?php

namespace App\Api\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginInput
{
    #[Assert\NotBlank]
    public string $email = '';
    
    #[Assert\NotBlank]
    public string $password = '';
}