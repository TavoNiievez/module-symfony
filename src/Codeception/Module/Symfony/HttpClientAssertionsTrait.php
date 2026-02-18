<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpClient\DataCollector\HttpClientDataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

use function array_change_key_case;
use function array_intersect_key;
use function in_array;
use function is_array;
use function is_object;
use function method_exists;
use function sprintf;

trait HttpClientAssertionsTrait
{
    /**
     * Asserts that the given URL has been called using, if specified, the given method, body and/or headers.
     * By default, it will inspect the default Symfony HttpClient; you may check a different one by passing its
     * service-id in $httpClientId.
     * It succeeds even if the request was executed multiple times.
     *
     * ```php
     * <?php
     * $I->assertHttpClientRequest(
     *     'https://example.com/api',
     *     'POST',
     *     '{"data": "value"}',
     *     ['Authorization' => 'Bearer token']
     * );
     * ```
     *
     * @param string|array<mixed>|null      $expectedBody
     * @param array<string,string|string[]> $expectedHeaders
     */
    public function assertHttpClientRequest(
        string            $expectedUrl,
        string            $expectedMethod = 'GET',
        string|array|null $expectedBody   = null,
        array             $expectedHeaders = [],
        string            $httpClientId = 'http_client',
    ): void {
        $found = false;
        foreach ($this->getHttpClientTraces($httpClientId, __FUNCTION__) as $trace) {
            if (!is_array($trace)) {
                continue;
            }
            /** @var array{info: array{url: string}, url: string, method: string, options?: array{body?: mixed, json?: mixed, headers?: mixed}} $trace */
            if ($this->matchRequest($trace, $expectedUrl, $expectedMethod, $expectedBody, $expectedHeaders)) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('The expected request has not been called: "%s" - "%s"', $expectedMethod, $expectedUrl));
    }

    /**
     * Asserts that exactly $count requests have been executed by the given HttpClient.
     * By default, it will inspect the default Symfony HttpClient; you may check a different one by passing its
     * service-id in $httpClientId.
     *
     * ```php
     * $I->assertHttpClientRequestCount(3);
     * ```
     */
    public function assertHttpClientRequestCount(int $count, string $httpClientId = 'http_client'): void
    {
        $this->assertCount($count, $this->getHttpClientTraces($httpClientId, __FUNCTION__));
    }

    /**
     * Asserts that the given URL *has not* been requested with the supplied HTTP method.
     * By default, it will inspect the default Symfony HttpClient; you may check a different one by passing its
     * service-id in $httpClientId.
     * ```php
     * $I->assertNotHttpClientRequest('https://example.com/unexpected', 'GET');
     * ```
     */
    public function assertNotHttpClientRequest(
        string $unexpectedUrl,
        string $unexpectedMethod = 'GET',
        string $httpClientId = 'http_client',
    ): void {
        $found = false;
        foreach ($this->getHttpClientTraces($httpClientId, __FUNCTION__) as $trace) {
            if (!is_array($trace)) {
                continue;
            }
            /** @var array{info: array{url: string}, url: string, method: string} $trace */
            if ($this->matchesUrlAndMethod($trace, $unexpectedUrl, $unexpectedMethod)) {
                $found = true;
                break;
            }
        }
        Assert::assertFalse($found, sprintf('Unexpected URL was called: "%s" - "%s"', $unexpectedMethod, $unexpectedUrl));
    }

    /**
     * @return array<mixed>
     */
    private function getHttpClientTraces(string $httpClientId, string $function): array
    {
        $clients = $this->grabHttpClientCollector($function)->getClients();

        if (!isset($clients[$httpClientId])) {
            $this->fail(sprintf('HttpClient "%s" is not registered.', $httpClientId));
        }

        /** @var array{traces: array<mixed>} $clientData */
        $clientData = $clients[$httpClientId];
        return $clientData['traces'];
    }

    /** @param array{info: array{url: string}, url: string, method: string} $trace */
    private function matchesUrlAndMethod(array $trace, string $expectedUrl, string $expectedMethod): bool
    {
        return $expectedMethod === $trace['method'] && in_array($expectedUrl, [$trace['info']['url'], $trace['url']], true);
    }

    /**
     * @param array{info: array{url: string}, url: string, method: string, options?: array{body?: mixed, json?: mixed, headers?: mixed}} $trace
     * @param string|array<mixed>|null $expectedBody
     * @param array<string,string|string[]> $expectedHeaders
     */
    private function matchRequest(array $trace, string $expectedUrl, string $expectedMethod, string|array|null $expectedBody, array $expectedHeaders): bool
    {
        if (!$this->matchesUrlAndMethod($trace, $expectedUrl, $expectedMethod)) {
            return false;
        }

        $options = $trace['options'] ?? [];
        if ($expectedBody !== null && $expectedBody !== $this->extractValue($options['body'] ?? $options['json'] ?? null)) {
            return false;
        }

        if ($expectedHeaders !== []) {
            $actualHeaders = $this->extractValue($options['headers'] ?? []);
            if (!is_array($actualHeaders)) {
                return false;
            }
            $normalizedExpected = array_change_key_case($expectedHeaders);
            if (array_intersect_key(array_change_key_case($actualHeaders), $normalizedExpected) !== $normalizedExpected) {
                return false;
            }
        }

        return true;
    }

    private function extractValue(mixed $value): mixed
    {
        if ($value instanceof Data) {
            return $value->getValue(true);
        }
        if (is_object($value)) {
            if (method_exists($value, 'getValue')) {
                return $value->getValue(true);
            }
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
        }
        return $value;
    }

    protected function grabHttpClientCollector(string $function): HttpClientDataCollector
    {
        return $this->grabCollector(DataCollectorName::HTTP_CLIENT, $function);
    }
}
