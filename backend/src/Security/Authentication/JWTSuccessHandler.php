<?php

namespace App\Security\Authentication;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\SerializerInterface;

class JWTSuccessHandler implements EventSubscriberInterface
{
    public function __construct(
        private JwtCookieFactory $cookieFactory,
        private SerializerInterface $serializer,
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
        $event->getResponse()->headers->set('Content-Type', 'application/ld+json; charset=utf-8');

        $userJsonLd = $this->serializer->serialize($user, 'jsonld', [
            'groups' => ['user:read'],
            'resource_class' => User::class,
        ]);

        $event->setData(json_decode($userJsonLd, true));
    }
}
