<?php

namespace App\Api\Dto;

use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final class LabResultCreateInput
{
    #[Assert\Type('array')]
    public ?array $posts = [];

    #[Assert\NotBlank(allowNull: true)]
    public ?string $notes = null;

    #[Assert\Type('bool', 'null')]
    public ?bool $published = null;

    #[Assert\NotBlank]
    #[Assert\Regex('/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d/', message: 'Invalid value. Enter a date in the ATOM (Y-m-d\\TH:i:sP) format.')]
    #[Map(transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $drawnAt = null;
}
