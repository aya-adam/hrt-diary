<?php

namespace App\Tests\Support;

use GraphQL\Type\Introspection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\ArrayHasKey;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Helpers for functional GraphQL tests.
 *
 * This trait is an extension of `ApiPlatform\GraphQl\Test\GraphQlTestTrait` and replaces the methods
 * `executeGraphQl()`, `introspectSchema()` and `executeGraphQlMultipart()`. Instead of using the
 * static `createClient()` inside the methods, this customized trait allows the client to be set as
 * a method parameter which allow the usage of the custom static `createAuthenticatedClient()`.
 */
trait GraphQlTestTrait
{
    use \ApiPlatform\GraphQl\Test\GraphQlTestTrait;

    /**
     * @param array<string, mixed>           $variables
     * @param array<string, string|string[]> $headers
     */
    protected function executeGraphQl(HttpClientInterface $client, string $query, array $variables = [], ?string $operationName = null, array $headers = []): ResponseInterface
    {
        $payload = ['query' => $query];

        if ($variables) {
            $payload['variables'] = $variables;
        }

        if (null !== $operationName) {
            $payload['operationName'] = $operationName;
        }

        $options = [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
        ];

        if ($headers) {
            foreach ($headers as $key => $value) {
                $options['headers'][$key] = $value;
            }
        }

        return $client->request('POST', '/graphql', $options);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    protected function introspectSchema(HttpClientInterface $client, array $headers = []): ResponseInterface
    {
        return $this->executeGraphQl($client, Introspection::getIntrospectionQuery(), [], null, $headers);
    }

    /**
     * Send a `multipart/form-data` GraphQL request following the
     * graphql-multipart-request-spec (https://github.com/jaydenseric/graphql-multipart-request-spec).
     *
     * @param array<int|string, string|\Symfony\Component\HttpFoundation\File\UploadedFile> $files   Map of file marker => absolute file path or UploadedFile
     * @param array<string, string|string[]>                                                $headers
     */
    protected function executeGraphQlMultipart(HttpClientInterface $client, string $operations, string $map, array $files, array $headers = []): ResponseInterface
    {
        return $client->request('POST', '/graphql', [
            'headers' => ['Content-Type' => 'multipart/form-data'] + $headers,
            'extra' => [
                'parameters' => ['operations' => $operations, 'map' => $map],
                'files' => $files,
            ],
        ]);
    }

    /**
     * Asserts the error message
     * 
     * Mirrors the Behat `the GraphQL error message should be equal to` step:
     * looks under `errors[$i].message`.
     *
     * @param array{errors?: list<array<string, mixed>>} $data
     */
    protected function assertGraphQlErrorMessage(array $data, string $expectedDebugMessage, int $index = 0): void
    {
        if (!isset($data['errors'][$index])) {
            throw new ExpectationFailedException(\sprintf('No GraphQL error at index %d.', $index));
        }

        $error = $data['errors'][$index];
        $debug = $error['message'] ?? null;

        Assert::assertSame($expectedDebugMessage, $debug);
    }

    /**
     * Asserts the error status
     * 
     * Mirrors the Behat `the GraphQL extension status should be equal to` step:
     * looks under `errors[$i].extensions.status`.
     *
     * @param array{errors?: list<array<string, mixed>>} $data
     */
    protected function assertGraphQlErrorStatus(array $data, int $expectedErrorStatus, int $index = 0): void
    {
        if (!isset($data['errors'][$index])) {
            throw new ExpectationFailedException(\sprintf('No GraphQL error at index %d.', $index));
        }

        $error = $data['errors'][$index];
        $debug = $error['extensions']['status'] ?? null;

        Assert::assertSame($expectedErrorStatus, $debug);
    }

    /**
     * Assert that the violation messages for a field are the same
     * 
     * Mirrors the Behat `the GraphQL extension violation with the path `$field` should be equal to`.
     * `$expectedMessages` can be set as an array, incase there are multiple violations with the same path.
     * 
     * step: looks under `errors[$i].extensions.violations[][$field]`.
     *
     * @param array{errors?: list<array<string, mixed>>} $data
     */
    protected function assertGraphQlFieldViolation(array $data, string $field, string|array $expectedMessages, int $index = 0): void
    {
        if (!isset($data['errors'][$index])) {
            throw new ExpectationFailedException(\sprintf('No GraphQL error at index %d.', $index));
        }

        $error = $data['errors'][$index];

        if (!isset($error['extensions']['violations'])) {
            throw new ExpectationFailedException(\sprintf('No violations at GraphQL error index %d.', $index));
        }

        $groupedViolations = [];
        foreach ($error['extensions']['violations'] as $item) {
            $groupedViolations[$item['path']][] = $item['message'] ?? null;
        }


        if (!isset($groupedViolations[$field])) {
            throw new ExpectationFailedException(\sprintf('No violation for the field `%s` at GraphQL error index %d.', $field, $index));
        }

        if (is_string($expectedMessages)) {
            $expectedMessages = [$expectedMessages];
        }

        Assert::assertSame($expectedMessages, $groupedViolations[$field]);
    }

    /**
     * @param array{errors?: list<array<string, mixed>>} $data
     */
    protected function assertGraphQlHasNoErrors(array $data, string $message = ''): void
    {
        $constraint = new LogicalNot(
            new ArrayHasKey('errors'),
        );

        Assert::assertThat($data, $constraint, $message);
    }
    
    /**
     * @param array{errors?: list<array<string, mixed>>} $data
     */
    protected function assertGraphQlHasErrors(array $data, string $message = ''): void
    {
        $constraint = new ArrayHasKey('errors');

        Assert::assertThat($data, $constraint, $message);
    }
}
