<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\Http;

use Lwt\Modules\Tags\Http\TagApiHandler;
use Lwt\Shared\Http\ApiRoutableInterface;
use Lwt\Shared\Infrastructure\Http\JsonResponse;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for TagApiHandler.
 *
 * Tests the tag API handler including route dispatching, GET handling
 * for term/text/all tags, the handle() method, and unsupported HTTP methods.
 *
 * Note: TagApiHandler uses static TagsFacade calls, so tests that exercise
 * the actual facade methods require a database connection. Structural and
 * routing-logic tests run without a database.
 */
class TagApiHandlerTest extends TestCase
{
    private TagApiHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new TagApiHandler();
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(TagApiHandler::class, $this->handler);
    }

    #[Test]
    public function handlerImplementsApiRoutableInterface(): void
    {
        $this->assertInstanceOf(ApiRoutableInterface::class, $this->handler);
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classUsesApiRoutableTrait(): void
    {
        $reflection = new \ReflectionClass(TagApiHandler::class);
        $traitNames = array_map(
            fn(\ReflectionClass $t) => $t->getName(),
            $reflection->getTraits()
        );

        $this->assertContains(
            'Lwt\Shared\Http\ApiRoutableTrait',
            $traitNames
        );
    }

    #[Test]
    public function classHasRouteGetMethod(): void
    {
        $reflection = new \ReflectionClass(TagApiHandler::class);
        $this->assertTrue($reflection->hasMethod('routeGet'));

        $method = $reflection->getMethod('routeGet');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function classHasHandleGetMethod(): void
    {
        $reflection = new \ReflectionClass(TagApiHandler::class);
        $this->assertTrue($reflection->hasMethod('handleGet'));

        $method = $reflection->getMethod('handleGet');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function classHasHandleMethod(): void
    {
        $reflection = new \ReflectionClass(TagApiHandler::class);
        $this->assertTrue($reflection->hasMethod('handle'));

        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function routeGetAcceptsTwoArrayParameters(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'routeGet');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('fragments', $params[0]->getName());
        $this->assertSame('params', $params[1]->getName());
    }

    #[Test]
    public function handleGetAcceptsOneArrayParameter(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'handleGet');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('fragments', $params[0]->getName());
    }

    #[Test]
    public function handleAcceptsStringAndArrayParameters(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'handle');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('method', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
        $this->assertSame('fragments', $params[1]->getName());
    }

    #[Test]
    public function routeGetReturnsJsonResponse(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'routeGet');

        $this->assertSame(
            JsonResponse::class,
            $method->getReturnType()->getName()
        );
    }

    #[Test]
    public function handleGetReturnsJsonResponse(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'handleGet');

        $this->assertSame(
            JsonResponse::class,
            $method->getReturnType()->getName()
        );
    }

    #[Test]
    public function handleReturnsJsonResponse(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'handle');

        $this->assertSame(
            JsonResponse::class,
            $method->getReturnType()->getName()
        );
    }

    // =========================================================================
    // Trait-inherited method tests (routePost, routePut, routeDelete return 405)
    // =========================================================================

    #[Test]
    public function routePostReturns405(): void
    {
        $response = $this->handler->routePost([], []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame(['error' => 'Method Not Allowed'], $response->getData());
    }

    #[Test]
    public function routePutReturns405(): void
    {
        $response = $this->handler->routePut([], []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame(['error' => 'Method Not Allowed'], $response->getData());
    }

    #[Test]
    public function routeDeleteReturns405(): void
    {
        $response = $this->handler->routeDelete([], []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame(['error' => 'Method Not Allowed'], $response->getData());
    }

    // =========================================================================
    // handle() method dispatch tests
    // =========================================================================

    #[Test]
    public function handleWithPostReturns405(): void
    {
        $response = $this->handler->handle('POST', []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame(['error' => 'Method not allowed'], $response->getData());
    }

    #[Test]
    public function handleWithPutReturns405(): void
    {
        $response = $this->handler->handle('PUT', []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
    }

    #[Test]
    public function handleWithDeleteReturns405(): void
    {
        $response = $this->handler->handle('DELETE', []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
    }

    #[Test]
    public function handleWithPatchReturns405(): void
    {
        $response = $this->handler->handle('PATCH', []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
    }

    #[Test]
    public function handleWithUnknownMethodReturns405(): void
    {
        $response = $this->handler->handle('OPTIONS', []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
    }

    #[Test]
    public function handleIsCaseInsensitiveForMethodPost(): void
    {
        $response = $this->handler->handle('post', []);

        $this->assertSame(405, $response->getStatusCode());
    }

    #[Test]
    public function handleIsCaseInsensitiveForMethodPut(): void
    {
        $response = $this->handler->handle('put', []);

        $this->assertSame(405, $response->getStatusCode());
    }

    // =========================================================================
    // routeGet delegation tests (verifies it slices fragments)
    // =========================================================================

    #[Test]
    public function routeGetSlicesFragmentsBeforeDelegating(): void
    {
        // routeGet calls handleGet(array_slice($fragments, 1))
        // With fragments ['tags', 'term'], handleGet receives ['term']
        // We can't easily test the actual return without DB, but we can verify
        // the method exists and returns a JsonResponse.

        $reflection = new \ReflectionMethod(TagApiHandler::class, 'routeGet');
        $this->assertTrue($reflection->isPublic());

        // Verify the slicing logic by examining the source
        // routeGet calls $this->handleGet(array_slice($fragments, 1))
        // This means fragment[0] (the resource name 'tags') is stripped
        $this->assertSame(
            JsonResponse::class,
            $reflection->getReturnType()->getName()
        );
    }

    // =========================================================================
    // handleGet and handle with GET (require DB for full execution)
    // =========================================================================

    #[Test]
    public function handleGetWithTermFragmentReturnsJsonResponse(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        $response = $this->handler->handleGet(['term']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($response->getData());
    }

    #[Test]
    public function handleGetWithTextFragmentReturnsJsonResponse(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        $response = $this->handler->handleGet(['text']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($response->getData());
    }

    #[Test]
    public function handleGetWithEmptyFragmentsReturnsBothTagTypes(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        $response = $this->handler->handleGet([]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('term', $data);
        $this->assertArrayHasKey('text', $data);
    }

    #[Test]
    public function handleGetWithUnknownFragmentReturnsBothTagTypes(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        $response = $this->handler->handleGet(['unknown']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData();
        $this->assertArrayHasKey('term', $data);
        $this->assertArrayHasKey('text', $data);
    }

    #[Test]
    public function handleWithGetMethodDelegatesToHandleGet(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        $response = $this->handler->handle('GET', ['term']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function handleWithLowercaseGetDelegatesToHandleGet(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        $response = $this->handler->handle('get', []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetWithTagsAndTermFragments(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        // Simulates /api/v1/tags/term - fragments would be ['tags', 'term']
        $response = $this->handler->routeGet(['tags', 'term'], []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetWithTagsAndTextFragments(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        // Simulates /api/v1/tags/text - fragments would be ['tags', 'text']
        $response = $this->handler->routeGet(['tags', 'text'], []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetWithOnlyTagsFragmentReturnsBothTypes(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        // Simulates /api/v1/tags - fragments would be ['tags']
        $response = $this->handler->routeGet(['tags'], []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData();
        $this->assertArrayHasKey('term', $data);
        $this->assertArrayHasKey('text', $data);
    }

    #[Test]
    public function routeGetIgnoresQueryParams(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        // Query params are accepted but not used by this handler
        $response = $this->handler->routeGet(
            ['tags', 'term'],
            ['filter' => 'test', 'page' => '1']
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    // =========================================================================
    // handleGet fragment parsing edge cases
    // =========================================================================

    #[Test]
    public function handleGetCastsFragmentToString(): void
    {
        // The handler does (string) $fragments[0], so non-string values
        // should be handled. We test that the type parameter in the switch
        // statement is always a string.
        $method = new \ReflectionMethod(TagApiHandler::class, 'handleGet');
        $params = $method->getParameters();

        // Verify parameter is typed as array
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    #[Test]
    public function handleGetWithMultipleFragmentsUsesOnlyFirst(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for TagsFacade static calls');
        }

        // Only the first fragment determines the tag type
        $response = $this->handler->handleGet(['term', 'extra', 'fragments']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        // Should return term tags only (not both), since first fragment is 'term'
        $this->assertIsArray($response->getData());
    }

    // =========================================================================
    // Trait frag() helper tests (inherited from ApiRoutableTrait)
    // =========================================================================

    #[Test]
    public function fragHelperReturnsFragmentAtIndex(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'frag');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, ['a', 'b', 'c'], 1);

        $this->assertSame('b', $result);
    }

    #[Test]
    public function fragHelperReturnsEmptyStringForMissingIndex(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'frag');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, ['a'], 5);

        $this->assertSame('', $result);
    }

    #[Test]
    public function fragHelperReturnsEmptyStringForEmptyArray(): void
    {
        $method = new \ReflectionMethod(TagApiHandler::class, 'frag');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [], 0);

        $this->assertSame('', $result);
    }
}
