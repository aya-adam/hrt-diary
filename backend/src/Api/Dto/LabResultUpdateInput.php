<?php

namespace App\Api\Dto;

use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\IsNotNull;
use Symfony\Component\Validator\Constraints as Assert;

final class LabResultUpdateInput
{
    #[Assert\Type('array')]
    #[Map(if: new IsNotNull())]
    public ?array $posts = [];

    #[Assert\NotBlank(allowNull: true)]
    #[Map(if: new IsNotNull())]
    public ?string $notes = null;

    #[Assert\Type('bool', 'null')]
    #[Map(if: new IsNotNull())]
    public ?bool $published = null;

    #[Assert\NotBlank]
    #[Assert\Regex('/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d/', message: 'Invalid value. Enter a date in the ATOM (Y-m-d\\TH:i:sP) format.')]
    #[Map(if: new IsNotNull(), transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $drawnAt = null;
}
