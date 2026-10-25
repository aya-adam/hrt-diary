<?php

namespace App\Security\Authentication;

use App\Entity\RefreshTokenEntity;
use App\Entity\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Clock\DatePoint;
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
        #[Autowire('%app.jwt_access_token_cookie_path%')]
        private string $accessTokenCookiePath,
        #[Autowire('%app.jwt_refresh_token_cookie_path%')]
        private string $refreshTokenCookiePath,
    ) {}

    public function createAccessTokenCookie(UserEntity $user): Cookie
    {
        $token = $this->jwtManager->create($user);

        return Cookie::create($this->accessTokenCookieName)
            ->withValue($token)
            ->withExpires(new DatePoint("+{$this->accessTokenTtl} seconds"))
            ->withPath($this->accessTokenCookiePath)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('strict')
            ->withPartitioned(false);
    }

    public function createRefreshTokenCookie(UserEntity $user, ?string $previousIdentifier = null): Cookie
    {
        $this->revokeExistingRefreshTokens($previousIdentifier ?? $user->getUserIdentifier());

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return Cookie::create($this->refreshTokenCookieName)
            ->withValue($refreshToken->getRefreshToken())
            ->withExpires($refreshToken->getValid())
            ->withPath($this->refreshTokenCookiePath)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('strict')
            ->withPartitioned(false);
    }
    
    public function createClearingAccessTokenCookie(): Cookie
    {
        return Cookie::create($this->accessTokenCookieName)
            ->withValue(null)
            ->withExpires(new DatePoint('1 second'))
            ->withPath($this->accessTokenCookiePath)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('strict')
            ->withPartitioned(false);
    }

    public function createClearingRefreshTokenCookie(?UserEntity $user = null): Cookie
    {
        if ($user) {
            $this->revokeExistingRefreshTokens($user->getUserIdentifier());
        }

        return Cookie::create($this->refreshTokenCookieName)
            ->withValue(null)
            ->withExpires(new DatePoint('1 second'))
            ->withPath($this->accessTokenCookiePath)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('strict')
            ->withPartitioned(false);
    }

    private function revokeExistingRefreshTokens(string $identifier): void
    {
        $repository = $this->entityManager->getRepository(RefreshTokenEntity::class);

        foreach ($repository->findBy(['username' => $identifier]) as $old) {
            $this->refreshTokenManager->delete($old);
        }
    }
}
