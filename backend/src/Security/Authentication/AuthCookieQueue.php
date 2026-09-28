<?php

namespace App\Security\Authentication;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;

final class AuthCookieQueue
{
    public const ATTRIBUTE = '_cookies_to_set';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function queueAuthCookies(Cookie ...$cookies): void
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            return;
        }

        $request->attributes->set(
            self::ATTRIBUTE,
            array_merge($request->attributes->get(self::ATTRIBUTE, []), $cookies),
        );
    }
}
