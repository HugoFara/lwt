<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
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
    private ?string $corsBackup = null;

    private bool $hadCors = false;

    private string $originBackup = '';

    private bool $hadOrigin = false;

    protected function setUp(): void
    {
        // Snapshot any ambient CORS_ALLOWED_ORIGINS (a developer's .env may set
        // it for local Model B testing) and clear it through EnvLoader so each
        // test sees a deterministic baseline regardless of .env/$_ENV/getenv —
        // EnvLoader::get consults the loaded store first, which a bare
        // unset($_ENV[...]) would not clear.
        $this->hadCors = EnvLoader::has('CORS_ALLOWED_ORIGINS');
        $this->corsBackup = EnvLoader::get('CORS_ALLOWED_ORIGINS');
        EnvLoader::set('CORS_ALLOWED_ORIGINS', null);

        $this->hadOrigin = isset($_SERVER['HTTP_ORIGIN']);
        $this->originBackup = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        unset($_SERVER['HTTP_ORIGIN']);
    }

    protected function tearDown(): void
    {
        EnvLoader::set('CORS_ALLOWED_ORIGINS', $this->hadCors ? $this->corsBackup : null);
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
        EnvLoader::set(
            'CORS_ALLOWED_ORIGINS',
            'https://app.example.org, capacitor://localhost ,http://localhost'
        );

        $this->assertSame(
            ['https://app.example.org', 'capacitor://localhost', 'http://localhost'],
            Cors::allowedOrigins()
        );
    }

    public function testAllowedOriginsStripsTrailingSlashesAndBlanks(): void
    {
        EnvLoader::set('CORS_ALLOWED_ORIGINS', 'https://app.example.org/,, ,https://b.example/');

        $this->assertSame(
            ['https://app.example.org', 'https://b.example'],
            Cors::allowedOrigins()
        );
    }

    public function testResolveOriginNullWhenNoOriginHeader(): void
    {
        EnvLoader::set('CORS_ALLOWED_ORIGINS', 'https://app.example.org');

        $this->assertNull(Cors::resolveOrigin());
    }

    public function testResolveOriginNullWhenNotAllowListed(): void
    {
        EnvLoader::set('CORS_ALLOWED_ORIGINS', 'https://app.example.org');
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
        EnvLoader::set('CORS_ALLOWED_ORIGINS', 'https://app.example.org,capacitor://localhost');
        $_SERVER['HTTP_ORIGIN'] = 'capacitor://localhost';

        $this->assertSame('capacitor://localhost', Cors::resolveOrigin());
    }

    public function testResolveOriginMatchesDespiteTrailingSlash(): void
    {
        EnvLoader::set('CORS_ALLOWED_ORIGINS', 'https://app.example.org');
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
