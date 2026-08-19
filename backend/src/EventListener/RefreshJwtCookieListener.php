<?php

namespace App\EventListener;

use App\Entity\User;
use App\Security\Authentication\JwtCookieFactory;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class RefreshJwtCookieListener
{
    public function __construct(
        private JwtCookieFactory $cookieFactory,
    ) {}

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $user = $request->attributes->get('_user_for_new_cookies');
        if (!$user instanceof User) {
            return;
        }

        $previousIdentifier = $request->attributes->get('_previous_user_identifier');

        $response = $event->getResponse();
        $response->headers->setCookie($this->cookieFactory->createAccessTokenCookie($user));
        $response->headers->setCookie($this->cookieFactory->createRefreshTokenCookie($user, $previousIdentifier));
    }
}
