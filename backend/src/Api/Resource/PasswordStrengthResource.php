<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Api\Dto\PasswordStrengthInput;
use App\State\PasswordStrengthStateProcessor;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'PasswordStrength',
    operations: [],
    graphQlOperations: [
        new Mutation(
            name: 'check',
            input: PasswordStrengthInput::class,
            processor: PasswordStrengthStateProcessor::class,
        ),
    ],
)]
final class PasswordStrengthResource
{
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