<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\UnitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
#[ORM\Table(name: '`unit`')]
#[ApiResource(
    shortName: 'Unit',
    security: 'is_granted(\'IS_AUTHENTICATED\')',
    normalizationContext: ['groups' => ['read']],
    operations: [],
    graphQlOperations: [
        new Query(),
        new QueryCollection(),
    ],
)]
class UnitEntity
{
    #[Groups(['read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[Groups(['read'])]
    #[ORM\ManyToOne(inversedBy: 'units')]
    private ?QuantityTypeEntity $quantityType = null;

    #[Groups(['read'])]
    #[ORM\Column(length: 10)]
    private ?string $symbol = null;

    #[Groups(['read'])]
    #[ORM\Column(length: 255)]
    private ?string $label = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getQuantityType(): ?QuantityTypeEntity
    {
        return $this->quantityType;
    }

    public function setQuantityType(?QuantityTypeEntity $quantityType): static
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
