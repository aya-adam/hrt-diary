<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Entity\HormoneEntity;
use App\ObjectMapper\ResourceTransformer;
use App\State\DtoStateProvider;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Hormone',
    stateOptions: new Options(entityClass: HormoneEntity::class),
    security: 'is_granted(\'IS_AUTHENTICATED\')',
    operations: [],
    graphQlOperations: [
        new Query(
            provider: DtoStateProvider::class,
        ),
        new QueryCollection(
            provider: DtoStateProvider::class,
        ),
    ],
)]
#[Map(source: HormoneEntity::class)]
final class HormoneResource
{
    public ?Uuid $id = null;

    public ?string $name = null;

    public ?string $commonName = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?UnitResource $defaultUnit = null;
}
