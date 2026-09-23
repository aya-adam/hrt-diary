<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\HormoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HormoneRepository::class)]
#[ApiResource(
    security: 'is_granted(\'IS_AUTHENTICATED\')',
    normalizationContext: ['groups' => ['read']],
    operations: [],
    graphQlOperations: [
        new Query(),
        new QueryCollection(),
    ],
)]
class Hormone
{
    #[Groups(['read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    /**
     * @var Collection<int, Measurement>
     */
    #[Groups(['read'])]
    #[ORM\OneToMany(targetEntity: Measurement::class, mappedBy: 'hormone', orphanRemoval: true)]
    private Collection $measurements;

    /**
     * @var Collection<int, ReferenceRange>
     */
    #[Groups(['read'])]
    #[ORM\OneToMany(targetEntity: ReferenceRange::class, mappedBy: 'hormone', orphanRemoval: true)]
    private Collection $referenceRanges;

    #[Groups(['read'])]
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[Groups(['read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $commonName = null;

    #[Groups(['read'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Unit $defaultUnit = null;

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
     * @return Collection<int, Measurement>
     */
    public function getMeasurements(): Collection
    {
        return $this->measurements;
    }

    public function addMeasurement(Measurement $measurement): static
    {
        if (!$this->measurements->contains($measurement)) {
            $this->measurements->add($measurement);
            $measurement->setHormone($this);
        }

        return $this;
    }

    public function removeMeasurement(Measurement $measurement): static
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
     * @return Collection<int, ReferenceRange>
     */
    public function getReferenceRanges(): Collection
    {
        return $this->referenceRanges;
    }

    public function addReferenceRange(ReferenceRange $referenceRange): static
    {
        if (!$this->referenceRanges->contains($referenceRange)) {
            $this->referenceRanges->add($referenceRange);
            $referenceRange->setHormone($this);
        }

        return $this;
    }

    public function removeReferenceRange(ReferenceRange $referenceRange): static
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

    public function getDefaultUnit(): ?Unit
    {
        return $this->defaultUnit;
    }

    public function setDefaultUnit(?Unit $defaultUnit): static
    {
        $this->defaultUnit = $defaultUnit;

        return $this;
    }
}
