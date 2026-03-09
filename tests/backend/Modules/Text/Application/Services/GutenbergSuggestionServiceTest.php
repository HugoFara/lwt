<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Text\Application\Services;

use Lwt\Modules\Text\Application\Services\GutenbergSuggestionService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for GutenbergSuggestionService.
 *
 * Tests the pure logic methods (caching, path generation) without
 * requiring external API calls or database.
 */
class GutenbergSuggestionServiceTest extends TestCase
{
    private GutenbergSuggestionService $service;

    protected function setUp(): void
    {
        $this->service = new GutenbergSuggestionService();
    }

    // =========================================================================
    // cachePath tests
    // =========================================================================

    public function testCachePathReturnsAbsolutePath(): void
    {
        $path = $this->callCachePath('en_p1');
        $this->assertStringStartsWith(sys_get_temp_dir(), $path);
    }

    public function testCachePathContainsCacheDirectory(): void
    {
        $path = $this->callCachePath('fr_p2');
        $this->assertStringContainsString('lwt_gutenberg_cache', $path);
    }

    public function testCachePathEndsWithJson(): void
    {
        $path = $this->callCachePath('de_p1');
        $this->assertStringEndsWith('.json', $path);
    }

    public function testCachePathSanitizesSpecialChars(): void
    {
        $path = $this->callCachePath('en/../etc/passwd');
        $this->assertStringNotContainsString('..', $path);
        $this->assertStringNotContainsString('/', basename($path, '.json'));
    }

    public function testCachePathDifferentKeysProduceDifferentPaths(): void
    {
        $path1 = $this->callCachePath('en_p1');
        $path2 = $this->callCachePath('en_p2');
        $this->assertNotSame($path1, $path2);
    }

    // =========================================================================
    // writeCache + readCache roundtrip tests
    // =========================================================================

    public function testWriteAndReadCacheRoundtrip(): void
    {
        $key = 'test_roundtrip_' . uniqid();
        $data = ['results' => [['id' => 1, 'title' => 'Test Book']], 'count' => 1, 'next' => false];

        $this->callWriteCache($key, $data);
        $cached = $this->callReadCache($key);

        $this->assertNotNull($cached);
        $this->assertSame(1, $cached['count']);
        $this->assertSame('Test Book', $cached['results'][0]['title']);

        // Clean up
        @unlink($this->callCachePath($key));
    }

    public function testReadCacheReturnsNullForMissingKey(): void
    {
        $cached = $this->callReadCache('nonexistent_key_' . uniqid());
        $this->assertNull($cached);
    }

    public function testReadCacheReturnsNullForExpiredEntry(): void
    {
        $key = 'test_expired_' . uniqid();
        $path = $this->callCachePath($key);

        // Write data then backdate the file
        $this->callWriteCache($key, ['test' => true]);
        touch($path, time() - 90000); // > 24h ago

        $cached = $this->callReadCache($key);
        $this->assertNull($cached);

        // Clean up
        @unlink($path);
    }

    public function testCacheHandlesUnicodeData(): void
    {
        $key = 'test_unicode_' . uniqid();
        $data = ['results' => [['title' => 'Gödel, Escher, Bach — 日本語']]];

        $this->callWriteCache($key, $data);
        $cached = $this->callReadCache($key);

        $this->assertNotNull($cached);
        $this->assertSame('Gödel, Escher, Bach — 日本語', $cached['results'][0]['title']);

        // Clean up
        @unlink($this->callCachePath($key));
    }

    // =========================================================================
    // getSuggestions validation tests
    // =========================================================================

    public function testGetSuggestionsRequiresValidLanguage(): void
    {
        // Language ID 999999 won't exist — should return error
        $result = $this->service->getSuggestions(999999);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('language code', $result['error']);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    private function callCachePath(string $key): string
    {
        $method = new ReflectionMethod(GutenbergSuggestionService::class, 'cachePath');
        return $method->invoke($this->service, $key);
    }

    private function callWriteCache(string $key, array $data): void
    {
        $method = new ReflectionMethod(GutenbergSuggestionService::class, 'writeCache');
        $method->invoke($this->service, $key, $data);
    }

    private function callReadCache(string $key): ?array
    {
        $method = new ReflectionMethod(GutenbergSuggestionService::class, 'readCache');
        return $method->invoke($this->service, $key);
    }
}
