<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

use PHPUnit\Framework\TestCase;

// Manually require the class since it uses manual loading, not PSR-4
require_once __DIR__ . '/../../../../src/backend/Core/Database/PrefixMigration.php';

use Lwt\Database\PrefixMigration;

/**
 * Tests for PrefixMigration class.
 *
 * @coversDefaultClass \Lwt\Database\PrefixMigration
 */
class PrefixMigrationTest extends TestCase
{
    /**
     * Test that the class can be instantiated.
     *
     * @covers ::__construct
     */
    public function testCanInstantiate(): void
    {
        $migration = new PrefixMigration();
        $this->assertInstanceOf(PrefixMigration::class, $migration);
    }

    /**
     * Test that verbose mode can be enabled.
     *
     * @covers ::__construct
     */
    public function testVerboseModeCanBeEnabled(): void
    {
        $migration = new PrefixMigration(true);
        $this->assertInstanceOf(PrefixMigration::class, $migration);
    }

    /**
     * Test that log starts empty.
     *
     * @covers ::getLog
     */
    public function testLogStartsEmpty(): void
    {
        $migration = new PrefixMigration();
        $this->assertEquals([], $migration->getLog());
    }
}
