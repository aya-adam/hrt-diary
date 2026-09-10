<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\HormoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HormoneRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['hormone:read']],
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['hormone:read']],
        ),
    ],
)]
class Hormone
{
    #[Groups(['hormone:read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    /**
     * @var Collection<int, Measurement>
     */
    #[Groups(['hormone:read'])]
    #[ORM\OneToMany(targetEntity: Measurement::class, mappedBy: 'hormone', orphanRemoval: true)]
    private Collection $measurements;

    #[Groups(['hormone:read'])]
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[Groups(['hormone:read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $commonName = null;

    #[Groups(['hormone:read'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Unit $defaultUnit = null;

    public function __construct()
    {
        $this->measurements = new ArrayCollection();
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
