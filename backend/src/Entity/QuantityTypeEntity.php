<?php

namespace App\Entity;

use App\Repository\QuantityTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuantityTypeRepository::class)]
#[ORM\Table(name: '`quantity_type`')]
#[UniqueEntity(fields: 'slug', identifierFieldNames: ['id'], message: 'This slug already exists.')]
class QuantityTypeEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    /**
     * @var Collection<int, UnitEntity>
     */
    #[ORM\OneToMany(targetEntity: UnitEntity::class, mappedBy: 'quantityType')]
    private Collection $units;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 255, unique: true)]
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
     * @return Collection<int, UnitEntity>
     */
    public function getUnits(): Collection
    {
        return $this->units;
    }

    public function addUnit(UnitEntity $unit): static
    {
        if (!$this->units->contains($unit)) {
            $this->units->add($unit);
            $unit->setQuantityType($this);
        }

        return $this;
    }

    public function removeUnit(UnitEntity $unit): static
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
