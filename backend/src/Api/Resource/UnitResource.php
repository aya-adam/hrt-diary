<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Entity\UnitEntity;
use App\ObjectMapper\ResourceTransformer;
use App\State\DtoStateProvider;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Unit',
    stateOptions: new Options(entityClass: UnitEntity::class),
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
#[Map(source: UnitEntity::class)]
final class UnitResource
{
    public ?Uuid $id = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?QuantityTypeResource $quantityType = null;

    public ?string $symbol = null;

    public ?string $label = null;
}
