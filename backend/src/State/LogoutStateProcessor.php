<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\AuthOutput;
use App\Security\Authentication\AuthCookieQueue;
use App\Security\Authentication\JwtCookieFactory;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

class LogoutStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly AuthCookieQueue $cookieQueue,
        private readonly JwtCookieFactory $cookieFactory,
        #[Autowire('%app.jwt_refresh_token_cookie_name%')]
        private string $refreshTokenCookieName,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthOutput
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

        return new AuthOutput('Successfully logged out.');
    }
}
