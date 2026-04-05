<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\GutenbergClient;

/**
 * Testable subclass that stubs fetchJson and exposes extractTextUrl.
 */
class TestableGutenbergClient extends GutenbergClient
{
    private ?array $mockResponse = null;
    public string $lastFetchedUrl = '';

    public function setMockResponse(?array $response): void
    {
        $this->mockResponse = $response;
    }

    protected function fetchJson(string $url): ?array
    {
        $this->lastFetchedUrl = $url;
        return $this->mockResponse;
    }

    public function testExtractTextUrl(array $book): ?string
    {
        return $this->extractTextUrl($book);
    }
}
