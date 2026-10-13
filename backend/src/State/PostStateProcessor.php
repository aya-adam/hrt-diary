<?php

namespace App\State;

use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\PostEntity;
use App\State\Trait\GraphQlOperationHelperTrait;
use App\State\Trait\MapperAwareInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class PostStateProcessor implements ProcessorInterface
{
    use MapperAwareInterface, GraphQlOperationHelperTrait;

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $removeProcessor,
        private readonly Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /**
         * @disregard P1013 Undefined method
         */
        $data = $this->dtoToEntity($data, $operation->getStateOptions()->getEntityClass(), $context['graphql_context']['previous_object']->id ?? null);

        if (self::isMutationCreate($operation)) {
            $data = $this->setOwner($data);
        } else {
            $this->assertOwnership($data);
        }

        $data = $this->setPublishedAt($data, $context);

        $result = $operation instanceof DeleteMutation
            ? $this->remove($data, $operation, $uriVariables, $context)
            : $this->persist($data, $operation, $uriVariables, $context);

        return $result
            ? $this->entityToDto($data, $operation->getClass())
            : $result;
    }

    private function persist(mixed $data, Operation $operation, array $uriVariables, array $context): mixed
    {
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        // Add further actions: logs, notifications etc.

        return $result;
    }

    private function remove(mixed $data, Operation $operation, array $uriVariables, array $context): mixed
    {
        $result = $this->removeProcessor->process($data, $operation, $uriVariables, $context);

        // Add further actions: logs, notifications etc.

        return $result;
    }

    private function assertOwnership(mixed $data): void
    {
        if ($data instanceof PostEntity && $data->getUser()->getUserIdentifier() !== $this->security->getUser()->getUserIdentifier()) {
            throw new AccessDeniedException('Access Denied.');
        }
    }

    private function setOwner(mixed $data): mixed
    {
        if ($data instanceof PostEntity) {
            $data->setUser($this->security->getUser());
        }
        return $data;
    }

    private function setPublishedAt(mixed $data, array $context): mixed
    {
        $published = $context['args']['input']['published'] ?? null;

        if (null !== $published) {
            if (true === $published && !$data->getPublishedAt()) {
                $data->setPublishedAt(new \DateTimeImmutable());
            }

            if (false === $published && $data->getPublishedAt()) {
                $data->setPublishedAt(null);
            }
        }

        return $data;
    }
}
