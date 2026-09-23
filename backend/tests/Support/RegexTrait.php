<?php

namespace App\Tests\Support;

use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\RegularExpression;

trait RegexTrait
{
    /**
     * Assert that a variable is an iri
     */
    protected function assertMatchesIri(string $string, string $resource, bool $collection = true, string $message = ''): void {
        /**
         * @var ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
         * @disregard P1013 Undefined method
        */
        $resourceMetadataFactory = static::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class);
        
        /**
         * @var PathSegmentNameGeneratorInterface $pathSegmentNameGenerator
         * @disregard P1013 Undefined method
        */
        $pathSegmentNameGenerator = static::getContainer()->get('api_platform.metadata.path_segment_name_generator.underscore');

        $resourceMetadata = $resourceMetadataFactory->create($resource)->getOperation();
        $shortName = $resourceMetadata->getShortName();

        $segment = $pathSegmentNameGenerator->getSegmentName($shortName, $collection);

        $pattern = sprintf('/^\/%s\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', preg_quote($segment, '/'));

        Assert::assertThat($string, new RegularExpression($pattern), $message);
    }

    /**
     * Assert that a variable is a ISO 8601 date
     */
    protected static function assertMatchesDate(string $string, string $message = ''): void {
        $pattern = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\+|-)[0-9]{2}:[0-9]{2}$/';
        Assert::assertThat($string, new RegularExpression($pattern), $message);
    }
}