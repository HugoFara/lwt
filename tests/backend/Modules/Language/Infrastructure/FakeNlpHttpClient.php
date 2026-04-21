<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Infrastructure;

use Lwt\Modules\Language\Infrastructure\NlpHttpClient;

/**
 * In-memory NlpHttpClient used to keep NlpServiceHandler tests offline.
 *
 * The handler tests previously hit the real NLP service URL and waited ~4s per
 * test for a DNS/connect timeout. Injecting this fake makes the suite instant.
 */
final class FakeNlpHttpClient implements NlpHttpClient
{
    public ?string $response = null;
    /** @var list<array{url: string, method: string, body: ?string, timeout: int, ignoreErrors: bool}> */
    public array $calls = [];

    public function request(
        string $url,
        string $method,
        ?string $jsonBody = null,
        int $timeoutSeconds = 30,
        bool $ignoreErrors = false,
    ): ?string {
        $this->calls[] = [
            'url' => $url,
            'method' => $method,
            'body' => $jsonBody,
            'timeout' => $timeoutSeconds,
            'ignoreErrors' => $ignoreErrors,
        ];
        return $this->response;
    }
}
