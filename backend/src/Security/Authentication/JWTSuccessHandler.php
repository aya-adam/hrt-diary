<?php

namespace App\Security\Authentication;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

class JWTSuccessHandler implements EventSubscriberInterface
{
    public function __construct(
        private int $tokenTtl,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data = $event->getData();
        $token = $data['token'] ?? null;

        if (!$token) {
            return;
        }

        $response = $event->getResponse();

        $cookie = Cookie::create('access_token')
            ->withValue($token)
            ->withExpires(new \DateTimeImmutable("+{$this->tokenTtl} seconds"))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('none')
            ->withPartitioned(true);

        $response->headers->setCookie($cookie);

        $event->setData([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }
}
