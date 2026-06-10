<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextApiHandler;
use Lwt\Shared\Infrastructure\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

/**
 * DB-free dispatch tests for TextApiHandler::routeGet.
 *
 * The reading-chrome endpoints (book-context, audio) delegate straight to
 * BookFacade / TextFacade under per-user QueryBuilder scoping, so their happy
 * paths are exercised by those facades' own DB-backed tests. What is verified
 * here without a database is the routing layer: that an unrecognised subpath
 * under a numeric text id 404s and advertises the supported subpaths (so a new
 * subpath cannot silently regress the dispatch), and that a non-numeric text id
 * falls through to the resource-level 404.
 */
class TextApiHandlerRouteGetTest extends TestCase
{
    private TextApiHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new TextApiHandler(null);
    }

    /**
     * @param list<string> $fragments
     */
    private function get(array $fragments): JsonResponse
    {
        return $this->handler->routeGet($fragments, []);
    }

    public function testUnknownNumericSubpathAdvertisesReadingChromeEndpoints(): void
    {
        $res = $this->get(['texts', '5', 'bogus']);

        $this->assertSame(404, $res->getStatusCode());
        $data = $res->getData();
        $this->assertIsArray($data);
        $this->assertStringContainsString('book-context', (string) $data['error']);
        $this->assertStringContainsString('audio', (string) $data['error']);
    }

    public function testNonNumericTextIdFallsThroughToResource404(): void
    {
        $res = $this->get(['texts', 'not-an-id']);

        $this->assertSame(404, $res->getStatusCode());
    }
}
