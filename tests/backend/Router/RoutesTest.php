<?php declare(strict_types=1);
namespace Tests\Router;

require_once __DIR__ . '/../../../src/backend/Router/Router.php';

use Lwt\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for all route definitions
 *
 * Tests that all routes defined in routes.php:
 * 1. Resolve correctly to their handlers
 * 2. Have existing handler files (for file handlers) or valid controller format
 *
 * @since 3.0.0 Updated to expect controller handlers instead of legacy file paths
 */
class RoutesTest extends TestCase
{
    private Router $router;
    private string $basePath;
    private array $originalServer;
    private array $originalGet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = dirname(__DIR__, 3); // Go up to project root
        $this->router = new Router($this->basePath);

        // Load routes
        require_once $this->basePath . '/src/backend/Router/routes.php';
        \Lwt\Router\registerRoutes($this->router);

        // Save original superglobals
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        parent::tearDown();
    }

    /**
     * Helper to simulate a request
     */
    private function simulateRequest(
        string $uri,
        string $method = 'GET',
        string $queryString = ''
    ): array {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['QUERY_STRING'] = $queryString;
        $_GET = [];
        if ($queryString) {
            parse_str($queryString, $_GET);
        }

        return $this->router->resolve();
    }

    /**
     * Helper to check if a handler file exists
     */
    private function assertHandlerFileExists(string $handler): void
    {
        // Skip controller format (e.g., UserController@index)
        if (str_contains($handler, '@')) {
            return;
        }

        $fullPath = $this->basePath . '/' . $handler;
        $this->assertFileExists(
            $fullPath,
            "Handler file does not exist: {$handler}"
        );
    }

    // ==================== HOME PAGE TESTS ====================

    public function testHomePageRoute(): void
    {
        $result = $this->simulateRequest('/');
        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('Lwt\\Modules\\Home\\Http\\HomeController@index', $result['handler']);
    }

    public function testIndexPhpRoute(): void
    {
        $result = $this->simulateRequest('/index.php');
        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('Lwt\\Modules\\Home\\Http\\HomeController@index', $result['handler']);
    }

    /**
     * @dataProvider indexPhpWithPathInfoProvider
     */
    public function testIndexPhpWithPathInfoRedirect(string $path, string $expectedRedirect): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('redirect', $result['type'], "Route {$path} should be a redirect");
        $this->assertEquals($expectedRedirect, $result['url']);
        $this->assertEquals(301, $result['code']);
    }

    public static function indexPhpWithPathInfoProvider(): array
    {
        return [
            ['/index.php/admin/install-demo', '/admin/install-demo'],
            ['/index.php/feeds', '/feeds'],
            ['/index.php/feeds/edit', '/feeds/edit'],
            ['/index.php/admin/statistics', '/admin/statistics'],
            ['/index.php/text/read', '/text/read'],
        ];
    }

    // ==================== TEXT ROUTES TESTS ====================

    /**
     * @dataProvider textRoutesProvider
     */
    public function testTextRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function textRoutesProvider(): array
    {
        $textController = 'Lwt\\Modules\\Text\\Http\\TextController';
        $textPrintController = 'Lwt\\Modules\\Text\\Http\\TextPrintController';
        return [
            'text read' => ['/text/read', "{$textController}@read"],
            'text edit' => ['/text/edit', "{$textController}@edit"],
            'texts list' => ['/texts', "{$textController}@edit"],
            'text display' => ['/text/display', "{$textController}@display"],
            'text print' => ['/text/print', "{$textPrintController}@printAnnotated"],
            'text print-plain' => ['/text/print-plain', "{$textPrintController}@printPlain"],
            'text import-long' => ['/text/import-long', "{$textController}@importLong"],
            'text set-mode' => ['/text/set-mode', "{$textController}@setMode"],
            'text check' => ['/text/check', "{$textController}@check"],
            'text archived' => ['/text/archived', "{$textController}@archived"],
        ];
    }

    // ==================== WORD ROUTES TESTS ====================

    /**
     * @dataProvider wordRoutesProvider
     */
    public function testWordRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function wordRoutesProvider(): array
    {
        $vocabularyController = 'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController';
        return [
            'word edit' => ['/word/edit', "{$vocabularyController}@editWord"],
            'word edit-term' => ['/word/edit-term', "{$vocabularyController}@editTerm"],
            'words edit list' => ['/words/edit', "{$vocabularyController}@listEditAlpine"],
            'word edit-multi' => ['/word/edit-multi', "{$vocabularyController}@editMulti"],
            'words list' => ['/words', "{$vocabularyController}@listEditAlpine"],
            'word new' => ['/word/new', "{$vocabularyController}@createWord"],
            'word show' => ['/word/show', "{$vocabularyController}@showWord"],
            'word inline-edit' => ['/word/inline-edit', "{$vocabularyController}@inlineEdit"],
            'word bulk-translate' => ['/word/bulk-translate', "{$vocabularyController}@bulkTranslate"],
            'word set-all-status' => ['/word/set-all-status', "{$vocabularyController}@markAllWords"],
            'word upload' => ['/word/upload', "{$vocabularyController}@upload"],
        ];
    }

    // ==================== TEST ROUTES TESTS ====================

    /**
     * @dataProvider reviewTestRoutesProvider
     */
    public function testReviewTestRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function reviewTestRoutesProvider(): array
    {
        return [
            'test index' => ['/test', 'Lwt\\Modules\\Review\\Http\\ReviewController@index'],
        ];
    }

    // ==================== LANGUAGE ROUTES TESTS ====================

    /**
     * @dataProvider languageRoutesProvider
     */
    public function testLanguageRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function languageRoutesProvider(): array
    {
        return [
            'languages list' => ['/languages', 'Lwt\\Modules\\Language\\Http\\LanguageController@index'],
        ];
    }

    // ==================== TAG ROUTES TESTS ====================

    /**
     * @dataProvider tagRoutesProvider
     */
    public function testTagRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function tagRoutesProvider(): array
    {
        return [
            'tags list' => ['/tags', 'Lwt\\Modules\\Tags\\Http\\TermTagController@index'],
            'tags text' => ['/tags/text', 'Lwt\\Modules\\Tags\\Http\\TextTagController@index'],
        ];
    }

    // ==================== FEED ROUTES TESTS ====================

    /**
     * @dataProvider feedRoutesProvider
     */
    public function testFeedRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function feedRoutesProvider(): array
    {
        return [
            'feeds index' => ['/feeds', 'Lwt\\Modules\\Feed\\Http\\FeedController@index'],
            'feeds edit' => ['/feeds/edit', 'Lwt\\Modules\\Feed\\Http\\FeedController@edit'],
            'feeds wizard' => ['/feeds/wizard', 'Lwt\\Modules\\Feed\\Http\\FeedController@wizard'],
        ];
    }

    // ==================== ADMIN ROUTES TESTS ====================

    /**
     * @dataProvider adminRoutesProvider
     */
    public function testAdminRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function adminRoutesProvider(): array
    {
        return [
            'admin backup' => ['/admin/backup', 'Lwt\\Modules\\Admin\\Http\\AdminController@backup'],
            'admin wizard' => ['/admin/wizard', 'Lwt\\Modules\\Admin\\Http\\AdminController@wizard'],
            'admin statistics' => ['/admin/statistics', 'Lwt\\Modules\\Admin\\Http\\AdminController@statistics'],
            'admin install-demo' => ['/admin/install-demo', 'Lwt\\Modules\\Admin\\Http\\AdminController@installDemo'],
            'admin settings' => ['/admin/settings', 'Lwt\\Modules\\Admin\\Http\\AdminController@settings'],
            'admin server-data' => ['/admin/server-data', 'Lwt\\Modules\\Admin\\Http\\AdminController@serverData'],
        ];
    }

    // ==================== WORDPRESS ROUTES TESTS ====================

    /**
     * @dataProvider wordpressRoutesProvider
     */
    public function testWordpressRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function wordpressRoutesProvider(): array
    {
        return [
            'wordpress start' => ['/wordpress/start', 'Lwt\\Modules\\User\\Http\\WordPressController@start'],
            'wordpress stop' => ['/wordpress/stop', 'Lwt\\Modules\\User\\Http\\WordPressController@stop'],
        ];
    }

    // ==================== API ROUTES TESTS ====================

    /**
     * @dataProvider apiRoutesProvider
     */
    public function testApiRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function apiRoutesProvider(): array
    {
        return [
            'api v1' => ['/api/v1', 'ApiController@v1'],
            'api translate' => ['/api/translate', 'Lwt\\Modules\\Dictionary\\Http\\TranslationController@translate'],
            'api google' => ['/api/google', 'Lwt\\Modules\\Dictionary\\Http\\TranslationController@google'],
            'api glosbe' => ['/api/glosbe', 'Lwt\\Modules\\Dictionary\\Http\\TranslationController@glosbe'],
        ];
    }

    // ==================== 404 TESTS ====================

    public function testNonExistentRouteReturns404(): void
    {
        $result = $this->simulateRequest('/nonexistent/route');

        $this->assertEquals('not_found', $result['type']);
        $this->assertEquals('/nonexistent/route', $result['path']);
    }

    public function testUnregisteredPhpFileReturns404(): void
    {
        $result = $this->simulateRequest('/some_unregistered_file.php');

        $this->assertEquals('not_found', $result['type']);
    }

    // ==================== ALL HANDLER FILES EXIST TEST ====================

    public function testAllHandlerFilesExist(): void
    {
        $routesFile = $this->basePath . '/src/backend/Router/routes.php';

        // Extract all handler paths from routes.php
        $content = file_get_contents($routesFile);

        // Match patterns like: $router->register('/path', 'src/php/Legacy/file.php')
        preg_match_all(
            "/register\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]/",
            $content,
            $matches
        );

        $handlers = array_unique($matches[2]);

        // Known missing files (documented issues to fix)
        // If any files are added here, add corresponding test in testKnownRoutingIssues()
        $knownMissingFiles = [];

        $missingFiles = [];

        foreach ($handlers as $handler) {
            // Skip controller format
            if (str_contains($handler, '@')) {
                continue;
            }

            $fullPath = $this->basePath . '/' . $handler;
            if (!file_exists($fullPath)) {
                $missingFiles[] = $handler;
            }
        }

        // Filter out known missing files for the assertion
        $unexpectedMissingFiles = array_diff($missingFiles, array_keys($knownMissingFiles));

        $this->assertEmpty(
            $unexpectedMissingFiles,
            "Unexpected missing handler files: " . implode(', ', $unexpectedMissingFiles)
        );

        // Document known missing files
        // These are tracked in testKnownRoutingIssues() to ensure they get fixed
        $this->assertEquals(
            array_keys($knownMissingFiles),
            $missingFiles,
            "Unexpected missing files found or known issues were fixed"
        );
    }

    /**
     * Test to document and track known routing issues
     *
     * When a route has a known issue (e.g., missing handler file),
     * add it here to track and ensure it gets fixed.
     */
    public function testKnownRoutingIssues(): void
    {
        // No known routing issues - all routes have valid handlers
        $this->assertTrue(true, 'No known routing issues');
    }

    // ==================== ROUTE CONSISTENCY TESTS ====================

    public function testAllRoutesHaveConsistentNaming(): void
    {
        // This test ensures naming conventions are followed
        $routes = [
            // New routes should use hyphens for word separation
            '/text/import-long' => 'should use hyphens',
            '/word/edit-multi' => 'should use hyphens',
            '/word/inline-edit' => 'should use hyphens',
            '/word/bulk-translate' => 'should use hyphens',
            '/admin/install-demo' => 'should use hyphens',
            '/admin/server-data' => 'should use hyphens',
        ];

        foreach ($routes as $route => $message) {
            $result = $this->simulateRequest($route);
            $this->assertEquals(
                'handler',
                $result['type'],
                "Route {$route} should exist ({$message})"
            );
        }
    }
}
