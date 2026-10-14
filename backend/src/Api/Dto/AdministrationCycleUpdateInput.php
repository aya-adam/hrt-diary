<?php

namespace App\Api\Dto;

use App\Acme\LocationStartingIndexValidator;
use App\Entity\AdministrationCycleEntity;
use App\Util\DateImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\IsNotNull;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: AdministrationCycleEntity::class)]
final class AdministrationCycleUpdateInput
{
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 100)]
    #[Map(if: new IsNotNull())]
    public ?string $name = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Regex('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/', message: 'Invalid value. Enter a date in the Y-m-d format.')]
    #[Map(if: new IsNotNull(), transform: [DateImmutableTransformer::class, 'format'])]
    public ?string $startingDate = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Type('array', 'null')]
    #[Assert\Count(min: 1, minMessage: 'You must specify at least one location.')]
    #[Assert\All([
        new Assert\NotBlank,
        new Assert\Type('string'),
    ])]
    #[Map(if: new IsNotNull())]
    public ?array $locations = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Type('int')]
    #[Assert\PositiveOrZero]
    #[Assert\Callback([LocationStartingIndexValidator::class, 'validate'])]
    #[Map(if: new IsNotNull())]
    public ?int $startingIndex = null;
}
