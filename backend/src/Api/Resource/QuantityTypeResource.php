<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Entity\QuantityTypeEntity;
use App\State\DtoStateProvider;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'QuantityType',
    stateOptions: new Options(entityClass: QuantityTypeEntity::class),
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
#[Map(source: QuantityTypeEntity::class)]
final class QuantityTypeResource
{
    public ?Uuid $id = null;

    public ?string $label = null;

    public ?string $slug = null;
}
