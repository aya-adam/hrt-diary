<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
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
    operations: [
        new Get(
            normalizationContext: ['groups' => ['labResult:read']],
            security: 'object.getUser() == user or is_granted(\'ROLE_ADMIN\')',
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['labResult:read']],
        ),
        new Post(
            normalizationContext: ['groups' => ['labResult:read']],
            denormalizationContext: ['groups' => ['labResult:create']],
            processor: LabResultStateProcessor::class,
        ),
        new Patch(
            normalizationContext: ['groups' => ['labResult:read']],
            denormalizationContext: ['groups' => ['labResult:update']],
            security: 'object.getUser() == user or is_granted(\'ROLE_ADMIN\')',
            processor: LabResultStateProcessor::class,
        ),
        new Delete(
            security: 'object.getUser() == user or is_granted(\'ROLE_ADMIN\')',
            processor: LabResultStateProcessor::class,
        ),
    ],
)]
class LabResult
{
    #[Groups(['labResult:read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'labResults')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, PostEntity>
     */
    #[Groups(['labResult:read', 'labResult:create', 'labResult:update'])]
    #[ORM\ManyToMany(targetEntity: PostEntity::class, inversedBy: 'labResults')]
    private Collection $posts;

    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['labResult:read', 'labResult:create', 'labResult:update'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[Assert\Type(['bool', 'null'])]
    #[Groups(['labResult:create', 'labResult:update'])]
    private ?bool $published = null;

    #[Groups(['labResult:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[Assert\NotBlank]
    #[Assert\Type('DateTimeInterface')]
    #[Groups(['labResult:read', 'labResult:create', 'labResult:update'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $drawnAt = null;

    #[Groups(['labResult:read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['labResult:read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
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
