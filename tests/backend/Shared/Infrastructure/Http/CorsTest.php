<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\Cors;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the CORS allow-list decision logic.
 *
 * Covers the security-critical surface — which origins are accepted and
 * echoed back — without asserting on actual header emission (a thin wrapper
 * that requires a separate process).
 */
#[CoversClass(Cors::class)]
class CorsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $envBackup = [];

    private string $originBackup = '';

    private bool $hadOrigin = false;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->hadOrigin = isset($_SERVER['HTTP_ORIGIN']);
        $this->originBackup = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        unset($_ENV['CORS_ALLOWED_ORIGINS'], $_SERVER['HTTP_ORIGIN']);
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        if ($this->hadOrigin) {
            $_SERVER['HTTP_ORIGIN'] = $this->originBackup;
        } else {
            unset($_SERVER['HTTP_ORIGIN']);
        }
    }

    public function testAllowedOriginsEmptyByDefault(): void
    {
        $this->assertSame([], Cors::allowedOrigins());
    }

    public function testAllowedOriginsParsesCommaList(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS']
            = 'https://app.example.org, capacitor://localhost ,http://localhost';

        $this->assertSame(
            ['https://app.example.org', 'capacitor://localhost', 'http://localhost'],
            Cors::allowedOrigins()
        );
    }

    public function testAllowedOriginsStripsTrailingSlashesAndBlanks(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'https://app.example.org/,, ,https://b.example/';

        $this->assertSame(
            ['https://app.example.org', 'https://b.example'],
            Cors::allowedOrigins()
        );
    }

    public function testResolveOriginNullWhenNoOriginHeader(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'https://app.example.org';

        $this->assertNull(Cors::resolveOrigin());
    }

    public function testResolveOriginNullWhenNotAllowListed(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'https://app.example.org';
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.example';

        $this->assertNull(Cors::resolveOrigin());
    }

    public function testResolveOriginNullWhenAllowListEmpty(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.org';

        $this->assertNull(Cors::resolveOrigin());
    }

    public function testResolveOriginEchoesAllowListedOrigin(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'https://app.example.org,capacitor://localhost';
        $_SERVER['HTTP_ORIGIN'] = 'capacitor://localhost';

        $this->assertSame('capacitor://localhost', Cors::resolveOrigin());
    }

    public function testResolveOriginMatchesDespiteTrailingSlash(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'https://app.example.org';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.org/';

        $this->assertSame('https://app.example.org', Cors::resolveOrigin());
    }

    public function testHandlePreflightIgnoresNonOptions(): void
    {
        $this->assertFalse(Cors::handlePreflight('GET'));
        $this->assertFalse(Cors::handlePreflight('POST'));
    }

    public function testHandlePreflightAnswersOptions(): void
    {
        // No allow-listed origin set, so no headers are emitted; the method
        // still reports it handled the OPTIONS request.
        $this->assertTrue(Cors::handlePreflight('OPTIONS'));
        $this->assertTrue(Cors::handlePreflight('options'));
    }
}
