<?php

namespace App\ObjectMapper;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

final class ResourceTransformer implements TransformCallableInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectMapperInterface $mapper,
        private readonly ResourceMetadataCollectionFactoryInterface $factory,
    ) {}

    #[Override]
    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        if (null === $value) {
            return $value;
        }

        if (!$value->id) {
            throw new \LogicException('Value has no `id` property.');
        }

        $entityClass = $this->resolveEntityClass($value::class);
        $entity = $this->entityManager->getRepository($entityClass)->find($value->id);

        if (null === $entity) {
            throw new \Doctrine\ORM\EntityNotFoundException(sprintf('%s with the id `%s` not found.', $value::class, $value->id->toRfc4122()));
        }

        return $this->mapper->map($value, $entity);
    }

    private function resolveEntityClass(string $resourceClass): string
    {
        try {
            $resource = $this->factory->create($resourceClass)[0] ?? null;
        } catch (\ApiPlatform\Metadata\Exception\ResourceClassNotFoundException) {
            $resource = null;
        }

        $stateOptions = $resource?->getStateOptions();

        if (!$stateOptions instanceof Options) {
            throw new \LogicException(sprintf('Could not resolve entity class of `%s`', $resourceClass));
        }

        return $stateOptions->getEntityClass();
    }
}
