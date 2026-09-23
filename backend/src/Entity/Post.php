<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\PostRepository;
use App\State\PostStateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
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
            denormalizationContext: ['groups' => ['post:create']],
            processor: PostStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            normalizationContext: ['groups' => ['read']],
            denormalizationContext: ['groups' => ['post:update']],
            processor: PostStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            processor: PostStateProcessor::class,
        ),
    ],
)]
class Post
{
    #[Groups(['read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, LabResult>
     */
    #[Groups(['read', 'post:create', 'post:update'])]
    #[ORM\ManyToMany(targetEntity: LabResult::class, mappedBy: 'posts')]
    private Collection $labResults;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['read', 'post:create', 'post:update'])]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['read', 'post:create', 'post:update'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $excerpt = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['read', 'post:create', 'post:update'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[Assert\Type(['bool', 'null'])]
    #[Groups(['post:create', 'post:update'])]
    private ?bool $published = null;

    #[Groups(['read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

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
        $this->labResults = new ArrayCollection();
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
     * @return Collection<int, LabResult>
     */
    public function getLabResults(): Collection
    {
        return $this->labResults;
    }

    public function addLabResult(LabResult $labResult): static
    {
        if (!$this->labResults->contains($labResult)) {
            $this->labResults->add($labResult);
            $labResult->addPost($this);
        }

        return $this;
    }

    public function removeLabResult(LabResult $labResult): static
    {
        if ($this->labResults->removeElement($labResult)) {
            $labResult->removePost($this);
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): static
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

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
