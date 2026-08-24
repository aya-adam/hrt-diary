<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\LabResult;
use App\Entity\Post;
use App\Entity\User;
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
        $this->addOwnerFilter($queryBuilder, $resourceClass);
    }

    #[Override]
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addOwnerFilter($queryBuilder, $resourceClass);
    }

    private function addOwnerFilter(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (!in_array($resourceClass, $this->getSupportedClasses(), true) || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $resourceClass === User::class
            ? $queryBuilder->andWhere(sprintf('%s.id = :user', $alias))
            : $queryBuilder->andWhere(sprintf('%s.user = :user', $alias));
        $queryBuilder->setParameter('user', $user->getId(), UuidType::NAME);
    }

    private function getSupportedClasses(): array
    {
        return [
            LabResult::class,
            Post::class,
            User::class,
        ];
    }
}
