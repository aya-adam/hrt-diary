<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\AuthPayload;
use App\Api\Resource\Login;
use App\Repository\UserRepository;
use App\Security\Authentication\AuthCookieQueue;
use App\Security\Authentication\JwtCookieFactory;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JwtCookieFactory $cookieFactory,
        private readonly AuthCookieQueue $cookieQueue,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthPayload
    {
        if (!$data instanceof Login) {
            throw new RuntimeException(sprintf('Invalid data type "%s". App\Api\Resource\Login require.', get_debug_type($data)));
        }

        $user = $this->userRepository->findOneBy(['email' => $data->email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data->password)) {
            throw new UnauthorizedHttpException(
                'Cookie realm="api", error="missing_token", error_description="Authentication required"',
                'Invalid credentials.'
            );
        }

        $accessTokenCookie = $this->cookieFactory->createAccessTokenCookie($user);
        $refreshTokenCookie = $this->cookieFactory->createRefreshTokenCookie($user);

        $this->cookieQueue->queueAuthCookies($accessTokenCookie, $refreshTokenCookie);

        return new AuthPayload($user, $accessTokenCookie, 'Successfully authenticated.');
    }
}
