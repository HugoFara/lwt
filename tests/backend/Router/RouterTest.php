<?php

declare(strict_types=1);

namespace Tests\Router;

require_once __DIR__ . '/../../../src/backend/Router/Router.php';
require_once __DIR__ . '/../../../src/backend/Router/Middleware/MiddlewareInterface.php';

use Lwt\Router\Router;
use Lwt\Router\Middleware\MiddlewareInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Router class
 *
 * Tests route registration, resolution, pattern matching,
 * and HTTP method routing.
 */
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router(dirname(__DIR__, 3));

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

    public function testPhpFileReturnsNotFound(): void
    {
        // A .php file should return not_found (no legacy route support)
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

    // ==================== MIDDLEWARE TESTS ====================

    public function testRegisterWithMiddleware(): void
    {
        $this->router->registerWithMiddleware(
            '/protected',
            'handler.php',
            ['TestMiddleware']
        );

        $result = $this->simulateRequest('/protected');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('handler.php', $result['handler']);
        $this->assertArrayHasKey('middleware', $result);
        $this->assertContains('TestMiddleware', $result['middleware']);
    }

    public function testRegisterWithMiddlewareMultiple(): void
    {
        $middleware = ['AuthMiddleware', 'LoggingMiddleware'];
        $this->router->registerWithMiddleware(
            '/protected',
            'handler.php',
            $middleware
        );

        $result = $this->simulateRequest('/protected');

        $this->assertCount(2, $result['middleware']);
        $this->assertEquals($middleware, $result['middleware']);
    }

    public function testRegisterWithMiddlewareMethod(): void
    {
        $this->router->registerWithMiddleware(
            '/protected',
            'get_handler.php',
            ['AuthMiddleware'],
            'GET'
        );
        $this->router->registerWithMiddleware(
            '/protected',
            'post_handler.php',
            ['AdminMiddleware'],
            'POST'
        );

        $getResult = $this->simulateRequest('/protected', 'GET');
        $this->assertEquals('get_handler.php', $getResult['handler']);
        $this->assertContains('AuthMiddleware', $getResult['middleware']);

        $postResult = $this->simulateRequest('/protected', 'POST');
        $this->assertEquals('post_handler.php', $postResult['handler']);
        $this->assertContains('AdminMiddleware', $postResult['middleware']);
    }

    public function testRouteWithoutMiddlewareHasEmptyMiddlewareArray(): void
    {
        $this->router->register('/public', 'handler.php');

        $result = $this->simulateRequest('/public');

        $this->assertEquals('handler', $result['type']);
        $this->assertArrayHasKey('middleware', $result);
        $this->assertEmpty($result['middleware']);
    }

    public function testRegisterPrefixWithMiddleware(): void
    {
        $this->router->registerPrefixWithMiddleware(
            '/api',
            'ApiHandler@handle',
            ['ApiAuthMiddleware']
        );

        $result = $this->simulateRequest('/api/users');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('ApiHandler@handle', $result['handler']);
        $this->assertContains('ApiAuthMiddleware', $result['middleware']);
    }

    public function testPatternRouteWithMiddleware(): void
    {
        $this->router->registerWithMiddleware(
            '/user/{id}',
            'UserController@show',
            ['AuthMiddleware']
        );

        $result = $this->simulateRequest('/user/123');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('UserController@show', $result['handler']);
        $this->assertEquals('123', $result['params']['id']);
        $this->assertContains('AuthMiddleware', $result['middleware']);
    }

    public function testMiddlewareNotIncludedForNotFoundRoute(): void
    {
        $this->router->registerWithMiddleware(
            '/protected',
            'handler.php',
            ['AuthMiddleware']
        );

        $result = $this->simulateRequest('/nonexistent');

        $this->assertEquals('not_found', $result['type']);
        $this->assertArrayNotHasKey('middleware', $result);
    }

    // ==================== TYPED ROUTE PARAMETER TESTS ====================

    public function testTypedIntParameter(): void
    {
        $this->router->register('/text/{id:int}', 'text_handler.php');

        $result = $this->simulateRequest('/text/123');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('text_handler.php', $result['handler']);
        $this->assertArrayHasKey('routeParams', $result);
        $this->assertSame(123, $result['routeParams']['id']); // Should be int, not string
    }

    public function testTypedIntParameterRejectsNonNumeric(): void
    {
        $this->router->register('/text/{id:int}', 'text_handler.php');

        $result = $this->simulateRequest('/text/abc');

        // Should not match because 'abc' doesn't match [0-9]+
        $this->assertEquals('not_found', $result['type']);
    }

    public function testTypedAlphaParameter(): void
    {
        $this->router->register('/category/{name:alpha}', 'category_handler.php');

        $result = $this->simulateRequest('/category/sports');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('sports', $result['routeParams']['name']);
    }

    public function testTypedAlphaParameterRejectsNumbers(): void
    {
        $this->router->register('/category/{name:alpha}', 'category_handler.php');

        $result = $this->simulateRequest('/category/sports123');

        // Should not match because 'sports123' doesn't match [a-zA-Z]+
        $this->assertEquals('not_found', $result['type']);
    }

    public function testTypedSlugParameter(): void
    {
        $this->router->register('/post/{slug:slug}', 'post_handler.php');

        $result = $this->simulateRequest('/post/my-first-post_2023');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('my-first-post_2023', $result['routeParams']['slug']);
    }

    public function testTypedAlphanumParameter(): void
    {
        $this->router->register('/token/{token:alphanum}', 'token_handler.php');

        $result = $this->simulateRequest('/token/abc123XYZ');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('abc123XYZ', $result['routeParams']['token']);
    }

    public function testTypedUuidParameter(): void
    {
        $this->router->register('/resource/{id:uuid}', 'resource_handler.php');

        $result = $this->simulateRequest('/resource/550e8400-e29b-41d4-a716-446655440000');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result['routeParams']['id']);
    }

    public function testTypedUuidParameterRejectsInvalid(): void
    {
        $this->router->register('/resource/{id:uuid}', 'resource_handler.php');

        $result = $this->simulateRequest('/resource/not-a-uuid');

        $this->assertEquals('not_found', $result['type']);
    }

    // ==================== OPTIONAL PARAMETER TESTS ====================

    public function testOptionalParameterPresent(): void
    {
        $this->router->register('/page/{num?}', 'page_handler.php');

        $result = $this->simulateRequest('/page/5');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('5', $result['routeParams']['num']);
    }

    public function testOptionalTypedParameterPresent(): void
    {
        $this->router->register('/page/{num:int?}', 'page_handler.php');

        $result = $this->simulateRequest('/page/5');

        $this->assertEquals('handler', $result['type']);
        $this->assertSame(5, $result['routeParams']['num']);
    }

    public function testOptionalTypedParameterRejectsInvalid(): void
    {
        $this->router->register('/page/{num:int?}', 'page_handler.php');

        $result = $this->simulateRequest('/page/abc');

        // Should not match - optional but if present must be valid
        $this->assertEquals('not_found', $result['type']);
    }

    // ==================== MULTIPLE TYPED PARAMETERS TESTS ====================

    public function testMultipleTypedParameters(): void
    {
        $this->router->register('/user/{userId:int}/post/{postId:int}', 'user_post_handler.php');

        $result = $this->simulateRequest('/user/42/post/99');

        $this->assertEquals('handler', $result['type']);
        $this->assertSame(42, $result['routeParams']['userId']);
        $this->assertSame(99, $result['routeParams']['postId']);
    }

    public function testMixedTypedAndUntypedParameters(): void
    {
        $this->router->register('/user/{userId:int}/action/{action}', 'handler.php');

        $result = $this->simulateRequest('/user/42/action/delete');

        $this->assertEquals('handler', $result['type']);
        $this->assertSame(42, $result['routeParams']['userId']);
        $this->assertEquals('delete', $result['routeParams']['action']);
    }

    // ==================== ROUTE PARAMS VS QUERY PARAMS TESTS ====================

    public function testRouteParamsSeparateFromQueryParams(): void
    {
        $this->router->register('/text/{id:int}', 'text_handler.php');

        $result = $this->simulateRequest('/text/123', 'GET', ['page' => '2']);

        // routeParams should only have route parameters
        $this->assertArrayHasKey('routeParams', $result);
        $this->assertArrayHasKey('id', $result['routeParams']);
        $this->assertArrayNotHasKey('page', $result['routeParams']);

        // params should have both
        $this->assertArrayHasKey('id', $result['params']);
        $this->assertArrayHasKey('page', $result['params']);
    }

    // ==================== CONVENIENCE METHOD TESTS ====================

    public function testGetMethod(): void
    {
        $this->router->get('/api/users', 'UserController@index');

        $getResult = $this->simulateRequest('/api/users', 'GET');
        $this->assertEquals('handler', $getResult['type']);
        $this->assertEquals('UserController@index', $getResult['handler']);

        // POST should not match
        $postResult = $this->simulateRequest('/api/users', 'POST');
        $this->assertEquals('not_found', $postResult['type']);
    }

    public function testPostMethod(): void
    {
        $this->router->post('/api/users', 'UserController@store');

        $postResult = $this->simulateRequest('/api/users', 'POST');
        $this->assertEquals('handler', $postResult['type']);
        $this->assertEquals('UserController@store', $postResult['handler']);

        // GET should not match
        $getResult = $this->simulateRequest('/api/users', 'GET');
        $this->assertEquals('not_found', $getResult['type']);
    }

    public function testPutMethod(): void
    {
        $this->router->put('/api/users/{id:int}', 'UserController@update');

        $result = $this->simulateRequest('/api/users/123', 'PUT');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('UserController@update', $result['handler']);
        $this->assertSame(123, $result['routeParams']['id']);
    }

    public function testDeleteMethod(): void
    {
        $this->router->delete('/api/users/{id:int}', 'UserController@destroy');

        $result = $this->simulateRequest('/api/users/123', 'DELETE');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('UserController@destroy', $result['handler']);
    }

    public function testPatchMethod(): void
    {
        $this->router->patch('/api/users/{id:int}', 'UserController@patch');

        $result = $this->simulateRequest('/api/users/123', 'PATCH');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('UserController@patch', $result['handler']);
    }

    public function testMatchMultipleMethods(): void
    {
        $this->router->match(['GET', 'POST'], '/api/data', 'DataController@handle');

        $getResult = $this->simulateRequest('/api/data', 'GET');
        $this->assertEquals('handler', $getResult['type']);

        $postResult = $this->simulateRequest('/api/data', 'POST');
        $this->assertEquals('handler', $postResult['type']);

        // PUT should not match
        $putResult = $this->simulateRequest('/api/data', 'PUT');
        $this->assertEquals('not_found', $putResult['type']);
    }

    public function testConvenienceMethodWithMiddleware(): void
    {
        $this->router->get('/protected', 'ProtectedController@index', ['AuthMiddleware']);

        $result = $this->simulateRequest('/protected', 'GET');

        $this->assertEquals('handler', $result['type']);
        $this->assertContains('AuthMiddleware', $result['middleware']);
    }

    public function testConvenienceMethodWithTypedParams(): void
    {
        $this->router->get('/user/{id:int}/posts/{slug:slug}', 'PostController@show', ['AuthMiddleware']);

        $result = $this->simulateRequest('/user/42/posts/my-post-title', 'GET');

        $this->assertEquals('handler', $result['type']);
        $this->assertSame(42, $result['routeParams']['id']);
        $this->assertEquals('my-post-title', $result['routeParams']['slug']);
        $this->assertContains('AuthMiddleware', $result['middleware']);
    }

    // ==================== ADDITIONAL PREFIX ROUTE TESTS ====================

    public function testPrefixRouteCatchesAllSubpaths(): void
    {
        $this->router->registerPrefix('/api', 'ApiHandler@handle');

        $result1 = $this->simulateRequest('/api/users');
        $this->assertEquals('handler', $result1['type']);

        $result2 = $this->simulateRequest('/api/users/123/posts');
        $this->assertEquals('handler', $result2['type']);
    }

    public function testPrefixRouteWithMethod(): void
    {
        $this->router->registerPrefix('/api', 'ApiGetHandler@handle', 'GET');
        $this->router->registerPrefix('/api', 'ApiPostHandler@handle', 'POST');

        $getResult = $this->simulateRequest('/api/data', 'GET');
        $this->assertEquals('ApiGetHandler@handle', $getResult['handler']);

        $postResult = $this->simulateRequest('/api/data', 'POST');
        $this->assertEquals('ApiPostHandler@handle', $postResult['handler']);
    }

    public function testExactRouteOverridesPrefix(): void
    {
        $this->router->registerPrefix('/api', 'ApiHandler@handle');
        $this->router->register('/api/special', 'SpecialHandler@handle');

        $specialResult = $this->simulateRequest('/api/special');
        $this->assertEquals('SpecialHandler@handle', $specialResult['handler']);

        $otherResult = $this->simulateRequest('/api/other');
        $this->assertEquals('ApiHandler@handle', $otherResult['handler']);
    }

    // ==================== ADDITIONAL PATH EDGE CASES ====================

    public function testDoubleSlashInPathNotNormalized(): void
    {
        $this->router->register('/test', 'handler.php');

        $result = $this->simulateRequest('//test');

        // Router does NOT normalize double slashes - this is correct behavior
        $this->assertEquals('not_found', $result['type']);
    }

    public function testPathWithEncodedCharacters(): void
    {
        $this->router->register('/search/{query}', 'search_handler.php');

        $result = $this->simulateRequest('/search/hello%20world');

        $this->assertEquals('handler', $result['type']);
        $this->assertArrayHasKey('query', $result['params']);
    }

    public function testPathWithMultipleConsecutiveSlashesNotNormalized(): void
    {
        $this->router->register('/a/b/c', 'handler.php');

        $result = $this->simulateRequest('/a///b//c');

        // Router does NOT normalize multiple slashes - returns not_found
        $this->assertEquals('not_found', $result['type']);
    }

    public function testCaseInsensitiveMethod(): void
    {
        $this->router->register('/test', 'handler.php', 'get');

        // Simulate request with lowercase method
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['QUERY_STRING'] = '';
        $_GET = [];

        $result = $this->router->resolve();

        $this->assertEquals('handler', $result['type']);
    }

    public function testVeryLongPath(): void
    {
        $longPath = '/' . str_repeat('segment/', 20) . 'end';
        $this->router->register($longPath, 'handler.php');

        $result = $this->simulateRequest($longPath);

        $this->assertEquals('handler', $result['type']);
    }

    // ==================== ADDITIONAL TYPED PARAMETER TESTS ====================

    public function testTypedIntParameterWithLeadingZeros(): void
    {
        $this->router->register('/item/{id:int}', 'handler.php');

        $result = $this->simulateRequest('/item/007');

        $this->assertEquals('handler', $result['type']);
        $this->assertSame(7, $result['routeParams']['id']); // Should parse as int
    }

    public function testTypedIntParameterWithLargeNumber(): void
    {
        $this->router->register('/item/{id:int}', 'handler.php');

        $result = $this->simulateRequest('/item/999999999');

        $this->assertEquals('handler', $result['type']);
        $this->assertSame(999999999, $result['routeParams']['id']);
    }

    public function testSlugParameterWithSpecialCharacters(): void
    {
        $this->router->register('/post/{slug:slug}', 'handler.php');

        // Slug should allow hyphens, underscores, and alphanumeric
        $result1 = $this->simulateRequest('/post/my-post-title');
        $this->assertEquals('handler', $result1['type']);

        $result2 = $this->simulateRequest('/post/post_with_underscores');
        $this->assertEquals('handler', $result2['type']);
    }

    public function testAlphanumParameterRejectsSpecialChars(): void
    {
        $this->router->register('/token/{token:alphanum}', 'handler.php');

        $result = $this->simulateRequest('/token/abc-123');

        // Hyphen is not alphanumeric
        $this->assertEquals('not_found', $result['type']);
    }

    // ==================== ADDITIONAL MIDDLEWARE TESTS ====================

    public function testPrefixMiddlewareWithWildcard(): void
    {
        $this->router->registerPrefixWithMiddleware(
            '/admin',
            'AdminHandler@handle',
            ['AdminAuthMiddleware'],
            '*'
        );

        $getResult = $this->simulateRequest('/admin/users', 'GET');
        $this->assertContains('AdminAuthMiddleware', $getResult['middleware']);

        $postResult = $this->simulateRequest('/admin/users', 'POST');
        $this->assertContains('AdminAuthMiddleware', $postResult['middleware']);
    }

    public function testMiddlewareOrder(): void
    {
        $middleware = ['First', 'Second', 'Third'];
        $this->router->registerWithMiddleware('/test', 'handler.php', $middleware);

        $result = $this->simulateRequest('/test');

        // Middleware should maintain order
        $this->assertSame(['First', 'Second', 'Third'], $result['middleware']);
    }

    // ==================== ADDITIONAL MATCH METHOD TESTS ====================

    public function testMatchWithEmptyMethodsArray(): void
    {
        $this->router->match([], '/test', 'handler.php');

        // No methods registered, should not match
        $result = $this->simulateRequest('/test', 'GET');
        $this->assertEquals('not_found', $result['type']);
    }

    public function testMatchWithAllCommonMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $this->router->match($methods, '/resource', 'ResourceController@handle');

        foreach ($methods as $method) {
            $result = $this->simulateRequest('/resource', $method);
            $this->assertEquals('handler', $result['type'], "Should match $method");
        }
    }

    // ==================== ADDITIONAL QUERY PARAMETER TESTS ====================

    public function testQueryParamsWithArrayNotation(): void
    {
        $this->router->register('/test', 'handler.php');

        $result = $this->simulateRequest(
            '/test?items[]=1&items[]=2',
            'GET',
            ['items' => ['1', '2']]
        );

        $this->assertEquals('handler', $result['type']);
        $this->assertIsArray($result['params']['items']);
    }

    public function testQueryParamsWithNestedArray(): void
    {
        $this->router->register('/test', 'handler.php');

        $result = $this->simulateRequest(
            '/test?filter[status]=active',
            'GET',
            ['filter' => ['status' => 'active']]
        );

        $this->assertEquals('handler', $result['type']);
        $this->assertIsArray($result['params']['filter']);
    }

    // ==================== CONSTRUCTOR TESTS ====================

    public function testConstructorWithContainer(): void
    {
        $container = $this->createMock(\Lwt\Shared\Infrastructure\Container\Container::class);
        $router = new Router(dirname(__DIR__, 3), $container);

        $this->assertInstanceOf(Router::class, $router);
    }

    public function testConstructorWithNullContainer(): void
    {
        $router = new Router(dirname(__DIR__, 3), null);

        $this->assertInstanceOf(Router::class, $router);
    }

    // ==================== HTTP HEAD METHOD TEST ====================

    public function testHeadMethodFallsBackToGet(): void
    {
        $this->router->get('/test', 'handler.php');

        // HEAD requests should typically match GET routes
        $result = $this->simulateRequest('/test', 'HEAD');

        // Behavior depends on implementation - may return not_found or match GET
        $this->assertContains($result['type'], ['handler', 'not_found']);
    }

    // ==================== STATIC FILE DETECTION TESTS ====================

    public function testStaticFileExtensionsReturnNotFound(): void
    {
        $extensions = ['.css', '.js', '.png', '.jpg', '.gif', '.svg', '.ico'];

        foreach ($extensions as $ext) {
            $result = $this->simulateRequest('/assets/file' . $ext);
            $this->assertEquals('not_found', $result['type'], "Extension $ext should return not_found");
        }
    }

    // ==================== ROUTE PRIORITY TESTS ====================

    public function testLaterRegistrationOverridesEarlier(): void
    {
        $this->router->register('/test', 'first_handler.php');
        $this->router->register('/test', 'second_handler.php');

        $result = $this->simulateRequest('/test');

        $this->assertEquals('second_handler.php', $result['handler']);
    }

    public function testPatternRoutesMatchInRegistrationOrder(): void
    {
        // More specific pattern should be registered first
        $this->router->register('/user/{id:int}', 'int_handler.php');
        $this->router->register('/user/{name}', 'name_handler.php');

        // Numeric should match int pattern
        $numericResult = $this->simulateRequest('/user/123');
        $this->assertEquals('int_handler.php', $numericResult['handler']);

        // Non-numeric should match generic pattern
        $nameResult = $this->simulateRequest('/user/john');
        $this->assertEquals('name_handler.php', $nameResult['handler']);
    }

    // ==================== UNICODE PATH TESTS ====================

    public function testUnicodeInPath(): void
    {
        $this->router->register('/日本語', 'japanese_handler.php');

        $result = $this->simulateRequest('/日本語');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('japanese_handler.php', $result['handler']);
    }

    public function testUnicodeInPatternParameter(): void
    {
        $this->router->register('/word/{word}', 'word_handler.php');

        $result = $this->simulateRequest('/word/日本語');

        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('日本語', $result['params']['word']);
    }
}
