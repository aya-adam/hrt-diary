<?php

namespace App\Api\Dto;

use App\Entity\PostEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: PostEntity::class)]
final class PostCreateInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 255)]
    public ?string $excerpt = null;

    #[Assert\NotBlank(allowNull: true)]
    public ?string $text = null;

    #[Assert\Type('bool', 'null')]
    public ?bool $published = null;
}
