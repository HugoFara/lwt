<?php

declare(strict_types=1);

namespace Lwt\Tests\Api\V1;

use Lwt\Api\V1\Endpoints;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Guards the gap between "a handler implements this method" and "the endpoint
 * registry permits it".
 *
 * `Endpoints::ROUTES` is keyed by path, and a request only matches a key
 * exactly when it carries no ID. `GET /books/12/chapters` matches nothing, so
 * `getMethodsForEndpoint()` falls back to the first segment — `books`. Any
 * method missing from the bare entry is rejected with 405 before dispatch, no
 * matter how complete the handler is.
 *
 * That is how `DELETE /books/{id}` and `PUT /books/{id}/progress` sat
 * unreachable while `BookApiHandler::routeDelete()` and `updateProgress()`
 * were fully written: the `books/chapters` and `books/progress` keys look like
 * they authorise those routes, but nothing ever matches them.
 *
 * The cases below are real request shapes, with IDs, asserted through the same
 * public entry point the API uses.
 */
class EndpointMethodReachabilityTest extends TestCase
{
    /**
     * Request shapes the API is expected to serve.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function servableRoutes(): array
    {
        return [
            // Books — the surface this test was written for.
            'list books'            => ['GET', '/api/v1/books'],
            'import a book'         => ['POST', '/api/v1/books'],
            'read one book'         => ['GET', '/api/v1/books/12'],
            'read book chapters'    => ['GET', '/api/v1/books/12/chapters'],
            'update book progress'  => ['PUT', '/api/v1/books/12/progress'],
            'delete a book'         => ['DELETE', '/api/v1/books/12'],

            // Reads added for the bulk-translate and feed-article-edit pages.
            'unknown words to translate' => ['GET', '/api/v1/terms/unknown-for-translate'],
            'extract articles to edit'   => ['POST', '/api/v1/feeds/articles/extract'],

            // Anchors on surfaces that already work, so a regression in the
            // fallback itself is caught rather than only the books entry.
            'set a term status'     => ['PUT', '/api/v1/terms/5/status'],
            'delete a term'         => ['DELETE', '/api/v1/terms/5'],
            'read text words'       => ['GET', '/api/v1/texts/9/words'],
            'update a text'         => ['PUT', '/api/v1/texts/9'],
            'delete a feed'         => ['DELETE', '/api/v1/feeds/3'],
        ];
    }

    #[Test]
    #[DataProvider('servableRoutes')]
    public function theRegistryPermitsEveryRouteTheApiServes(string $method, string $uri): void
    {
        $resolved = Endpoints::resolve($method, $uri);

        // resolve() returns the endpoint string when the method is permitted,
        // and a JsonResponse (404/405) when it is not.
        $this->assertIsString(
            $resolved,
            "$method $uri is rejected by Endpoints::ROUTES before it can reach "
            . 'its handler. If the handler implements this method, add the '
            . 'method to the *first path segment* entry in ROUTES — a '
            . 'sub-resource key such as "books/progress" is never matched by a '
            . 'URL containing an ID.'
        );
    }

    /**
     * Methods no handler implements must stay rejected.
     */
    #[Test]
    public function theRegistryStillRejectsUnsupportedMethods(): void
    {
        // Nothing in the API accepts PUT on the bare tags collection.
        $this->assertNotIsString(
            Endpoints::resolve('DELETE', '/api/v1/tags'),
            'tags is registered GET-only; DELETE should not resolve.'
        );
    }

    /**
     * Helper: assert a value is not a string.
     *
     * @param mixed $value Value under test
     */
    private function assertNotIsString(mixed $value, string $message = ''): void
    {
        $this->assertFalse(is_string($value), $message);
    }
}
