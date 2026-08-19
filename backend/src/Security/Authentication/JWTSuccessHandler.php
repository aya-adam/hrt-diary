<?php

namespace App\Security\Authentication;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTSuccessHandler implements EventSubscriberInterface
{
    public function __construct(
        private JwtCookieFactory $cookieFactory,
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

        $event->getResponse()->headers->setCookie($this->cookieFactory->createAccessTokenCookie($user));

        $event->setData([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }
}
