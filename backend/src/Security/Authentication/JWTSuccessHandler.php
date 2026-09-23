<?php

namespace App\Security\Authentication;

use ApiPlatform\Metadata\IriConverterInterface;
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
        private IriConverterInterface $iriConverter,
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
        $event->getResponse()->headers->set('Content-Type', 'application/json');

        $cookies = $event->getResponse()->headers->getCookies();
        $responseCookies = [];
        foreach ($cookies as $cookie) {
            $responseCookies[$cookie->getName()] = [
                'path' => $cookie->getPath(),
                'expiresAt' => $cookie->getExpiresTime(),
            ];
        };

        $serializedUser = $this->serializer->serialize($user, 'json', [
            'groups' => ['user:login:read'],
            'resource_class' => User::class,
        ]);

        $jsonUser = json_decode($serializedUser, true);
        if (isset($jsonUser['id'])) {
            $jsonUser['id'] = $this->iriConverter->getIriFromResource($user);
        }

        $event->setData([
            'user' => $jsonUser,
            'cookies' => $responseCookies,
        ]);
    }
}
