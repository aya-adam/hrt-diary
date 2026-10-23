<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\LoginInput;
use App\Api\Resource\AuthResource;
use App\Api\Resource\UserResource;
use App\Repository\UserRepository;
use App\Security\Authentication\AuthCookieQueue;
use App\Security\Authentication\JwtCookieFactory;
use App\State\Trait\GraphQlOperationHelperTrait;
use App\State\Trait\MapperAwareInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthStateProcessor implements ProcessorInterface
{
    use MapperAwareInterface, GraphQlOperationHelperTrait;

    public function __construct(
        private readonly AuthCookieQueue $cookieQueue,
        private readonly JwtCookieFactory $cookieFactory,
        #[Autowire('%app.jwt_refresh_token_cookie_name%')]
        private string $refreshTokenCookieName,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly RequestStack $requestStack,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthResource
    {
        if ($this->isMutationAuthLogin($operation)) {
            return $this->processAuthLogin($data, $operation, $uriVariables, $context);
        }

        if ($this->isMutationAuthRefresh($operation)) {
            return $this->processAuthRefresh($data, $operation, $uriVariables, $context);
        }

        if ($this->isMutationAuthRevoke($operation)) {
            return $this->processAuthRevoke($data, $operation, $uriVariables, $context);
        }

        throw new \LogicException(sprintf('Unexpected operation "%s%s". "getAuth" | "refreshAuth" | "revokeAuth" expected.', $operation->getName(), $operation->getShortName()));
    }

    private function processAuthLogin(mixed $data, Operation $operation, array $uriVariables, array $context): AuthResource
    {
        if (!$data instanceof LoginInput) {
            throw new \RuntimeException(sprintf('Invalid data type "%s". "%s" required.', get_debug_type($data), LoginInput::class));
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

        return new AuthResource(
            'Successfully authenticated.',
            $accessTokenCookie,
            $this->entityToDto($user, UserResource::class),
        );
    }

    private function processAuthRefresh(mixed $data, Operation $operation, array $uriVariables, array $context): AuthResource
    {
        $request = $this->requestStack->getMainRequest();
        $tokenValue = $request?->cookies->get($this->refreshTokenCookieName);

        if (!$tokenValue) {
            throw new UnauthorizedHttpException(
                'Cookie realm="api", error="missing_token", error_description="Authentication required"',
                'No refresh token cookie present.'
            );
        }

        $refreshToken = $this->refreshTokenManager->get($tokenValue);

        if (!$refreshToken || !$refreshToken->isValid()) {
            $user = $refreshToken?->getUsername()
                ? $this->userRepository->findOneBy(['email' => $refreshToken->getUsername()])
                : null;

            $this->cookieQueue->queueAuthCookies(
                $this->cookieFactory->createClearingAccessTokenCookie(),
                $this->cookieFactory->createClearingRefreshTokenCookie($user),
            );

            throw new UnauthorizedHttpException(
                'Cookie realm="api", error="invalid_token", error_description="The access token has expired"',
                'Refresh token is invalid or has expired.'
            );
        }

        $user = $this->userRepository->findOneBy(['email' => $refreshToken->getUsername()]);

        $accessTokenCookie = $this->cookieFactory->createAccessTokenCookie($user);
        $refreshTokenCookie = $this->cookieFactory->createRefreshTokenCookie($user);

        $this->cookieQueue->queueAuthCookies($accessTokenCookie, $refreshTokenCookie);

        return new AuthResource(
            'Token successfully refreshed.',
            $accessTokenCookie,
            $this->entityToDto($user, UserResource::class),
        );
    }

    private function processAuthRevoke(mixed $data, Operation $operation, array $uriVariables, array $context): AuthResource
    {
        $request = $this->requestStack->getMainRequest();
        $tokenValue = $request?->cookies->get($this->refreshTokenCookieName);

        if ($tokenValue) {
            $refreshToken = $this->refreshTokenManager->get($tokenValue);
            if ($refreshToken) {
                $this->refreshTokenManager->delete($refreshToken);
            }
        }

        $this->cookieQueue->queueAuthCookies(
            $this->cookieFactory->createClearingAccessTokenCookie(),
            $this->cookieFactory->createClearingRefreshTokenCookie(),
        );

        return new AuthResource('Successfully logged out.');
    }
}
