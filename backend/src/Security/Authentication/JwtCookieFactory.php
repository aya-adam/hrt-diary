<?php

namespace App\Security\Authentication;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;

final class JwtCookieFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        #[Autowire('%lexik_jwt_authentication.token_ttl%')]
        private int $accessTokenTtl,
        #[Autowire('%gesdinet_jwt_refresh_token.ttl%')]
        private int $refreshTokenTtl,
        #[Autowire('%app.jwt_access_token_cookie_name%')]
        private string $accessTokenCookieName,
        #[Autowire('%app.jwt_refresh_token_cookie_name%')]
        private string $refreshTokenCookieName,
    ) {}

    public function createAccessTokenCookie(User $user): Cookie
    {
        $token = $this->jwtManager->create($user);

        return Cookie::create($this->accessTokenCookieName)
            ->withValue($token)
            ->withExpires(new \DateTimeImmutable("+{$this->accessTokenTtl} seconds"))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('none')
            ->withPartitioned(true);
    }

    public function createRefreshTokenCookie(User $user, ?string $previousIdentifier = null): Cookie
    {
        $this->revokeExistingRefreshTokens($previousIdentifier ?? $user->getUserIdentifier());

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return Cookie::create($this->refreshTokenCookieName)
            ->withValue($refreshToken->getRefreshToken())
            ->withExpires($refreshToken->getValid())
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('none')
            ->withPartitioned(true);
    }

    private function revokeExistingRefreshTokens(string $identifier): void
    {
        $repository = $this->entityManager->getRepository(RefreshToken::class);

        foreach ($repository->findBy(['username' => $identifier]) as $old) {
            $this->refreshTokenManager->delete($old);
        }
    }
}
