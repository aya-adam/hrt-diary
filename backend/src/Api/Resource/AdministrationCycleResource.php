<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Api\Dto\AdministrationCycleCreateInput;
use App\Api\Dto\AdministrationCycleUpdateInput;
use App\Entity\AdministrationCycleEntity;
use App\State\AdministrationCycleStateProcessor;
use App\State\DtoStateProvider;
use App\Util\DateImmutableTransformer;
use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'AdministrationCycle',
    stateOptions: new Options(entityClass: AdministrationCycleEntity::class),
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
            input: AdministrationCycleCreateInput::class,
            provider: DtoStateProvider::class,
            processor: AdministrationCycleStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            input: AdministrationCycleUpdateInput::class,
            provider: DtoStateProvider::class,
            processor: AdministrationCycleStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            provider: DtoStateProvider::class,
            processor: AdministrationCycleStateProcessor::class,
        ),
    ],
)]
#[Map(source: AdministrationCycleEntity::class)]
final class AdministrationCycleResource
{
    public ?Uuid $id = null;

    public ?string $name = null;

    #[Map(source: 'startingDate', transform: [DateImmutableTransformer::class, 'format'])]
    public ?string $startingDate = null;

    public array $locations = [];

    public ?int $startingIndex = null;

    #[Map(source: 'createdAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $createdAt = null;

    #[Map(source: 'updatedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $updatedAt = null;
}
