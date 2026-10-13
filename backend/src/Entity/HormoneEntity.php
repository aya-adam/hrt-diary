<?php

namespace App\Entity;

use App\Repository\HormoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HormoneRepository::class)]
#[ORM\Table(name: '`hormone`')]
class HormoneEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    /**
     * @var Collection<int, MeasurementEntity>
     */
    #[ORM\OneToMany(targetEntity: MeasurementEntity::class, mappedBy: 'hormone', orphanRemoval: true)]
    private Collection $measurements;

    /**
     * @var Collection<int, ReferenceRangeEntity>
     */
    #[ORM\OneToMany(targetEntity: ReferenceRangeEntity::class, mappedBy: 'hormone', orphanRemoval: true)]
    private Collection $referenceRanges;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $commonName = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?UnitEntity $defaultUnit = null;

    public function __construct()
    {
        $this->measurements = new ArrayCollection();
        $this->referenceRanges = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    /**
     * @return Collection<int, MeasurementEntity>
     */
    public function getMeasurements(): Collection
    {
        return $this->measurements;
    }

    public function addMeasurement(MeasurementEntity $measurement): static
    {
        if (!$this->measurements->contains($measurement)) {
            $this->measurements->add($measurement);
            $measurement->setHormone($this);
        }

        return $this;
    }

    public function removeMeasurement(MeasurementEntity $measurement): static
    {
        if ($this->measurements->removeElement($measurement)) {
            // set the owning side to null (unless already changed)
            if ($measurement->getHormone() === $this) {
                $measurement->setHormone(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ReferenceRangeEntity>
     */
    public function getReferenceRanges(): Collection
    {
        return $this->referenceRanges;
    }

    public function addReferenceRange(ReferenceRangeEntity $referenceRange): static
    {
        if (!$this->referenceRanges->contains($referenceRange)) {
            $this->referenceRanges->add($referenceRange);
            $referenceRange->setHormone($this);
        }

        return $this;
    }

    public function removeReferenceRange(ReferenceRangeEntity $referenceRange): static
    {
        if ($this->referenceRanges->removeElement($referenceRange)) {
            // set the owning side to null (unless already changed)
            if ($referenceRange->getHormone() === $this) {
                $referenceRange->setHormone(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCommonName(): ?string
    {
        return $this->commonName;
    }

    public function setCommonName(?string $commonName): static
    {
        $this->commonName = $commonName;

        return $this;
    }

    public function getDefaultUnit(): ?UnitEntity
    {
        return $this->defaultUnit;
    }

    public function setDefaultUnit(?UnitEntity $defaultUnit): static
    {
        $this->defaultUnit = $defaultUnit;

        return $this;
    }
}
