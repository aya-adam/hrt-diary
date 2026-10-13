<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Api\Dto\PostCreateInput;
use App\Api\Dto\PostUpdateInput;
use App\Entity\PostEntity;
use App\State\DtoStateProvider;
use App\State\PostStateProcessor;
use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Post',
    stateOptions: new Options(entityClass: PostEntity::class),
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
            input: PostCreateInput::class,
            provider: DtoStateProvider::class,
            processor: PostStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            input: PostUpdateInput::class,
            provider: DtoStateProvider::class,
            processor: PostStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            provider: DtoStateProvider::class,
            processor: PostStateProcessor::class,
        ),
    ],
)]
#[Map(source: PostEntity::class)]
final class PostResource
{
    public ?Uuid $id = null;

    public ?string $title = null;

    public ?string $excerpt = null;

    public ?string $text = null;

    #[Map(source: 'publishedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $publishedAt = null;

    #[Map(source: 'createdAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $createdAt = null;

    #[Map(source: 'updatedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $updatedAt = null;
}
