<?php

namespace App\Api\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Api\Dto\EventCreateInput;
use App\Api\Dto\EventUpdateInput;
use App\Entity\EventEntity;
use App\State\DtoStateProvider;
use App\State\EventStateProcessor;
use App\Util\DateIntervalTransformer;
use App\Util\DateTimeImmutableTransformer;
use DateInterval;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Event',
    stateOptions: new Options(entityClass: EventEntity::class),
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
            input: EventCreateInput::class,
            provider: DtoStateProvider::class,
            processor: EventStateProcessor::class,
        ),
        new Mutation(
            name: 'update',
            input: EventUpdateInput::class,
            provider: DtoStateProvider::class,
            processor: EventStateProcessor::class,
        ),
        new DeleteMutation(
            name: 'delete',
            provider: DtoStateProvider::class,
            processor: EventStateProcessor::class,
        ),
    ],
)]
#[Map(source: EventEntity::class)]
final class EventResource
{
    public ?Uuid $id = null;

    public ?string $name = null;

    #[Map(source: 'date', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $date = null;

    #[Map(source: 'repeatInterval', transform: [DateIntervalTransformer::class, 'format'])]
    public ?string $repeatInterval = null;

    #[Map(source: 'earlyNotification', transform: [DateIntervalTransformer::class, 'format'])]
    public ?string $earlyNotification = null;

    public ?bool $critical = null;

    #[Map(source: 'createdAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $createdAt = null;

    #[Map(source: 'updatedAt', transform: [DateTimeImmutableTransformer::class, 'format'])]
    public ?string $updatedAt = null;

    public static function formatCritical(?bool $value, object $source): ?bool
    {
        if ($source instanceof EventEntity) {
            return $value;
        }

        if ($source instanceof EventCreateInput || $source instanceof EventUpdateInput) {
            return $value ?? false;
        }

        throw new \LogicException(sprintf('Expected argument `$source` of type %s|%s|%s. `%s` given.', EventEntity::class, EventCreateInput::class, EventUpdateInput::class, get_debug_type($source)));
    }
}
