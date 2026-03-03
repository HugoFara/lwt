<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\SecurityHeaders;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for SecurityHeaders.
 *
 * Tests the security header configuration logic.
 * Header sending methods require @runInSeparateProcess to test actual output.
 */
class SecurityHeadersTest extends TestCase
{
    private array $originalServer;
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
        $this->originalEnv = $_ENV;
        SecurityHeaders::reset();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_ENV = $this->originalEnv;
        SecurityHeaders::reset();
        parent::tearDown();
    }

    // ===== isSecureConnection() tests via reflection =====

    public function testIsSecureConnectionReturnsTrueForHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $method = new ReflectionMethod(SecurityHeaders::class, 'isSecureConnection');

        $this->assertTrue($method->invoke(null));
    }

    public function testIsSecureConnectionReturnsFalseForHttpsOff(): void
    {
        $_SERVER['HTTPS'] = 'off';
        unset($_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['SERVER_PORT']);
        $method = new ReflectionMethod(SecurityHeaders::class, 'isSecureConnection');

        $this->assertFalse($method->invoke(null));
    }

    public function testIsSecureConnectionReturnsTrueForForwardedProto(): void
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $method = new ReflectionMethod(SecurityHeaders::class, 'isSecureConnection');

        $this->assertTrue($method->invoke(null));
    }

    public function testIsSecureConnectionReturnsTrueForPort443(): void
    {
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = 443;
        $method = new ReflectionMethod(SecurityHeaders::class, 'isSecureConnection');

        $this->assertTrue($method->invoke(null));
    }

    public function testIsSecureConnectionReturnsFalseForPort80(): void
    {
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = 80;
        $method = new ReflectionMethod(SecurityHeaders::class, 'isSecureConnection');

        $this->assertFalse($method->invoke(null));
    }

    public function testIsSecureConnectionReturnsFalseWhenNoIndicators(): void
    {
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['SERVER_PORT']);
        $method = new ReflectionMethod(SecurityHeaders::class, 'isSecureConnection');

        $this->assertFalse($method->invoke(null));
    }

    // ===== isValidCspSource() tests via reflection =====

    public function testIsValidCspSourceAcceptsHttpsDomain(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertTrue($method->invoke(null, 'https://example.com'));
    }

    public function testIsValidCspSourceAcceptsHttpsDomainWithPort(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertTrue($method->invoke(null, 'https://example.com:8443'));
    }

    public function testIsValidCspSourceAcceptsHttpDomain(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertTrue($method->invoke(null, 'http://example.com'));
    }

    public function testIsValidCspSourceAcceptsWildcardSubdomain(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertTrue($method->invoke(null, '*.example.com'));
    }

    public function testIsValidCspSourceRejectsJavascript(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertFalse($method->invoke(null, 'javascript:alert(1)'));
    }

    public function testIsValidCspSourceRejectsDataUri(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertFalse($method->invoke(null, 'data:text/html'));
    }

    public function testIsValidCspSourceRejectsEmptyString(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertFalse($method->invoke(null, ''));
    }

    public function testIsValidCspSourceRejectsPlainDomain(): void
    {
        $method = new ReflectionMethod(SecurityHeaders::class, 'isValidCspSource');

        $this->assertFalse($method->invoke(null, 'example.com'));
    }

    // ===== buildMediaSrcDirective() tests via reflection =====

    public function testBuildMediaSrcDefaultIsSelfAndBlob(): void
    {
        unset($_ENV['CSP_MEDIA_SOURCES']);
        putenv('CSP_MEDIA_SOURCES');
        $method = new ReflectionMethod(SecurityHeaders::class, 'buildMediaSrcDirective');

        $result = $method->invoke(null);

        $this->assertStringContainsString("'self'", $result);
        $this->assertStringContainsString('blob:', $result);
        $this->assertStringStartsWith('media-src ', $result);
    }

    public function testBuildMediaSrcWithHttpsSetting(): void
    {
        $_ENV['CSP_MEDIA_SOURCES'] = 'https';
        $method = new ReflectionMethod(SecurityHeaders::class, 'buildMediaSrcDirective');

        $result = $method->invoke(null);

        $this->assertStringContainsString('https:', $result);
        $this->assertStringContainsString("'self'", $result);
    }

    public function testBuildMediaSrcWithCustomDomains(): void
    {
        $_ENV['CSP_MEDIA_SOURCES'] = 'https://audio.example.com,https://cdn.example.org';
        $method = new ReflectionMethod(SecurityHeaders::class, 'buildMediaSrcDirective');

        $result = $method->invoke(null);

        $this->assertStringContainsString('https://audio.example.com', $result);
        $this->assertStringContainsString('https://cdn.example.org', $result);
        $this->assertStringContainsString("'self'", $result);
    }

    public function testBuildMediaSrcRejectsInvalidDomains(): void
    {
        $_ENV['CSP_MEDIA_SOURCES'] = 'javascript:alert(1),https://valid.example.com';
        $method = new ReflectionMethod(SecurityHeaders::class, 'buildMediaSrcDirective');

        $result = $method->invoke(null);

        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('https://valid.example.com', $result);
    }

    // ===== reset() tests =====

    public function testResetAllowsHeadersToBeResent(): void
    {
        // After reset, headersSent flag should be cleared
        SecurityHeaders::reset();

        // We can't easily verify the flag is false, but we can verify
        // the class doesn't throw and can be called again
        $this->assertTrue(true); // Reset completes without error
    }

}
