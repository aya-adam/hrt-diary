<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\MeasurementRepository;
use App\State\MeasurementStateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MeasurementRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['measurement:read']],
            security: 'object.getUser() == user or is_granted(\'ROLE_ADMIN\')',
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['measurement:read']],
            parameters: [
                'hormone' => new QueryParameter(filter: new IriFilter()),
                'labResult' => new QueryParameter(filter: new IriFilter()),
            ],
        ),
        new Post(
            normalizationContext: ['groups' => ['measurement:read']],
            denormalizationContext: ['groups' => ['measurement:create']],
            processor: MeasurementStateProcessor::class,
        ),
        new Patch(
            normalizationContext: ['groups' => ['measurement:read']],
            denormalizationContext: ['groups' => ['measurement:update']],
            security: 'object.getUser() == user or is_granted(\'ROLE_ADMIN\')',
            processor: MeasurementStateProcessor::class,
        ),
        new Delete(
            security: 'object.getUser() == user or is_granted(\'ROLE_ADMIN\')',
            processor: MeasurementStateProcessor::class,
        ),
    ],
)]
class Measurement
{
    #[Groups(['measurement:read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Assert\NotBlank]
    #[Groups(['measurement:read', 'measurement:create', 'measurement:update'])]
    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LabResult $labResult = null;

    #[Assert\DisableAutoMapping]
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Type(Unit::class)]
    #[Groups(['measurement:read', 'measurement:create', 'measurement:update'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Unit $unit = null;

    #[Assert\NotBlank]
    #[Groups(['measurement:read', 'measurement:create', 'measurement:update'])]
    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hormone $hormone = null;
    
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['measurement:read', 'measurement:create', 'measurement:update'])]
    #[ORM\ManyToOne]
    private ?ReferenceRange $referenceRange = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    #[Groups(['measurement:read', 'measurement:create', 'measurement:update'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $value = null;

    #[Groups(['measurement:read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['measurement:read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLabResult(): ?LabResult
    {
        return $this->labResult;
    }

    public function setLabResult(?LabResult $labResult): static
    {
        $this->labResult = $labResult;

        return $this;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getHormone(): ?Hormone
    {
        return $this->hormone;
    }

    public function setHormone(?Hormone $hormone): static
    {
        $this->hormone = $hormone;

        return $this;
    }

    public function getReferenceRange(): ?ReferenceRange
    {
        return $this->referenceRange;
    }

    public function setReferenceRange(?ReferenceRange $referenceRange): static
    {
        $this->referenceRange = $referenceRange;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    #[Groups(['measurement:read'])]
    public function getDrawnAt(): ?\DateTimeImmutable
    {
        return $this->labResult->getCreatedAt();
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
