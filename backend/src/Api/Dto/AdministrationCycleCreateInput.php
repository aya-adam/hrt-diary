<?php

namespace App\Api\Dto;

use App\Acme\LocationStartingIndexValidator;
use App\Entity\AdministrationCycleEntity;
use App\Util\DateImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: AdministrationCycleEntity::class)]
final class AdministrationCycleCreateInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Regex('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/', message: 'Invalid value. Enter a date in the Y-m-d format.')]
    #[Map(transform: [DateImmutableTransformer::class, 'format'])]
    public ?string $startingDate = null;

    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'You must specify at least one location.')]
    #[Assert\All([
        new Assert\NotBlank,
        new Assert\Type('string'),
    ])]
    public ?array $locations = null;

    #[Assert\NotBlank]
    #[Assert\Type('int')]
    #[Assert\PositiveOrZero]
    #[Assert\Callback([LocationStartingIndexValidator::class, 'validate'])]
    public ?int $startingIndex = null;
}
