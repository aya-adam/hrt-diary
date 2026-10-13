<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Api\Dto\ReferenceRangeCreateInput;
use App\Api\Dto\ReferenceRangeUpdateInput;
use App\Entity\ReferenceRangeEntity;
use App\ObjectMapper\ResourceTransformer;
use App\State\DtoStateProvider;
use App\State\ReferenceRangeStateProcessor;
use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'ReferenceRange',
    stateOptions: new Options(entityClass: ReferenceRangeEntity::class),
    security: 'is_granted(\'IS_AUTHENTICATED\')',
    operations: [],
    graphQlOperations: [
        new Query(
            provider: DtoStateProvider::class,
        ),
        new QueryCollection(
            provider: DtoStateProvider::class,
        ),
        new Mutation(
            name: 'create',
            input: ReferenceRangeCreateInput::class,
            provider: DtoStateProvider::class,
            processor: ReferenceRangeStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            input: ReferenceRangeUpdateInput::class,
            provider: DtoStateProvider::class,
            processor: ReferenceRangeStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            provider: DtoStateProvider::class,
            processor: ReferenceRangeStateProcessor::class,
        ),
    ],
)]
#[Map(source: ReferenceRangeEntity::class)]
final class ReferenceRangeResource
{
    public ?Uuid $id = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?HormoneResource $hormone = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?UnitResource $unit = null;

    public ?string $name = null;

    public ?string $lowerLimit = null;

    public ?string $upperLimit = null;

    public ?string $notes = null;

    #[Map(source: 'createdAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $createdAt = null;

    #[Map(source: 'updatedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $updatedAt = null;
}
