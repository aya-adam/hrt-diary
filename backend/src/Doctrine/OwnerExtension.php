<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Operation;
use App\Entity\LabResultEntity;
use App\Entity\MeasurementEntity;
use App\Entity\PostEntity;
use App\Entity\ReferenceRangeEntity;
use App\Entity\UserEntity;
use Override;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\SecurityBundle\Security;

final class OwnerExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
    ) {}

    #[Override]
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addOwnerFilter($queryBuilder, $resourceClass, $operation);
    }

    #[Override]
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addOwnerFilter($queryBuilder, $resourceClass, $operation);
    }

    private function addOwnerFilter(QueryBuilder $queryBuilder, string $resourceClass, ?Operation $operation): void
    {
        if (!in_array($resourceClass, $this->getSupportedClasses(), true) || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof UserEntity) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ($resourceClass === UserEntity::class) {
            $queryBuilder->andWhere(sprintf('%s.id = :user', $alias));
        } elseif ($resourceClass === ReferenceRangeEntity::class && ($operation instanceof Query || $operation instanceof QueryCollection)) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(sprintf('%s.user = :user', $alias), sprintf('%s.user IS NULL', $alias)));
        } else {
            $queryBuilder->andWhere(sprintf('%s.user = :user', $alias));
        }

        $queryBuilder->setParameter('user', $user->getId(), UuidType::NAME);
    }

    private function getSupportedClasses(): array
    {
        return [
            LabResultEntity::class,
            MeasurementEntity::class,
            PostEntity::class,
            ReferenceRangeEntity::class,
            UserEntity::class,
        ];
    }
}
