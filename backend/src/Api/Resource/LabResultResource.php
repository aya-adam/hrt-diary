<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Api\Dto\LabResultCreateInput;
use App\Api\Dto\LabResultUpdateInput;
use App\Entity\LabResultEntity;
use App\State\DtoStateProvider;
use App\State\LabResultStateProcessor;
use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Transform\MapCollection;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'LabResult',
    stateOptions: new Options(entityClass: LabResultEntity::class),
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
            input: LabResultCreateInput::class,
            provider: DtoStateProvider::class,
            processor: LabResultStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            input: LabResultUpdateInput::class,
            provider: DtoStateProvider::class,
            processor: LabResultStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            provider: DtoStateProvider::class,
            processor: LabResultStateProcessor::class,
        ),
    ],
)]
#[Map(source: LabResultEntity::class)]
final class LabResultResource
{
    public ?Uuid $id = null;

    /**
     * @var PostResource[]
     */
    #[Map(source: 'posts', transform: new MapCollection())]
    public ?array $posts = [];

    public ?string $notes = null;

    #[Map(source: 'publishedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $publishedAt = null;

    #[Map(source: 'drawnAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $drawnAt = null;

    #[Map(source: 'createdAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $createdAt = null;

    #[Map(source: 'updatedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $updatedAt = null;
}
