<?php

namespace App\State;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\AuthOutput;
use App\Api\Dto\LoginInput;
use App\Repository\UserRepository;
use App\Security\Authentication\AuthCookieQueue;
use App\Security\Authentication\JwtCookieFactory;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.symfony.iri_converter')]
        private readonly IriConverterInterface $iriConverter,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JwtCookieFactory $cookieFactory,
        private readonly AuthCookieQueue $cookieQueue,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthOutput
    {
        if (!$data instanceof LoginInput) {
            throw new RuntimeException(sprintf('Invalid data type "%s". "%s" required.', get_debug_type($data), LoginInput::class));
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

        return new AuthOutput(
            'Successfully authenticated.',
            $this->iriConverter->getIriFromResource($user),
            $user->getUserIdentifier(),
            $accessTokenCookie,
        );
    }
}
