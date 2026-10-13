<?php

namespace App\Api\Dto;

use App\Api\Resource\HormoneResource;
use App\Api\Resource\LabResultResource;
use App\Api\Resource\ReferenceRangeResource;
use App\Api\Resource\UnitResource;
use App\ObjectMapper\ResourceTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final class MeasurementCreateInput
{
    #[Assert\NotBlank]
    #[Map(transform: ResourceTransformer::class)]
    public ?LabResultResource $labResult = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Map(transform: ResourceTransformer::class)]
    public ?UnitResource $unit = null;

    #[Assert\NotBlank]
    #[Map(transform: ResourceTransformer::class)]
    public ?HormoneResource $hormone = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Map(transform: ResourceTransformer::class)]
    public ?ReferenceRangeResource $referenceRange = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    public ?string $value = null;
}
