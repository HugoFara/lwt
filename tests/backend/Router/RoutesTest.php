<?php

declare(strict_types=1);

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
 * 3. Handle legacy redirects properly
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

        $this->router = new Router();
        $this->basePath = dirname(__DIR__, 3); // Go up to project root

        // Load routes
        $registerRoutes = require $this->basePath . '/src/backend/Router/routes.php';
        $registerRoutes($this->router);

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
        $this->assertEquals('HomeController@index', $result['handler']);
    }

    public function testIndexPhpRoute(): void
    {
        $result = $this->simulateRequest('/index.php');
        $this->assertEquals('handler', $result['type']);
        $this->assertEquals('HomeController@index', $result['handler']);
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
        return [
            'text read' => ['/text/read', 'TextController@read'],
            'text edit' => ['/text/edit', 'TextController@edit'],
            'texts list' => ['/texts', 'TextController@edit'],
            'text display' => ['/text/display', 'TextController@display'],
            'text print' => ['/text/print', 'TextController@printText'],
            'text print-plain' => ['/text/print-plain', 'TextController@printPlain'],
            'text import-long' => ['/text/import-long', 'TextController@importLong'],
            'text set-mode' => ['/text/set-mode', 'TextController@setMode'],
            'text check' => ['/text/check', 'TextController@check'],
            'text archived' => ['/text/archived', 'TextController@archived'],
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
        return [
            'word edit' => ['/word/edit', 'WordController@edit'],
            'words edit list' => ['/words/edit', 'WordController@listEdit'],
            'word edit-multi' => ['/word/edit-multi', 'WordController@editMulti'],
            'word delete' => ['/word/delete', 'WordController@delete'],
            'word delete-multi' => ['/word/delete-multi', 'WordController@deleteMulti'],
            'words list' => ['/words', 'WordController@all'],
            'word new' => ['/word/new', 'WordController@create'],
            'word show' => ['/word/show', 'WordController@show'],
            'word insert-wellknown' => ['/word/insert-wellknown', 'WordController@insertWellknown'],
            'word insert-ignore' => ['/word/insert-ignore', 'WordController@insertIgnore'],
            'word inline-edit' => ['/word/inline-edit', 'WordController@inlineEdit'],
            'word bulk-translate' => ['/word/bulk-translate', 'WordController@bulkTranslate'],
            'word set-status' => ['/word/set-status', 'WordController@setStatus'],
            'word upload' => ['/word/upload', 'WordController@upload'],
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
            'test index' => ['/test', 'TestController@index'],
            'test set-status' => ['/test/set-status', 'TestController@setStatus'],
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
            'languages list' => ['/languages', 'LanguageController@index'],
            'languages select-pair' => ['/languages/select-pair', 'LanguageController@selectPair'],
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
            'tags list' => ['/tags', 'TagsController@index'],
            'tags text' => ['/tags/text', 'TagsController@textTags'],
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
            'feeds index' => ['/feeds', 'FeedsController@index'],
            'feeds edit' => ['/feeds/edit', 'FeedsController@edit'],
            'feeds wizard' => ['/feeds/wizard', 'FeedsController@wizard'],
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
            'admin backup' => ['/admin/backup', 'AdminController@backup'],
            'admin wizard' => ['/admin/wizard', 'AdminController@wizard'],
            'admin statistics' => ['/admin/statistics', 'AdminController@statistics'],
            'admin install-demo' => ['/admin/install-demo', 'AdminController@installDemo'],
            'admin settings' => ['/admin/settings', 'AdminController@settings'],
            'admin settings hover' => ['/admin/settings/hover', 'AdminController@settingsHover'],
            'admin settings tts' => ['/admin/settings/tts', 'AdminController@settingsTts'],
            'admin tables' => ['/admin/tables', 'AdminController@tables'],
            'admin server-data' => ['/admin/server-data', 'AdminController@serverData'],
        ];
    }

    // ==================== MOBILE ROUTES TESTS ====================

    /**
     * @dataProvider mobileRoutesProvider
     */
    public function testMobileRoutes(string $path, string $expectedHandler): void
    {
        $result = $this->simulateRequest($path);
        $this->assertEquals('handler', $result['type'], "Route {$path} should resolve to handler");
        $this->assertEquals($expectedHandler, $result['handler']);
        $this->assertHandlerFileExists($result['handler']);
    }

    public static function mobileRoutesProvider(): array
    {
        return [
            'mobile index' => ['/mobile', 'MobileController@index'],
            'mobile start' => ['/mobile/start', 'MobileController@start'],
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
            'wordpress start' => ['/wordpress/start', 'WordPressController@start'],
            'wordpress stop' => ['/wordpress/stop', 'WordPressController@stop'],
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
            'api translate' => ['/api/translate', 'ApiController@translate'],
            'api google' => ['/api/google', 'ApiController@google'],
            'api glosbe' => ['/api/glosbe', 'ApiController@glosbe'],
        ];
    }

    // ==================== LEGACY URL REDIRECT TESTS ====================

    /**
     * @dataProvider legacyTextRedirectsProvider
     */
    public function testLegacyTextRedirects(string $legacyPath, string $expectedRedirect): void
    {
        $result = $this->simulateRequest($legacyPath);
        $this->assertEquals('redirect', $result['type'], "Legacy path {$legacyPath} should redirect");
        $this->assertEquals($expectedRedirect, $result['url']);
        $this->assertEquals(301, $result['code']);
    }

    public static function legacyTextRedirectsProvider(): array
    {
        return [
            'do_text.php' => ['/do_text.php', '/text/read'],
            'do_text_header.php' => ['/do_text_header.php', '/text/read'],
            'do_text_text.php' => ['/do_text_text.php', '/text/read'],
            'edit_texts.php' => ['/edit_texts.php', '/text/edit'],
            'display_impr_text.php' => ['/display_impr_text.php', '/text/display'],
            'display_impr_text_header.php' => ['/display_impr_text_header.php', '/text/display'],
            'display_impr_text_text.php' => ['/display_impr_text_text.php', '/text/display'],
            'print_impr_text.php' => ['/print_impr_text.php', '/text/print'],
            'print_text.php' => ['/print_text.php', '/text/print-plain'],
            'long_text_import.php' => ['/long_text_import.php', '/text/import-long'],
            'set_text_mode.php' => ['/set_text_mode.php', '/text/set-mode'],
            'check_text.php' => ['/check_text.php', '/text/check'],
            'edit_archivedtexts.php' => ['/edit_archivedtexts.php', '/text/archived'],
        ];
    }

    /**
     * @dataProvider legacyWordRedirectsProvider
     */
    public function testLegacyWordRedirects(string $legacyPath, string $expectedRedirect): void
    {
        $result = $this->simulateRequest($legacyPath);
        $this->assertEquals('redirect', $result['type'], "Legacy path {$legacyPath} should redirect");
        $this->assertEquals($expectedRedirect, $result['url']);
        $this->assertEquals(301, $result['code']);
    }

    public static function legacyWordRedirectsProvider(): array
    {
        return [
            'edit_word.php' => ['/edit_word.php', '/word/edit'],
            'edit_tword.php' => ['/edit_tword.php', '/word/edit'],
            'edit_words.php' => ['/edit_words.php', '/words/edit'],
            'edit_mword.php' => ['/edit_mword.php', '/word/edit-multi'],
            'delete_word.php' => ['/delete_word.php', '/word/delete'],
            'delete_mword.php' => ['/delete_mword.php', '/word/delete-multi'],
            'all_words_wellknown.php' => ['/all_words_wellknown.php', '/words'],
            'new_word.php' => ['/new_word.php', '/word/new'],
            'show_word.php' => ['/show_word.php', '/word/show'],
            'insert_word_wellknown.php' => ['/insert_word_wellknown.php', '/word/insert-wellknown'],
            'insert_word_ignore.php' => ['/insert_word_ignore.php', '/word/insert-ignore'],
            'inline_edit.php' => ['/inline_edit.php', '/word/inline-edit'],
            'bulk_translate_words.php' => ['/bulk_translate_words.php', '/word/bulk-translate'],
            'set_word_status.php' => ['/set_word_status.php', '/word/set-status'],
            'upload_words.php' => ['/upload_words.php', '/word/upload'],
        ];
    }

    /**
     * @dataProvider legacyTestRedirectsProvider
     */
    public function testLegacyTestRedirects(string $legacyPath, string $expectedRedirect): void
    {
        $result = $this->simulateRequest($legacyPath);
        $this->assertEquals('redirect', $result['type'], "Legacy path {$legacyPath} should redirect");
        $this->assertEquals($expectedRedirect, $result['url']);
        $this->assertEquals(301, $result['code']);
    }

    public static function legacyTestRedirectsProvider(): array
    {
        return [
            'do_test.php' => ['/do_test.php', '/test'],
            'do_test_header.php' => ['/do_test_header.php', '/test'],
            'do_test_table.php' => ['/do_test_table.php', '/test'],
            'do_test_test.php' => ['/do_test_test.php', '/test'],
            'set_test_status.php' => ['/set_test_status.php', '/test/set-status'],
        ];
    }

    /**
     * @dataProvider legacyOtherRedirectsProvider
     */
    public function testLegacyOtherRedirects(string $legacyPath, string $expectedRedirect): void
    {
        $result = $this->simulateRequest($legacyPath);
        $this->assertEquals('redirect', $result['type'], "Legacy path {$legacyPath} should redirect");
        $this->assertEquals($expectedRedirect, $result['url']);
        $this->assertEquals(301, $result['code']);
    }

    public static function legacyOtherRedirectsProvider(): array
    {
        return [
            // Language
            'edit_languages.php' => ['/edit_languages.php', '/languages'],
            'select_lang_pair.php' => ['/select_lang_pair.php', '/languages/select-pair'],

            // Tags
            'edit_tags.php' => ['/edit_tags.php', '/tags'],
            'edit_texttags.php' => ['/edit_texttags.php', '/tags/text'],

            // Feeds
            'do_feeds.php' => ['/do_feeds.php', '/feeds'],
            'edit_feeds.php' => ['/edit_feeds.php', '/feeds/edit'],
            'feed_wizard.php' => ['/feed_wizard.php', '/feeds/wizard'],

            // Admin
            'backup_restore.php' => ['/backup_restore.php', '/admin/backup'],
            'database_wizard.php' => ['/database_wizard.php', '/admin/wizard'],
            'statistics.php' => ['/statistics.php', '/admin/statistics'],
            'install_demo.php' => ['/install_demo.php', '/admin/install-demo'],
            'settings.php' => ['/settings.php', '/admin/settings'],
            'set_word_on_hover.php' => ['/set_word_on_hover.php', '/admin/settings/hover'],
            'text_to_speech_settings.php' => ['/text_to_speech_settings.php', '/admin/settings/tts'],
            'table_set_management.php' => ['/table_set_management.php', '/admin/tables'],
            'server_data.php' => ['/server_data.php', '/admin/server-data'],

            // Mobile
            'mobile.php' => ['/mobile.php', '/mobile'],
            'start.php' => ['/start.php', '/mobile/start'],

            // WordPress
            'wp_lwt_start.php' => ['/wp_lwt_start.php', '/wordpress/start'],
            'wp_lwt_stop.php' => ['/wp_lwt_stop.php', '/wordpress/stop'],

            // API
            'api.php' => ['/api.php', '/api/v1'],
            'trans.php' => ['/trans.php', '/api/translate'],
            'ggl.php' => ['/ggl.php', '/api/google'],
            'glosbe_api.php' => ['/glosbe_api.php', '/api/glosbe'],
        ];
    }

    // ==================== LEGACY REDIRECT WITH QUERY STRING TESTS ====================

    public function testLegacyRedirectPreservesQueryString(): void
    {
        $result = $this->simulateRequest('/do_text.php', 'GET', 'text=123&start=1');

        $this->assertEquals('redirect', $result['type']);
        $this->assertEquals('/text/read?text=123&start=1', $result['url']);
    }

    public function testLegacyRedirectWithComplexQueryString(): void
    {
        $result = $this->simulateRequest('/edit_words.php', 'GET', 'lang=1&filter=new&sort=date&order=desc');

        $this->assertEquals('redirect', $result['type']);
        $this->assertEquals('/words/edit?lang=1&filter=new&sort=date&order=desc', $result['url']);
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
            '/word/delete-multi' => 'should use hyphens',
            '/word/insert-wellknown' => 'should use hyphens',
            '/word/insert-ignore' => 'should use hyphens',
            '/word/inline-edit' => 'should use hyphens',
            '/word/bulk-translate' => 'should use hyphens',
            '/word/set-status' => 'should use hyphens',
            '/test/set-status' => 'should use hyphens',
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
