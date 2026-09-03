<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\QuantityTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuantityTypeRepository::class)]
#[UniqueEntity(fields: 'slug', identifierFieldNames: ['id'], message: 'This slug already exists.')]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['quantityType:read']],
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['quantityType:read']],
        ),
    ],
)]
class QuantityType
{
    #[Groups(['quantityType:read', 'hormone:read', 'unit:read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    /**
     * @var Collection<int, Unit>
     */
    #[Groups(['quantityType:read'])]
    #[ORM\OneToMany(targetEntity: Unit::class, mappedBy: 'quantityType')]
    private Collection $units;

    #[Groups(['quantityType:read', 'hormone:read', 'unit:read'])]
    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[Groups(['quantityType:read', 'hormone:read', 'unit:read'])]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['label'])]
    private ?string $slug = null;

    public function __construct()
    {
        $this->units = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Unit>
     */
    public function getUnits(): Collection
    {
        return $this->units;
    }

    public function addUnit(Unit $unit): static
    {
        if (!$this->units->contains($unit)) {
            $this->units->add($unit);
            $unit->setQuantityType($this);
        }

        return $this;
    }

    public function removeUnit(Unit $unit): static
    {
        if ($this->units->removeElement($unit)) {
            // set the owning side to null (unless already changed)
            if ($unit->getQuantityType() === $this) {
                $unit->setQuantityType(null);
            }
        }

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}
