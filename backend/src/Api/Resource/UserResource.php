<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Api\Dto\UserCreateInput;
use App\Api\Dto\UserUpdateInput;
use App\Api\Dto\UserUpdatePasswordInput;
use App\Entity\UserEntity;
use App\State\DtoStateProvider;
use App\State\UserStateProcessor;
use App\Util\DateTimeImmutableTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'User',
    stateOptions: new Options(entityClass: UserEntity::class),
    operations: [],
    graphQlOperations: [
        new Query(
            provider: DtoStateProvider::class,
            security: 'is_granted(\'IS_AUTHENTICATED\')',
        ),
        new QueryCollection(
            provider: DtoStateProvider::class,
            security: 'is_granted(\'IS_AUTHENTICATED\')',
        ),
        new Mutation(
            name: 'create',
            input: UserCreateInput::class,
            provider: DtoStateProvider::class,
            processor: UserStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            input: UserUpdateInput::class,
            provider: DtoStateProvider::class,
            processor: UserStateProcessor::class,
            security: 'is_granted(\'IS_AUTHENTICATED\')',
        ),
        new Mutation(
            name: 'updatePassword',
            input: UserUpdatePasswordInput::class,
            provider: DtoStateProvider::class,
            processor: UserStateProcessor::class,
            security: 'is_granted(\'IS_AUTHENTICATED\')',
        ),
        new DeleteMutation(
            name: 'delete',
            provider: DtoStateProvider::class,
            processor: UserStateProcessor::class,
            security: 'is_granted(\'IS_AUTHENTICATED\')',
        ),
    ],
)]
#[Map(source: UserEntity::class)]
final class UserResource
{
    public ?Uuid $id = null;

    public ?string $email = null;

    public ?array $roles = [];

    #[Map(source: 'createdAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $createdAt = null;

    #[Map(source: 'updatedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $updatedAt = null;
}
