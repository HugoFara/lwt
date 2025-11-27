<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Http;

require_once __DIR__ . '/../../../../src/backend/Core/settings.php';
require_once __DIR__ . '/../../../../src/backend/Core/Http/url_utilities.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for url_utilities.php functions
 */
final class UrlUtilitiesTest extends TestCase
{
    /**
     * Test if the language from dictionary feature is properly working.
     */
    public function testLangFromDict(): void
    {
        $urls = [
            'http://translate.google.com/lwt_term?ie=UTF-8&sl=ar&tl=en&text=&lwt_popup=true',
            'http://localhost/lwt/ggl.php/?sl=ar&tl=hr&text=',
            'http://localhost:5000/?lwt_translator=libretranslate&source=ar&target=en&q=lwt_term',
            'ggl.php?sl=ar&tl=en&text=###'
        ];
        foreach ($urls as $url) {
            $this->assertSame("ar", langFromDict($url));
        }
    }

    /**
     * Test langFromDict with edge cases
     */
    public function testLangFromDictEdgeCases(): void
    {
        // URL without language parameter
        $this->assertEquals('', langFromDict('http://example.com/page.php'));

        // Malformed URL
        $this->assertEquals('', langFromDict('not-a-url'));

        // Multiple sl parameters (parse_str uses the last one)
        $url = 'http://example.com/?sl=en&sl=fr';
        $result = langFromDict($url);
        // parse_str uses the last value when there are duplicates
        $this->assertEquals('fr', $result);

        // URL with fragment
        $this->assertEquals('de', langFromDict('http://example.com/?sl=de#fragment'));

        // Case sensitivity - query parameters are case-sensitive
        // 'SL' is different from 'sl', so this should return empty
        $this->assertEquals('', langFromDict('http://example.com/?SL=en'));
    }

    /**
     * Test URL base extraction
     */
    public function testUrlBase(): void
    {
        // Mock server variables for testing
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/lwt/index.php';

        $base = url_base();
        $this->assertStringStartsWith('http://', $base);
        $this->assertStringEndsWith('/', $base);
        $this->assertStringContainsString('localhost', $base);
    }

    /**
     * Test url_base with different server configurations
     */
    public function testUrlBaseVariousConfigurations(): void
    {
        // Save original values
        $origHost = $_SERVER['HTTP_HOST'] ?? null;
        $origUri = $_SERVER['REQUEST_URI'] ?? null;
        $origHttps = $_SERVER['HTTPS'] ?? null;

        // Test with HTTPS
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'secure.example.com';
        $_SERVER['REQUEST_URI'] = '/lwt/page.php';

        $base = url_base();
        $this->assertStringStartsWith('https://', $base);
        $this->assertStringContainsString('secure.example.com', $base);

        // Test without HTTPS
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test/index.php';

        $base = url_base();
        $this->assertStringStartsWith('http://', $base);
        $this->assertStringContainsString('example.com', $base);

        // Test with port number
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $_SERVER['REQUEST_URI'] = '/lwt/index.php';

        $base = url_base();
        $this->assertStringContainsString('localhost:8080', $base);

        // Restore original values
        if ($origHost !== null) {
            $_SERVER['HTTP_HOST'] = $origHost;
        }
        if ($origUri !== null) {
            $_SERVER['REQUEST_URI'] = $origUri;
        }
        if ($origHttps !== null) {
            $_SERVER['HTTPS'] = $origHttps;
        }
    }

    /**
     * Test target language extraction from dictionary URL
     */
    public function testTargetLangFromDict(): void
    {
        // Google Translate URLs
        $this->assertEquals('en', targetLangFromDict('http://translate.google.com/?sl=ar&tl=en&text=test'));
        $this->assertEquals('fr', targetLangFromDict('http://localhost/ggl.php?sl=ar&tl=fr&text='));

        // LibreTranslate URLs
        $this->assertEquals('en', targetLangFromDict('http://localhost:5000/?lwt_translator=libretranslate&source=ar&target=en&q=test'));

        // Empty URL
        $this->assertEquals('', targetLangFromDict(''));
    }

    /**
     * Test targetLangFromDict with edge cases
     */
    public function testTargetLangFromDictEdgeCases(): void
    {
        // URL without target parameter
        $this->assertEquals('', targetLangFromDict('http://example.com/page.php'));

        // Malformed URL
        $this->assertEquals('', targetLangFromDict('not-a-url'));

        // URL with only source language
        $this->assertEquals('', targetLangFromDict('http://example.com/?sl=en'));

        // LibreTranslate without target
        $this->assertEquals('', targetLangFromDict('http://localhost:5000/?source=en'));

        // URL with fragment
        $this->assertEquals('es', targetLangFromDict('http://example.com/?tl=es#fragment'));
    }
}
