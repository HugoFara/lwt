<?php

declare(strict_types=1);

namespace Tests\Router;

require_once __DIR__ . '/../../../src/backend/Router/Router.php';

use Lwt\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Router class
 *
 * Tests route registration, resolution, pattern matching,
 * legacy URL handling, and HTTP method routing.
 */
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();

        // Save original superglobals
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
    }

    protected function tearDown(): void
    {
        // Restore original superglobals
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;

        parent::tearDown();
    }

    private array $originalServer;
    private array $originalGet;

    /**
     * Helper to simulate a request
     */
    private function simulateRequest(
        string $uri,
        string $method = 'GET',
        array $query = []
    ): array {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['QUERY_STRING'] = http_build_query($query);
        $_GET = $query;

        return $this->router->resolve();
    }

    // ==================== ROUTE REGISTRATION TESTS ====================

    public function testRegisterRoute(): void
    {
        $this->router->register('/test', 'handler.php');

        $result = $this->simulateRequest('/test');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('handler.php', $result['handler']);
    }

    public function testRegisterRouteWithMethod(): void
    {
        $this->router->register('/test', 'get_handler.php', 'GET');
        $this->router->register('/test', 'post_handler.php', 'POST');

        $getResult = $this->simulateRequest('/test', 'GET');
        $this->assertEquals('get_handler.php', $getResult['handler']);

        $postResult = $this->simulateRequest('/test', 'POST');
        $this->assertEquals('post_handler.php', $postResult['handler']);
    }

    public function testRegisterRouteWithWildcardMethod(): void
    {
        $this->router->register('/test', 'any_handler.php', '*');

        $getResult = $this->simulateRequest('/test', 'GET');
        $this->assertEquals('any_handler.php', $getResult['handler']);

        $postResult = $this->simulateRequest('/test', 'POST');
        $this->assertEquals('any_handler.php', $postResult['handler']);
    }

    public function testMethodSpecificOverridesWildcard(): void
    {
        $this->router->register('/test', 'wildcard_handler.php', '*');
        $this->router->register('/test', 'specific_get_handler.php', 'GET');

        $getResult = $this->simulateRequest('/test', 'GET');
        $this->assertEquals('specific_get_handler.php', $getResult['handler']);

        $postResult = $this->simulateRequest('/test', 'POST');
        $this->assertEquals('wildcard_handler.php', $postResult['handler']);
    }

    // ==================== LEGACY ROUTE TESTS ====================

    public function testRegisterLegacyRoute(): void
    {
        $this->router->registerLegacy('old_page.php', '/new/path');

        $result = $this->simulateRequest('/old_page.php');

        $this->assertEquals('redirect', $result['type']);
        $this->assertEquals('/new/path', $result['url']);
        $this->assertEquals(301, $result['code']);
    }

    public function testLegacyRoutePreservesQueryString(): void
    {
        $this->router->registerLegacy('old_page.php', '/new/path');

        // Set query string BEFORE simulateRequest, then call with matching params
        $_SERVER['REQUEST_URI'] = '/old_page.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'id=123&action=view';
        $_GET = ['id' => '123', 'action' => 'view'];

        $result = $this->router->resolve();

        $this->assertEquals('redirect', $result['type']);
        $this->assertEquals('/new/path?id=123&action=view', $result['url']);
    }

    public function testLegacyRouteWithEmptyQueryString(): void
    {
        $this->router->registerLegacy('old_page.php', '/new/path');

        $_SERVER['QUERY_STRING'] = '';
        $result = $this->simulateRequest('/old_page.php');

        $this->assertEquals('/new/path', $result['url']);
    }

    // ==================== PATH NORMALIZATION TESTS ====================

    public function testPathNormalizationWithTrailingSlash(): void
    {
        $this->router->register('/test', 'handler.php');

        $result = $this->simulateRequest('/test/');

        // The router normalizes paths by removing trailing slashes
        // So /test/ becomes /test
        $this->assertEquals('handler', $result['type']);
    }

    public function testPathNormalizationWithLeadingSlash(): void
    {
        $this->router->register('/test', 'handler.php');

        $result = $this->simulateRequest('test');

        // The router adds leading slash if missing
        $this->assertEquals('handler', $result['type']);
    }

    public function testRootPath(): void
    {
        $this->router->register('/', 'home.php');

        $result = $this->simulateRequest('/');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('home.php', $result['handler']);
    }

    public function testEmptyRequestUri(): void
    {
        $this->router->register('/', 'home.php');

        $result = $this->simulateRequest('');

        // Empty URI should resolve to root
        $this->assertEquals('handler', $result['type']);
    }

    // ==================== PATTERN MATCHING TESTS ====================

    public function testPatternMatchingWithSingleParameter(): void
    {
        $this->router->register('/text/{id}', 'text_handler.php');

        $result = $this->simulateRequest('/text/123');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('text_handler.php', $result['handler']);
        $this->assertArrayHasKey('id', $result['params']);
        $this->assertEquals('123', $result['params']['id']);
    }

    public function testPatternMatchingWithMultipleParameters(): void
    {
        $this->router->register('/user/{userId}/post/{postId}', 'user_post_handler.php');

        $result = $this->simulateRequest('/user/42/post/99');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('user_post_handler.php', $result['handler']);
        $this->assertEquals('42', $result['params']['userId']);
        $this->assertEquals('99', $result['params']['postId']);
    }

    public function testPatternMatchingPreservesQueryParams(): void
    {
        $this->router->register('/text/{id}', 'text_handler.php');

        $result = $this->simulateRequest('/text/123', 'GET', ['action' => 'view']);

        $this->assertArrayHasKey('id', $result['params']);
        $this->assertArrayHasKey('action', $result['params']);
        $this->assertEquals('123', $result['params']['id']);
        $this->assertEquals('view', $result['params']['action']);
    }

    public function testExactMatchTakesPrecedenceOverPattern(): void
    {
        $this->router->register('/text/new', 'new_text_handler.php');
        $this->router->register('/text/{id}', 'text_handler.php');

        $exactResult = $this->simulateRequest('/text/new');
        $this->assertEquals('new_text_handler.php', $exactResult['handler']);

        $patternResult = $this->simulateRequest('/text/123');
        $this->assertEquals('text_handler.php', $patternResult['handler']);
    }

    // ==================== NOT FOUND TESTS ====================

    public function testNotFoundForUnregisteredRoute(): void
    {
        $result = $this->simulateRequest('/nonexistent/path');

        $this->assertEquals('not_found', $result['type']);
        $this->assertArrayHasKey('path', $result);
    }

    public function testNotFoundForPartialMatch(): void
    {
        $this->router->register('/text/read', 'handler.php');

        $result = $this->simulateRequest('/text');

        $this->assertEquals('not_found', $result['type']);
    }

    public function testNotFoundForWrongMethod(): void
    {
        $this->router->register('/test', 'handler.php', 'POST');

        $result = $this->simulateRequest('/test', 'GET');

        // Should return not_found because no GET handler is registered
        $this->assertEquals('not_found', $result['type']);
    }

    // ==================== URL PARSING TESTS ====================

    public function testUrlWithQueryString(): void
    {
        $this->router->register('/test', 'handler.php');

        $result = $this->simulateRequest('/test?foo=bar&baz=qux', 'GET', ['foo' => 'bar', 'baz' => 'qux']);

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('bar', $result['params']['foo']);
        $this->assertEquals('qux', $result['params']['baz']);
    }

    public function testUrlWithFragment(): void
    {
        $this->router->register('/test', 'handler.php');

        // Fragments are typically not sent to server, but we test parsing
        $result = $this->simulateRequest('/test#section');

        $this->assertEquals('handler', $result['type']);
    }

    // ==================== CONTROLLER FORMAT TESTS ====================

    public function testControllerAtMethodFormat(): void
    {
        $this->router->register('/api/users', 'UserController@index');

        $result = $this->simulateRequest('/api/users');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('UserController@index', $result['handler']);
    }

    // ==================== EDGE CASES ====================

    public function testRouteWithSpecialCharactersInPath(): void
    {
        $this->router->register('/api/v1.0/test', 'handler.php');

        $result = $this->simulateRequest('/api/v1.0/test');

        $this->assertEquals('handler', $result['type']);
    }

    public function testRouteWithHyphenInPath(): void
    {
        $this->router->register('/text/import-long', 'import_long_handler.php');

        $result = $this->simulateRequest('/text/import-long');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('import_long_handler.php', $result['handler']);
    }

    public function testRouteWithUnderscoreInPath(): void
    {
        $this->router->register('/admin/server_data', 'server_data_handler.php');

        $result = $this->simulateRequest('/admin/server_data');

        $this->assertEquals('handler', $result['type']);
    }

    public function testMultipleLegacyRoutesToSameDestination(): void
    {
        $this->router->registerLegacy('old1.php', '/new/path');
        $this->router->registerLegacy('old2.php', '/new/path');
        $this->router->registerLegacy('old3.php', '/new/path');

        $result1 = $this->simulateRequest('/old1.php');
        $result2 = $this->simulateRequest('/old2.php');
        $result3 = $this->simulateRequest('/old3.php');

        $this->assertEquals('/new/path', $result1['url']);
        $this->assertEquals('/new/path', $result2['url']);
        $this->assertEquals('/new/path', $result3['url']);
    }

    public function testLegacyPhpFileNotInMap(): void
    {
        // A .php file that's NOT registered as legacy should return not_found
        $result = $this->simulateRequest('/unregistered.php');

        $this->assertEquals('not_found', $result['type']);
    }

    public function testNestedPathWithPatterns(): void
    {
        $this->router->register('/api/v1/users/{id}/posts/{postId}', 'handler.php');

        $result = $this->simulateRequest('/api/v1/users/5/posts/10');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('5', $result['params']['id']);
        $this->assertEquals('10', $result['params']['postId']);
    }

    public function testDefaultRequestMethod(): void
    {
        $this->router->register('/test', 'handler.php');

        // Unset REQUEST_METHOD to test default
        unset($_SERVER['REQUEST_METHOD']);
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['QUERY_STRING'] = '';
        $_GET = [];

        $result = $this->router->resolve();

        // Should default to GET
        $this->assertEquals('handler', $result['type']);
    }

    public function testDefaultRequestUri(): void
    {
        $this->router->register('/', 'home.php');

        // Unset REQUEST_URI to test default
        unset($_SERVER['REQUEST_URI']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '';
        $_GET = [];

        $result = $this->router->resolve();

        // Should default to /
        $this->assertEquals('handler', $result['type']);
    }
}
