<?php

namespace App\ObjectMapper;

use App\Api\Dto\UserCreateInput;
use App\Api\Dto\UserUpdateInput;
use App\Entity\UserEntity;
use Override;
use Symfony\Component\ObjectMapper\TransformCallableInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements TransformCallableInterface<UserCreateInput|UserUpdateInput, UserEntity>
 */
final class UserPasswordTransformer implements TransformCallableInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[Override]
    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        if (!$target instanceof UserEntity) {
            throw new \LogicException(sprintf('Unexpected "%s" target. "%s" expected.', get_debug_type($target), UserEntity::class));
        }

        return $this->passwordHasher->hashPassword(
            $target,
            $value,
        );
    }
}
