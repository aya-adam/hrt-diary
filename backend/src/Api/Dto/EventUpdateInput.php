<?php

namespace App\Api\Dto;

use App\Api\Resource\EventResource;
use App\Entity\EventEntity;
use App\Util\DateIntervalTransformer;
use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\IsNotNull;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: EventEntity::class)]
final class EventUpdateInput
{
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 100)]
    #[Map(if: new IsNotNull())]
    public ?string $name = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Regex('/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d/', message: 'Invalid value. Enter a date in the ATOM (Y-m-d\\TH:i:sP) format.')]
    #[Map(if: new IsNotNull(), transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $date = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Regex('/P(?!$)(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?=[\d+])(?:\d+H)?(?:\d+M)?(?:\d+S)?)?/', message: 'Invalid value. Enter an interval in the ISO-8601 (P0Y0M0DT0H0M0S) format.')]
    #[Map(if: new IsNotNull(), transform: [DateIntervalTransformer::class, 'format'])]
    public ?string $repeatInterval = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Regex('/P(?!$)(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?=[\d+])(?:\d+H)?(?:\d+M)?(?:\d+S)?)?/', message: 'Invalid value. Enter an interval in the ISO-8601 (P0Y0M0DT0H0M0S) format.')]
    #[Map(if: new IsNotNull(), transform: [DateIntervalTransformer::class, 'format'])]
    public ?string $earlyNotification = null;

    #[Assert\Type(['bool', 'null'])]
    #[Map(if: new IsNotNull(), transform: [EventResource::class, 'formatCritical'])]
    public ?bool $critical = null;
}
