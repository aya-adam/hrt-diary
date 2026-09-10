<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Measurement;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MeasurementStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $removeProcessor,
        private readonly Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof Post) {
            $data = $this->setOwner($data);
        } else {
            $this->assertOwnership($data);
        }

        $data = $this->setUnit($data);

        return $operation instanceof Delete
            ? $this->remove($data, $operation, $uriVariables, $context)
            : $this->persist($data, $operation, $uriVariables, $context);
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
        if ($data instanceof Measurement && $data->getUser()->getUserIdentifier() !== $this->security->getUser()->getUserIdentifier()) {
            throw new AccessDeniedException();
        }
    }

    private function setOwner(mixed $data): mixed
    {
        if ($data instanceof Measurement) {
            $data->setUser($this->security->getUser());
        }
        return $data;
    }
    
    private function setUnit(mixed $data): mixed
    {
        if ($data instanceof Measurement && !$data->getUnit()) {
            $data->setUnit($data->getHormone()->getDefaultUnit());
        }
        return $data;
    }
}
