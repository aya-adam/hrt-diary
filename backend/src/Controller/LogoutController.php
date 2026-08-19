<?php

namespace App\Controller;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Routing\Attribute\Route;

final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
    ) {}

    // #[Route('/auth/logout', name: 'api_logout')]
    public function __invoke(Request $request): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Successfully logged out'], Response::HTTP_OK);

        $refreshToken = $request->cookies->get('refresh_token');
        if ($refreshToken) {
            $refreshTokenObject = $this->refreshTokenManager->get($refreshToken);
            if ($refreshTokenObject) {
                $this->refreshTokenManager->delete($refreshTokenObject);
            }
        }

        $response->headers->setCookie(
            Cookie::create('access_token')
                ->withValue('')
                ->withExpires(new \DateTimeImmutable('-1 year'))
                ->withPath('/')
                ->withDomain(null)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('none')
        );
        
        $response->headers->setCookie(
            Cookie::create('refresh_token')
                ->withValue('')
                ->withExpires(new \DateTimeImmutable('-1 year'))
                ->withPath('/')
                ->withDomain(null)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('none')
        );

        return $response;
    }
}
