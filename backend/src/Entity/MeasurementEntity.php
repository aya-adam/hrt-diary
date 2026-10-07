<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
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
#[ORM\Table(name: '`measurement`')]
#[ApiResource(
    shortName: 'Measurement',
    security: 'is_granted(\'IS_AUTHENTICATED\')',
    operations: [],
    graphQlOperations: [
        new Query(
            normalizationContext: ['groups' => ['read']],
        ),
        new QueryCollection(
            normalizationContext: ['groups' => ['read']],
        ),
        new Mutation(
            name: 'create',
            normalizationContext: ['groups' => ['read']],
            denormalizationContext: ['groups' => ['measurement:create']],
            processor: MeasurementStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            normalizationContext: ['groups' => ['read']],
            denormalizationContext: ['groups' => ['measurement:update']],
            processor: MeasurementStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            processor: MeasurementStateProcessor::class,
        ),
    ],
)]
class MeasurementEntity
{
    #[Groups(['read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserEntity $user = null;

    #[Assert\NotBlank]
    #[Groups(['read', 'measurement:create', 'measurement:update'])]
    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LabResultEntity $labResult = null;

    #[Assert\DisableAutoMapping]
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Type(UnitEntity::class)]
    #[Groups(['read', 'measurement:create', 'measurement:update', 'post:create'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?UnitEntity $unit = null;

    #[Assert\NotBlank]
    #[Groups(['read', 'measurement:create', 'measurement:update', 'post:create'])]
    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HormoneEntity $hormone = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['read', 'measurement:create', 'measurement:update', 'post:create'])]
    #[ORM\ManyToOne]
    private ?ReferenceRangeEntity $referenceRange = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    #[Groups(['read', 'measurement:create', 'measurement:update', 'post:create'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $value = null;

    #[Groups(['read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(?UserEntity $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLabResult(): ?LabResultEntity
    {
        return $this->labResult;
    }

    public function setLabResult(?LabResultEntity $labResult): static
    {
        $this->labResult = $labResult;

        return $this;
    }

    public function getUnit(): ?UnitEntity
    {
        return $this->unit;
    }

    public function setUnit(?UnitEntity $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getHormone(): ?HormoneEntity
    {
        return $this->hormone;
    }

    public function setHormone(?HormoneEntity $hormone): static
    {
        $this->hormone = $hormone;

        return $this;
    }

    public function getReferenceRange(): ?ReferenceRangeEntity
    {
        return $this->referenceRange;
    }

    public function setReferenceRange(?ReferenceRangeEntity $referenceRange): static
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

    #[Groups(['read'])]
    public function getDrawnAt(): ?\DateTimeImmutable
    {
        return $this->labResult->getDrawnAt();
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
