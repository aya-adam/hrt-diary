<?php

namespace App\State\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\Attribute\Required;

trait MapperAwareInterface
{
    private EntityManagerInterface $entityManager;
    private ObjectMapperInterface $mapper;

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    #[Required]
    public function setMapper(ObjectMapperInterface $mapper): void
    {
        $this->mapper = $mapper;
    }

    /**
     * Map dto to entity by providing a source and a target class.
     * 
     * By providing an ID of an existing entry, a `find($id)` operation is made first to fetch the
     * existing entry of the `$targetClass` to which the `$source` data is mapped to then.
     */
    public function dtoToEntity(mixed $source, string $targetClass, ?Uuid $id = null): object
    {
        $entity = $id
            ? $this->entityManager->find($targetClass, $id)
            : null;

        return $this->mapper->map($source, $entity ?? $targetClass);
    }

    /**
     * Map entity to dto by providing a source and a target class.
     */
    public function entityToDto(mixed $source, string $targetClass): object
    {
        return $this->mapper->map($source, $targetClass);
    }
}
