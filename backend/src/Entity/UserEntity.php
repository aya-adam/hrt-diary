<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\UserRepository;
use App\State\UserStateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: 'email', identifierFieldNames: ['id'], message: 'This email is already in use.')]
#[ApiResource(
    shortName: 'User',
    operations: [],
    graphQlOperations: [
        new Query(
            normalizationContext: ['groups' => ['read']],
            security: 'is_granted(\'IS_AUTHENTICATED\')',
        ),
        new QueryCollection(
            normalizationContext: ['groups' => ['read']],
            security: 'is_granted(\'IS_AUTHENTICATED\')',
        ),
        new Mutation(
            name: 'create',
            normalizationContext: ['groups' => ['read']],
            denormalizationContext: ['groups' => ['user:create']],
            validationContext: ['groups' => ['Default', 'user:create']],
            processor: UserStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            normalizationContext: ['groups' => ['read']],
            denormalizationContext: ['groups' => ['user:update']],
            validationContext: ['groups' => ['Default', 'user:update']],
            security: 'is_granted(\'IS_AUTHENTICATED\')',
            processor: UserStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            security: 'is_granted(\'IS_AUTHENTICATED\')',
            processor: UserStateProcessor::class,
        ),
    ],
)]
class UserEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(['read', 'user:login:read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    /**
     * @var Collection<int, LabResultEntity>
     */
    #[Groups(['read'])]
    #[ORM\OneToMany(targetEntity: LabResultEntity::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $labResults;

    /**
     * @var Collection<int, MeasurementEntity>
     */
    #[Groups(['read'])]
    #[ORM\OneToMany(targetEntity: MeasurementEntity::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $measurements;

    /**
     * @var Collection<int, PostEntity>
     */
    #[Groups(['read'])]
    #[ORM\OneToMany(targetEntity: PostEntity::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $posts;

    /**
     * @var Collection<int, ReferenceRangeEntity>
     */
    #[Groups(['read'])]
    #[ORM\OneToMany(targetEntity: ReferenceRangeEntity::class, mappedBy: 'user')]
    private Collection $referenceRanges;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    #[Groups(['read', 'user:create', 'user:login:read', 'user:update'])]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[Groups(['read'])]
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var string The plain password (before hashing)
     */
    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Length(min: 8, groups: ['user:create', 'user:update'])]
    #[Groups(['user:create', 'user:update'])]
    private ?string $plainPassword = null;

    #[Groups(['read', 'user:login:read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['read', 'user:login:read'])]
    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->labResults = new ArrayCollection();
        $this->measurements = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->referenceRanges = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    /**
     * @return Collection<int, LabResultEntity>
     */
    public function getLabResults(): Collection
    {
        return $this->labResults;
    }

    public function addLabResult(LabResultEntity $labResult): static
    {
        if (!$this->labResults->contains($labResult)) {
            $this->labResults->add($labResult);
            $labResult->setUser($this);
        }

        return $this;
    }

    public function removeLabResult(LabResultEntity $labResult): static
    {
        if ($this->labResults->removeElement($labResult)) {
            // set the owning side to null (unless already changed)
            if ($labResult->getUser() === $this) {
                $labResult->setUser(null);
            }
        }

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
            $measurement->setUser($this);
        }

        return $this;
    }

    public function removeMeasurement(MeasurementEntity $measurement): static
    {
        if ($this->measurements->removeElement($measurement)) {
            // set the owning side to null (unless already changed)
            if ($measurement->getUser() === $this) {
                $measurement->setUser(null);
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
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(PostEntity $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
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
            $referenceRange->setUser($this);
        }

        return $this;
    }

    public function removeReferenceRange(ReferenceRangeEntity $referenceRange): static
    {
        if ($this->referenceRanges->removeElement($referenceRange)) {
            // set the owning side to null (unless already changed)
            if ($referenceRange->getUser() === $this) {
                $referenceRange->setUser(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }
}
