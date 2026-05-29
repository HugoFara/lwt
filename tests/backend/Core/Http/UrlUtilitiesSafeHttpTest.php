<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Http;

use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use PHPUnit\Framework\TestCase;

/**
 * Tests for `UrlUtilities::safeHttpGet` and its redirect resolver.
 *
 * We can't easily spin up an HTTP server inside PHPUnit, so coverage
 * here focuses on the *rejection* paths (validation, scheme, private
 * IPs) and on the pure `resolveRelativeUrl` helper. The end-to-end
 * "real fetch + 302 → private IP" path is covered by the integration
 * tests in the matching Feed/WebPageExtractor suites.
 */
final class UrlUtilitiesSafeHttpTest extends TestCase
{
    public function testRejectsNonHttpScheme(): void
    {
        $this->assertNull(UrlUtilities::safeHttpGet('file:///etc/passwd'));
        $this->assertNull(UrlUtilities::safeHttpGet('gopher://example.com/'));
        $this->assertNull(UrlUtilities::safeHttpGet('ftp://example.com/'));
    }

    public function testRejectsInternalHostnames(): void
    {
        $this->assertNull(UrlUtilities::safeHttpGet('http://localhost/'));
        $this->assertNull(UrlUtilities::safeHttpGet('http://localhost.localdomain/'));
        $this->assertNull(UrlUtilities::safeHttpGet('http://foo.local/'));
        $this->assertNull(UrlUtilities::safeHttpGet('http://foo.internal/'));
    }

    public function testRejectsLoopbackIpLiterals(): void
    {
        $this->assertNull(UrlUtilities::safeHttpGet('http://127.0.0.1/'));
        $this->assertNull(UrlUtilities::safeHttpGet('http://127.0.0.1:8080/admin'));
        $this->assertNull(UrlUtilities::safeHttpGet('http://[::1]/'));
    }

    public function testRejectsPrivateRanges(): void
    {
        $this->assertNull(UrlUtilities::safeHttpGet('http://10.0.0.1/'));
        $this->assertNull(UrlUtilities::safeHttpGet('http://172.16.0.1/'));
        $this->assertNull(UrlUtilities::safeHttpGet('http://192.168.1.1/'));
        // AWS / GCE / DigitalOcean metadata endpoint
        $this->assertNull(UrlUtilities::safeHttpGet('http://169.254.169.254/latest/meta-data/'));
    }

    public function testRejectsEmptyUrl(): void
    {
        $this->assertNull(UrlUtilities::safeHttpGet(''));
        $this->assertNull(UrlUtilities::safeHttpGet('   '));
    }

    public function testResolveRelativeUrlAbsolute(): void
    {
        $resolved = $this->callResolve('https://example.com/feed.xml', 'https://other.com/x');
        $this->assertSame('https://other.com/x', $resolved);
    }

    public function testResolveRelativeUrlProtocolRelative(): void
    {
        $resolved = $this->callResolve('https://example.com/feed.xml', '//cdn.example.com/x');
        $this->assertSame('https://cdn.example.com/x', $resolved);
    }

    public function testResolveRelativeUrlAbsolutePath(): void
    {
        $resolved = $this->callResolve('https://example.com/dir/feed.xml', '/new-path');
        $this->assertSame('https://example.com/new-path', $resolved);
    }

    public function testResolveRelativeUrlRelativePath(): void
    {
        $resolved = $this->callResolve('https://example.com/dir/feed.xml', 'next-page');
        $this->assertSame('https://example.com/dir/next-page', $resolved);
    }

    public function testResolveRelativeUrlPreservesPort(): void
    {
        $resolved = $this->callResolve('https://example.com:8443/feed.xml', '/x');
        $this->assertSame('https://example.com:8443/x', $resolved);
    }

    /**
     * Call the private resolveRelativeUrl helper via reflection.
     */
    private function callResolve(string $base, string $relative): string
    {
        $method = new \ReflectionMethod(UrlUtilities::class, 'resolveRelativeUrl');
        /** @var string $result */
        $result = $method->invoke(null, $base, $relative);
        return $result;
    }
}
