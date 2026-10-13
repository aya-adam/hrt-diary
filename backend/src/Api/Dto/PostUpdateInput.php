<?php

namespace App\Api\Dto;

use App\Entity\PostEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\IsNotNull;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: PostEntity::class)]
final class PostUpdateInput
{
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 255)]
    #[Map(if: new IsNotNull())]
    public ?string $title = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 255)]
    #[Map(if: new IsNotNull())]
    public ?string $excerpt = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Map(if: new IsNotNull())]
    public ?string $text = null;

    #[Assert\Type('bool', 'null')]
    #[Map(if: new IsNotNull())]
    public ?bool $published = null;
}
