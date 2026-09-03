<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\UnitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['unit:read']],
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['unit:read']],
        ),
    ],
)]
class Unit
{
    #[Groups(['unit:read', 'quantityType:read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[Groups(['unit:read'])]
    #[ORM\ManyToOne(inversedBy: 'units')]
    private ?QuantityType $quantityType = null;

    #[Groups(['unit:read', 'quantityType:read'])]
    #[ORM\Column(length: 10)]
    private ?string $symbol = null;

    #[Groups(['unit:read', 'quantityType:read'])]
    #[ORM\Column(length: 255)]
    private ?string $label = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getQuantityType(): ?QuantityType
    {
        return $this->quantityType;
    }

    public function setQuantityType(?QuantityType $quantityType): static
    {
        $this->quantityType = $quantityType;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): static
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
