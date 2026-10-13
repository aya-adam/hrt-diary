<?php

namespace App\Api\Dto;

use App\Api\Resource\HormoneResource;
use App\Api\Resource\UnitResource;
use App\Entity\ReferenceRangeEntity;
use App\ObjectMapper\ResourceTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\IsNotNull;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: ReferenceRangeEntity::class)]
final class ReferenceRangeUpdateInput
{
    #[Assert\NotBlank(allowNull: true)]
    #[Map(if: new IsNotNull())]
    #[Map(transform: ResourceTransformer::class)]
    public ?HormoneResource $hormone = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Map(if: new IsNotNull())]
    #[Map(transform: ResourceTransformer::class)]
    public ?UnitResource $unit = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 255)]
    #[Map(if: new IsNotNull())]
    public ?string $name = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    #[Map(if: new IsNotNull())]
    public ?string $lowerLimit = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    #[Map(if: new IsNotNull())]
    public ?string $upperLimit = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Map(if: new IsNotNull())]
    public ?string $notes = null;
}
