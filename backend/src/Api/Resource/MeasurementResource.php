<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Api\Dto\MeasurementCreateInput;
use App\Api\Dto\MeasurementUpdateInput;
use App\Entity\MeasurementEntity;
use App\ObjectMapper\ResourceTransformer;
use App\State\DtoStateProvider;
use App\State\MeasurementStateProcessor;
use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Measurement',
    stateOptions: new Options(entityClass: MeasurementEntity::class),
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
            input: MeasurementCreateInput::class,
            provider: DtoStateProvider::class,
            processor: MeasurementStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            input: MeasurementUpdateInput::class,
            provider: DtoStateProvider::class,
            processor: MeasurementStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            provider: DtoStateProvider::class,
            processor: MeasurementStateProcessor::class,
        ),
    ],
)]
#[Map(source: MeasurementEntity::class)]
final class MeasurementResource
{
    public ?Uuid $id = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?LabResultResource $labResult = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?UnitResource $unit = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?HormoneResource $hormone = null;

    #[Map(transform: ResourceTransformer::class)]
    public ?ReferenceRangeResource $referenceRange = null;

    public ?string $value = null;

    #[Map(source: 'labResult.drawnAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $drawnAt = null;

    #[Map(source: 'createdAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $createdAt = null;

    #[Map(source: 'updatedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $updatedAt = null;
}
