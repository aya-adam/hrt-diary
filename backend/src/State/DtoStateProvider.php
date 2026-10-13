<?php

namespace App\State;

use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class DtoStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly ObjectMapperInterface $mapper,
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private readonly ProviderInterface $collectionProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private readonly ProviderInterface $itemProvider,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|iterable|null
    {
        if ($operation instanceof QueryCollection) {
            return $this->provideCollection($operation, $uriVariables, $context);
        }

        if ($operation instanceof Query) {
            return $this->provideItem($operation, $uriVariables, $context);
        }

        return $this->provide($operation, $uriVariables, $context);
    }

    private function provideItem(Operation $operation, array $uriVariables, array $context): object|null
    {
        $result = $this->itemProvider->provide($operation, $uriVariables, $context);

        return $result
            ? $this->mapper->map($result)
            : null;
    }

    private function provideCollection(Operation $operation, array $uriVariables, array $context): iterable
    {
        $results = $this->collectionProvider->provide($operation, $uriVariables, $context);

        $mappedResults = [];
        foreach ($results as $item) {
            $mappedResults[] = $this->mapper->map($item);
        }

        return $mappedResults;
    }
}
