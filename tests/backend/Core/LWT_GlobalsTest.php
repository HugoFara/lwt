<?php

declare(strict_types=1);

namespace Lwt\Tests\Core;

require_once __DIR__ . '/../../../../src/backend/Core/LWT_Globals.php';
require_once __DIR__ . '/../../../../src/backend/Core/database_connect.php';

use Lwt\Core\LWT_Globals;
use Lwt\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the LWT_Globals class.
 *
 * Tests global state management for database connection, table prefix,
 * and various application-wide settings.
 */
class LWT_GlobalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset LWT_Globals state before each test
        LWT_Globals::reset();
    }

    protected function tearDown(): void
    {
        // Reset LWT_Globals state after each test
        LWT_Globals::reset();
        
        parent::tearDown();
    }

    // ===== initialize() tests =====

    public function testInitialize(): void
    {
        LWT_Globals::initialize();
        
        // After initialization, debug/display settings should be 0
        $this->assertEquals(0, LWT_Globals::getDebug());
        $this->assertFalse(LWT_Globals::isDebug());
        $this->assertFalse(LWT_Globals::shouldDisplayErrors());
        $this->assertFalse(LWT_Globals::shouldDisplayTime());
    }

    public function testInitializeOnlyOnce(): void
    {
        LWT_Globals::initialize();
        LWT_Globals::setDebug(1);
        
        // Second initialize should not reset values
        LWT_Globals::initialize();
        
        $this->assertEquals(1, LWT_Globals::getDebug());
    }

    // ===== dbConnection tests =====

    public function testSetAndGetDbConnection(): void
    {
        $mockConnection = $this->createMock(\mysqli::class);
        
        LWT_Globals::setDbConnection($mockConnection);
        
        $this->assertSame($mockConnection, LWT_Globals::getDbConnection());
    }

    public function testGetDbConnectionReturnsNullInitially(): void
    {
        $this->assertNull(LWT_Globals::getDbConnection());
    }

    // ===== tablePrefix tests =====

    public function testSetAndGetTablePrefix(): void
    {
        LWT_Globals::setTablePrefix('test_');
        
        $this->assertEquals('test_', LWT_Globals::getTablePrefix());
    }

    public function testTablePrefixDefaultsToEmpty(): void
    {
        $this->assertEquals('', LWT_Globals::getTablePrefix());
    }

    public function testSetTablePrefixWithFixed(): void
    {
        LWT_Globals::setTablePrefix('lwt_', true);
        
        $this->assertEquals('lwt_', LWT_Globals::getTablePrefix());
        $this->assertTrue(LWT_Globals::isTablePrefixFixed());
    }

    public function testIsTablePrefixFixedDefaultsFalse(): void
    {
        $this->assertFalse(LWT_Globals::isTablePrefixFixed());
    }

    // ===== databaseName tests =====

    public function testSetAndGetDatabaseName(): void
    {
        LWT_Globals::setDatabaseName('test_database');
        
        $this->assertEquals('test_database', LWT_Globals::getDatabaseName());
    }

    public function testDatabaseNameDefaultsToEmpty(): void
    {
        $this->assertEquals('', LWT_Globals::getDatabaseName());
    }

    // ===== debug tests =====

    public function testSetDebugOn(): void
    {
        LWT_Globals::setDebug(1);
        
        $this->assertEquals(1, LWT_Globals::getDebug());
        $this->assertTrue(LWT_Globals::isDebug());
    }

    public function testSetDebugOff(): void
    {
        LWT_Globals::setDebug(1);
        LWT_Globals::setDebug(0);
        
        $this->assertEquals(0, LWT_Globals::getDebug());
        $this->assertFalse(LWT_Globals::isDebug());
    }

    public function testIsDebugReturnsBool(): void
    {
        LWT_Globals::setDebug(0);
        $this->assertIsBool(LWT_Globals::isDebug());
        
        LWT_Globals::setDebug(1);
        $this->assertIsBool(LWT_Globals::isDebug());
    }

    // ===== displayErrors tests =====

    public function testSetDisplayErrorsOn(): void
    {
        LWT_Globals::setDisplayErrors(1);
        
        $this->assertTrue(LWT_Globals::shouldDisplayErrors());
    }

    public function testSetDisplayErrorsOff(): void
    {
        LWT_Globals::setDisplayErrors(1);
        LWT_Globals::setDisplayErrors(0);
        
        $this->assertFalse(LWT_Globals::shouldDisplayErrors());
    }

    public function testShouldDisplayErrorsReturnsBool(): void
    {
        LWT_Globals::setDisplayErrors(0);
        $this->assertIsBool(LWT_Globals::shouldDisplayErrors());
        
        LWT_Globals::setDisplayErrors(1);
        $this->assertIsBool(LWT_Globals::shouldDisplayErrors());
    }

    // ===== displayTime tests =====

    public function testSetDisplayTimeOn(): void
    {
        LWT_Globals::setDisplayTime(1);
        
        $this->assertTrue(LWT_Globals::shouldDisplayTime());
    }

    public function testSetDisplayTimeOff(): void
    {
        LWT_Globals::setDisplayTime(1);
        LWT_Globals::setDisplayTime(0);
        
        $this->assertFalse(LWT_Globals::shouldDisplayTime());
    }

    public function testShouldDisplayTimeReturnsBool(): void
    {
        LWT_Globals::setDisplayTime(0);
        $this->assertIsBool(LWT_Globals::shouldDisplayTime());
        
        LWT_Globals::setDisplayTime(1);
        $this->assertIsBool(LWT_Globals::shouldDisplayTime());
    }

    // ===== table() tests =====

    public function testTableReturnsTableNameWithPrefix(): void
    {
        LWT_Globals::setTablePrefix('lwt_');
        
        $this->assertEquals('lwt_words', LWT_Globals::table('words'));
    }

    public function testTableReturnsTableNameWithoutPrefixWhenEmpty(): void
    {
        LWT_Globals::setTablePrefix('');
        
        $this->assertEquals('words', LWT_Globals::table('words'));
    }

    // ===== query() tests =====

    public function testQueryReturnsQueryBuilder(): void
    {
        $qb = LWT_Globals::query('words');
        
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testQueryUsesCorrectTablePrefix(): void
    {
        LWT_Globals::setTablePrefix('test_');
        
        $qb = LWT_Globals::query('words');
        $sql = $qb->toSql();
        
        $this->assertStringContainsString('test_words', $sql);
    }

    // ===== reset() tests =====

    public function testResetClearsAllValues(): void
    {
        // Set various values
        $mockConnection = $this->createMock(\mysqli::class);
        LWT_Globals::setDbConnection($mockConnection);
        LWT_Globals::setTablePrefix('test_', true);
        LWT_Globals::setDatabaseName('testdb');
        LWT_Globals::setDebug(1);
        LWT_Globals::setDisplayErrors(1);
        LWT_Globals::setDisplayTime(1);
        LWT_Globals::initialize();
        
        // Reset
        LWT_Globals::reset();
        
        // Verify all values are cleared
        $this->assertNull(LWT_Globals::getDbConnection());
        $this->assertEquals('', LWT_Globals::getTablePrefix());
        $this->assertFalse(LWT_Globals::isTablePrefixFixed());
        $this->assertEquals('', LWT_Globals::getDatabaseName());
        $this->assertEquals(0, LWT_Globals::getDebug());
        $this->assertFalse(LWT_Globals::shouldDisplayErrors());
        $this->assertFalse(LWT_Globals::shouldDisplayTime());
    }
}
