<?php

namespace App\Entity;

use App\Repository\LabResultRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LabResultRepository::class)]
#[ORM\Table(name: '`lab_result`')]
class LabResultEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'labResults')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserEntity $user = null;

    /**
     * @var Collection<int, MeasurementEntity>
     */
    #[ORM\OneToMany(targetEntity: MeasurementEntity::class, mappedBy: 'labResult', orphanRemoval: true)]
    private Collection $measurements;

    /**
     * @var Collection<int, PostEntity>
     */
    #[ORM\JoinTable(name: 'lab_result_post')]
    #[ORM\JoinColumn(name: 'lab_result_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: PostEntity::class, inversedBy: 'labResults')]
    private Collection $posts;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $drawnAt = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(?UserEntity $user): static
    {
        $this->user = $user;

        return $this;
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
            $measurement->setLabResult($this);
        }

        return $this;
    }

    public function removeMeasurement(MeasurementEntity $measurement): static
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
