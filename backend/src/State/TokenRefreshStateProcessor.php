<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\AuthPayload;
use App\Repository\UserRepository;
use App\Security\Authentication\AuthCookieQueue;
use App\Security\Authentication\JwtCookieFactory;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TokenRefreshStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly AuthCookieQueue $cookieQueue,
        private readonly JwtCookieFactory $cookieFactory,
        private readonly UserRepository $userRepository,
        #[Autowire('%app.jwt_refresh_token_cookie_name%')]
        private string $refreshTokenCookieName,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthPayload
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

        return new AuthPayload($user, $accessTokenCookie, 'Token successfully refreshed.');
    }
}
