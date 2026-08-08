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
            'save edited articles'       => ['POST', '/api/v1/feeds/articles/create-texts'],

            // Language edit: routePut() -> formatUpdate() has always existed,
            // but ROUTES listed languages as GET/POST/DELETE only.
            'update a language'     => ['PUT', '/api/v1/languages/5'],

            // Tag management: the list page's CRUD.
            'list term tags'        => ['GET', '/api/v1/tags/term/list'],
            'create a term tag'     => ['POST', '/api/v1/tags/term'],
            'update a term tag'     => ['PUT', '/api/v1/tags/term/7'],
            'delete a term tag'     => ['DELETE', '/api/v1/tags/term/7'],
            'delete text tags'      => ['DELETE', '/api/v1/tags/text'],

            // Admin user management. The handler enforces the admin role
            // itself; these only assert the registry lets the request through.
            'list users'            => ['GET', '/api/v1/admin/users'],
            'read one user'         => ['GET', '/api/v1/admin/users/4'],
            'create a user'         => ['POST', '/api/v1/admin/users'],
            'update a user'         => ['PUT', '/api/v1/admin/users/4'],
            'set a user role'       => ['PUT', '/api/v1/admin/users/4/role'],
            'set a user status'     => ['PUT', '/api/v1/admin/users/4/status'],
            'delete a user'         => ['DELETE', '/api/v1/admin/users/4'],

            // Standalone term creation, replacing POST /word/new (#262).
            'create a term for a language' => ['POST', '/api/v1/terms/for-language'],

            // The signed-in user's own profile (#262).
            'read own profile'      => ['GET', '/api/v1/profile'],
            'update own profile'    => ['PUT', '/api/v1/profile'],
            'change own password'   => ['PUT', '/api/v1/profile/password'],
            'read preferences'      => ['GET', '/api/v1/profile/preferences'],
            'save preferences'      => ['PUT', '/api/v1/profile/preferences'],

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
        // Nothing in the API accepts PATCH anywhere.
        $this->assertNotIsString(
            Endpoints::resolve('PATCH', '/api/v1/tags'),
            'No endpoint registers PATCH; it should not resolve.'
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
