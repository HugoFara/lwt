<?php

declare(strict_types=1);

namespace Lwt\Tests\Core;

require_once __DIR__ . '/../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';

use Lwt\Core\Globals;
use Lwt\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Globals class.
 *
 * Tests global state management for database connection, table prefix,
 * and various application-wide settings.
 */
class GlobalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset Globals state before each test
        Globals::reset();
    }

    protected function tearDown(): void
    {
        // Reset Globals state after each test
        Globals::reset();
        
        parent::tearDown();
    }

    // ===== initialize() tests =====

    public function testInitialize(): void
    {
        Globals::initialize();
        
        // After initialization, debug/display settings should be 0
        $this->assertEquals(0, Globals::getDebug());
        $this->assertFalse(Globals::isDebug());
        $this->assertFalse(Globals::shouldDisplayErrors());
        $this->assertFalse(Globals::shouldDisplayTime());
    }

    public function testInitializeOnlyOnce(): void
    {
        Globals::initialize();
        Globals::setDebug(1);
        
        // Second initialize should not reset values
        Globals::initialize();
        
        $this->assertEquals(1, Globals::getDebug());
    }

    // ===== dbConnection tests =====

    public function testSetAndGetDbConnection(): void
    {
        $mockConnection = $this->createMock(\mysqli::class);
        
        Globals::setDbConnection($mockConnection);
        
        $this->assertSame($mockConnection, Globals::getDbConnection());
    }

    public function testGetDbConnectionReturnsNullInitially(): void
    {
        $this->assertNull(Globals::getDbConnection());
    }

    // ===== tablePrefix tests =====

    public function testSetAndGetTablePrefix(): void
    {
        Globals::setTablePrefix('test_');
        
        $this->assertEquals('test_', Globals::getTablePrefix());
    }

    public function testTablePrefixDefaultsToEmpty(): void
    {
        $this->assertEquals('', Globals::getTablePrefix());
    }

    public function testSetTablePrefixWithFixed(): void
    {
        Globals::setTablePrefix('lwt_', true);
        
        $this->assertEquals('lwt_', Globals::getTablePrefix());
        $this->assertTrue(Globals::isTablePrefixFixed());
    }

    public function testIsTablePrefixFixedDefaultsFalse(): void
    {
        $this->assertFalse(Globals::isTablePrefixFixed());
    }

    // ===== databaseName tests =====

    public function testSetAndGetDatabaseName(): void
    {
        Globals::setDatabaseName('test_database');
        
        $this->assertEquals('test_database', Globals::getDatabaseName());
    }

    public function testDatabaseNameDefaultsToEmpty(): void
    {
        $this->assertEquals('', Globals::getDatabaseName());
    }

    // ===== debug tests =====

    public function testSetDebugOn(): void
    {
        Globals::setDebug(1);
        
        $this->assertEquals(1, Globals::getDebug());
        $this->assertTrue(Globals::isDebug());
    }

    public function testSetDebugOff(): void
    {
        Globals::setDebug(1);
        Globals::setDebug(0);
        
        $this->assertEquals(0, Globals::getDebug());
        $this->assertFalse(Globals::isDebug());
    }

    public function testIsDebugReturnsBool(): void
    {
        Globals::setDebug(0);
        $this->assertIsBool(Globals::isDebug());
        
        Globals::setDebug(1);
        $this->assertIsBool(Globals::isDebug());
    }

    // ===== displayErrors tests =====

    public function testSetDisplayErrorsOn(): void
    {
        Globals::setDisplayErrors(1);
        
        $this->assertTrue(Globals::shouldDisplayErrors());
    }

    public function testSetDisplayErrorsOff(): void
    {
        Globals::setDisplayErrors(1);
        Globals::setDisplayErrors(0);
        
        $this->assertFalse(Globals::shouldDisplayErrors());
    }

    public function testShouldDisplayErrorsReturnsBool(): void
    {
        Globals::setDisplayErrors(0);
        $this->assertIsBool(Globals::shouldDisplayErrors());
        
        Globals::setDisplayErrors(1);
        $this->assertIsBool(Globals::shouldDisplayErrors());
    }

    // ===== displayTime tests =====

    public function testSetDisplayTimeOn(): void
    {
        Globals::setDisplayTime(1);
        
        $this->assertTrue(Globals::shouldDisplayTime());
    }

    public function testSetDisplayTimeOff(): void
    {
        Globals::setDisplayTime(1);
        Globals::setDisplayTime(0);
        
        $this->assertFalse(Globals::shouldDisplayTime());
    }

    public function testShouldDisplayTimeReturnsBool(): void
    {
        Globals::setDisplayTime(0);
        $this->assertIsBool(Globals::shouldDisplayTime());
        
        Globals::setDisplayTime(1);
        $this->assertIsBool(Globals::shouldDisplayTime());
    }

    // ===== table() tests =====

    public function testTableReturnsTableNameWithPrefix(): void
    {
        Globals::setTablePrefix('lwt_');
        
        $this->assertEquals('lwt_words', Globals::table('words'));
    }

    public function testTableReturnsTableNameWithoutPrefixWhenEmpty(): void
    {
        Globals::setTablePrefix('');
        
        $this->assertEquals('words', Globals::table('words'));
    }

    // ===== query() tests =====

    public function testQueryReturnsQueryBuilder(): void
    {
        $qb = Globals::query('words');
        
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testQueryUsesCorrectTablePrefix(): void
    {
        Globals::setTablePrefix('test_');
        
        $qb = Globals::query('words');
        $sql = $qb->toSql();
        
        $this->assertStringContainsString('test_words', $sql);
    }

    // ===== reset() tests =====

    public function testResetClearsAllValues(): void
    {
        // Set various values
        $mockConnection = $this->createMock(\mysqli::class);
        Globals::setDbConnection($mockConnection);
        Globals::setTablePrefix('test_', true);
        Globals::setDatabaseName('testdb');
        Globals::setDebug(1);
        Globals::setDisplayErrors(1);
        Globals::setDisplayTime(1);
        Globals::initialize();
        
        // Reset
        Globals::reset();
        
        // Verify all values are cleared
        $this->assertNull(Globals::getDbConnection());
        $this->assertEquals('', Globals::getTablePrefix());
        $this->assertFalse(Globals::isTablePrefixFixed());
        $this->assertEquals('', Globals::getDatabaseName());
        $this->assertEquals(0, Globals::getDebug());
        $this->assertFalse(Globals::shouldDisplayErrors());
        $this->assertFalse(Globals::shouldDisplayTime());
    }
}
