<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\ReferenceRangeRepository;
use App\State\ReferenceRangeStateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReferenceRangeRepository::class)]
#[ORM\Table(name: '`reference_range`')]
#[ApiResource(
    shortName: 'ReferenceRange',
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
            denormalizationContext: ['groups' => ['referenceRange:create']],
            processor: ReferenceRangeStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            normalizationContext: ['groups' => ['read']],
            denormalizationContext: ['groups' => ['referenceRange:update']],
            processor: ReferenceRangeStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            processor: ReferenceRangeStateProcessor::class,
        ),
    ],
)]
class ReferenceRangeEntity
{
    #[Groups(['read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'referenceRanges')]
    private ?UserEntity $user = null;

    #[Assert\NotBlank]
    #[Groups(['read', 'referenceRange:create', 'referenceRange:update'])]
    #[ORM\ManyToOne(inversedBy: 'referenceRanges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HormoneEntity $hormone = null;

    #[Assert\DisableAutoMapping]
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Type(UnitEntity::class)]
    #[Groups(['read', 'referenceRange:create', 'referenceRange:update'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?UnitEntity $unit = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['read', 'referenceRange:create', 'referenceRange:update'])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    #[Groups(['read', 'referenceRange:create', 'referenceRange:update'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $lowerLimit = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Type('numeric')]
    #[Assert\Regex('/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/', message: 'Invalid number. Enter a value between 0 and 9999.99 with up to 2 decimal places (e.g. 42 or 42.5).')]
    #[Groups(['read', 'referenceRange:create', 'referenceRange:update'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $upperLimit = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['read', 'referenceRange:create', 'referenceRange:update'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

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

    public function getHormone(): ?HormoneEntity
    {
        return $this->hormone;
    }

    public function setHormone(?HormoneEntity $hormone): static
    {
        $this->hormone = $hormone;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLowerLimit(): ?string
    {
        return $this->lowerLimit;
    }

    public function setLowerLimit(string $lowerLimit): static
    {
        $this->lowerLimit = $lowerLimit;

        return $this;
    }

    public function getUpperLimit(): ?string
    {
        return $this->upperLimit;
    }

    public function setUpperLimit(string $upperLimit): static
    {
        $this->upperLimit = $upperLimit;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
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
