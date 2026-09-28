<?php

namespace App\EventListener;

use App\Security\Authentication\AuthCookieQueue;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class CookieResponseListener
{
    #[AsEventListener]
    public function onResponseEvent(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $cookies = $event->getRequest()->attributes->get(AuthCookieQueue::ATTRIBUTE, []);

        foreach ($cookies as $cookie) {
            $event->getResponse()->headers->setCookie($cookie);
        }
    }
}
