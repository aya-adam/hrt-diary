<?php

namespace App\State;

use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Security\Authentication\AuthCookieQueue;
use App\Security\Authentication\JwtCookieFactory;
use App\State\Trait\GraphQlOperationHelperTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserStateProcessor implements ProcessorInterface
{
    use GraphQlOperationHelperTrait;

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $removeProcessor,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private readonly AuthCookieQueue $cookieQueue,
        private readonly JwtCookieFactory $cookieFactory,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!self::isMutationCreate($operation)) {
            $this->assertOwnership($data);
        }

        if (self::isMutationCreate($operation) || self::isMutationUpdate($operation)) {
            $data = $this->hashUserPassword($data);
        }

        return $operation instanceof DeleteMutation
            ? $this->remove($data, $operation, $uriVariables, $context)
            : $this->persist($data, $operation, $uriVariables, $context);
    }

    private function persist(mixed $data, Operation $operation, array $uriVariables, array $context): mixed
    {
        // Must be captured BEFORE the persist processor flushes, since flush resets Doctrine's tracked change sets.
        $previousEmail = $this->getPreviousEmail($data);

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if (null !== $previousEmail) {
            $accessTokenCookie = $this->cookieFactory->createAccessTokenCookie($data);
            $refreshTokenCookie = $this->cookieFactory->createRefreshTokenCookie($data, $previousEmail);

            $this->cookieQueue->queueAuthCookies($accessTokenCookie, $refreshTokenCookie);
        }

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
        if ($data instanceof User && $data->getUserIdentifier() !== $this->security->getUser()->getUserIdentifier()) {
            throw new AccessDeniedException('Access Denied.');
        }
    }

    private function hashUserPassword(mixed $data): mixed
    {
        if ($data->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $data,
                $data->getPlainPassword()
            );
            $data->setPassword($hashedPassword);

            // To avoid leaving sensitive data like the plain password in memory or logs, we manually clear it after hashing.
            $data->setPlainPassword(null);
        }

        return $data;
    }

    /**
     * Returns the pre-update email if it changed, or null if this is a new
     * (not-yet-persisted) User or the email is unchanged. Relies on Doctrine's
     * UnitOfWork rather than API Platform's previous_data, since previous_data
     * is only populated for REST/JSON-LD operations, not GraphQL.
     */
    private function getPreviousEmail(mixed $data): ?string
    {
        if (!$data instanceof User || !$this->entityManager->contains($data)) {
            return null;
        }

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->recomputeSingleEntityChangeSet($this->entityManager->getClassMetadata(User::class), $data);

        $changeset = $unitOfWork->getEntityChangeSet($data);

        return isset($changeset['email']) ? $changeset['email'][0] : null;
    }
}
