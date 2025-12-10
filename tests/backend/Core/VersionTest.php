<?php declare(strict_types=1);

namespace Lwt\Tests\Core;

require_once __DIR__ . '/../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../src/backend/Core/version.php';

use Lwt\Core\Globals;
use PHPUnit\Framework\TestCase;

use function Lwt\Core\getVersion;
use function Lwt\Core\getVersionNumber;

Globals::initialize();

/**
 * Tests for version.php functions
 */
final class VersionTest extends TestCase
{
    /**
     * Test the display of version as a string
     */
    public function testGetVersion(): void
    {
        $version = getVersion();
        $this->assertIsString($version);
    }

    /**
     * Test the correct format of version as v{3-digit MAJOR}{3-digit MINOR}{3-digit PATCH}
     */
    public function testGetVersionNumber(): void
    {
        $version = getVersionNumber();
        $this->assertTrue(str_starts_with($version, 'v'));
        $this->assertSame(10, strlen($version));
    }
}
