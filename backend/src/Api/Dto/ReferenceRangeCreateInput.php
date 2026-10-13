<?php

namespace App\Api\Dto;

use App\Api\Resource\HormoneResource;
use App\Api\Resource\UnitResource;
use App\Entity\ReferenceRangeEntity;
use App\ObjectMapper\ResourceTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: ReferenceRangeEntity::class)]
final class ReferenceRangeCreateInput
{
    #[Assert\NotBlank]
    #[Map(transform: ResourceTransformer::class)]
    public ?HormoneResource $hormone = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Map(transform: ResourceTransformer::class)]
    public ?UnitResource $unit = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    public ?string $lowerLimit = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    public ?string $upperLimit = null;

    #[Assert\NotBlank(allowNull: true)]
    public ?string $notes = null;
}
