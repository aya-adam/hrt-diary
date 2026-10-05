<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\PasswordStrengthPayload;
use App\State\PasswordStrengthStateProcessor;

#[ApiResource(
    operations: [],
    graphQlOperations: [
        new Mutation(
            name: 'check',
            args: [
                'password' => ['type' => 'String!'],
            ],
            output: PasswordStrengthPayload::class,
            processor: PasswordStrengthStateProcessor::class,
        ),
    ],
)]
final class PasswordStrength
{
    public string $password = '';
}