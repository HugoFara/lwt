<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\GdlClient;

/**
 * Testable subclass that stubs fetchJson and exposes the mapping helpers.
 */
class TestableGdlClient extends GdlClient
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

    public function testExtractEpubUrl(array $book): ?string
    {
        return $this->extractEpubUrl($book);
    }

    public function testExtractLevel(array $book): string
    {
        return $this->extractLevel($book);
    }
}
