<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Entity\Post as PostEntity;
use App\Repository\LabResultRepository;
use App\State\LabResultStateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LabResultRepository::class)]
#[ApiResource(
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
            denormalizationContext: ['groups' => ['labResult:create']],
            processor: LabResultStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            normalizationContext: ['groups' => ['read']],
            denormalizationContext: ['groups' => ['labResult:update']],
            processor: LabResultStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            processor: LabResultStateProcessor::class,
        ),
    ],
)]
class LabResult
{
    #[Groups(['read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'labResults')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Measurement>
     */
    #[Groups(['read', 'labResult:create'])]
    #[ORM\OneToMany(targetEntity: Measurement::class, mappedBy: 'labResult', orphanRemoval: true)]
    private Collection $measurements;

    /**
     * @var Collection<int, PostEntity>
     */
    #[Groups(['read'])]
    #[ORM\ManyToMany(targetEntity: PostEntity::class, inversedBy: 'labResults')]
    private Collection $posts;

    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['read', 'labResult:create', 'labResult:update'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[Assert\Type(['bool', 'null'])]
    #[Groups(['labResult:create', 'labResult:update'])]
    private ?bool $published = null;

    #[Groups(['read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[Assert\NotBlank]
    #[Assert\Type('DateTimeInterface')]
    #[Groups(['read', 'labResult:create', 'labResult:update'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $drawnAt = null;

    #[Groups(['read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->measurements = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf(
            'LabResult{id: %s, user: %s, measurements: %s, posts: %s, notes: %s, publishedAt: %s, drawnAt: %s, createdAt: %s, updatedAt: %s}',
            $this->getId()?->toRfc4122() ?? 'null',
            $this->user?->getId()?->toRfc4122() ?? 'null',
            'null',
            'null',
            $this->getNotes() ?? 'null',
            $this->getPublishedAt()?->format('c') ?? 'null',
            $this->getDrawnAt()?->format('c') ?? 'null',
            $this->getCreatedAt()?->format('c') ?? 'null',
            $this->getUpdatedAt()?->format('c') ?? 'null'
        );
    }

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
            $measurement->setLabResult($this);
        }

        return $this;
    }

    public function removeMeasurement(Measurement $measurement): static
    {
        if ($this->measurements->removeElement($measurement)) {
            // set the owning side to null (unless already changed)
            if ($measurement->getLabResult() === $this) {
                $measurement->setLabResult(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostEntity>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(PostEntity $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
        }

        return $this;
    }

    public function removePost(PostEntity $post): static
    {
        $this->posts->removeElement($post);

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

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(?bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getDrawnAt(): ?\DateTimeImmutable
    {
        return $this->drawnAt;
    }

    public function setDrawnAt(\DateTimeImmutable $drawnAt): static
    {
        $this->drawnAt = $drawnAt;

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
