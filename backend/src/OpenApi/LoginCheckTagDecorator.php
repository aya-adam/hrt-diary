<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class LoginCheckTagDecorator implements OpenApiFactoryInterface
{
    private const OLD_TAG = 'Login Check';
    private const NEW_TAG = 'Authentication';

    /**
     * HTTP methods to inspect on each PathItem, paired with their PathItem
     * getter/wither method name suffixes (get/withGet, post/withPost, ...).
     */
    private const METHODS = ['Get', 'Post', 'Put', 'Patch', 'Delete', 'Options', 'Head', 'Trace'];

    public function __construct(private readonly OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            $updatedPathItem = $this->retagPathItem($pathItem);

            if ($updatedPathItem !== $pathItem) {
                // Paths::addPath overwrites the existing entry for this path.
                $paths->addPath($path, $updatedPathItem);
            }
        }

        return $openApi;
    }

    private function retagPathItem(PathItem $pathItem): PathItem
    {
        foreach (self::METHODS as $method) {
            $getter = 'get' . $method;
            $wither = 'with' . $method;

            /** @var Operation|null $operation */
            $operation = $pathItem->{$getter}();

            if (!$operation instanceof Operation) {
                continue;
            }

            if (!\in_array(self::OLD_TAG, $operation->getTags(), true)) {
                continue;
            }

            $newTags = array_map(
                static fn(string $tag): string => self::OLD_TAG === $tag ? self::NEW_TAG : $tag,
                $operation->getTags(),
            );

            $pathItem = $pathItem->{$wither}($operation->withTags($newTags));
        }

        return $pathItem;
    }
}
