<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Word;

require_once __DIR__ . '/../../../../src/backend/Core/settings.php';
require_once __DIR__ . '/../../../../src/backend/Services/WordStatusService.php';

use Lwt\Services\WordStatusService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for WordStatusService scoring functions
 */
final class WordScoringTest extends TestCase
{
    /**
     * Test makeScoreRandomInsertUpdate with different types
     */
    public function testMakeScoreRandomInsertUpdate(): void
    {
        // Test 'iv' type - column names only
        $result = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $this->assertStringContainsString('WoTodayScore', $result);
        $this->assertStringContainsString('WoTomorrowScore', $result);
        $this->assertStringContainsString('WoRandom', $result);
        $this->assertStringNotContainsString('=', $result);

        // Test 'id' type - values only (for INSERT)
        $result = WordStatusService::makeScoreRandomInsertUpdate('id');
        $this->assertStringContainsString('RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);
        $this->assertStringContainsString('WoStatus', $result);
        // Note: The result contains '=' in CASE conditions like "WoStatus = 1"
        // but not in assignment context (no "column = value")

        // Test 'u' type - key=value pairs for UPDATE
        $result = WordStatusService::makeScoreRandomInsertUpdate('u');
        $this->assertStringContainsString('WoTodayScore =', $result);
        $this->assertStringContainsString('WoTomorrowScore =', $result);
        $this->assertStringContainsString('WoRandom = RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);

        // Test default case (should return empty string)
        $result = WordStatusService::makeScoreRandomInsertUpdate('anything_else');
        $this->assertEquals('', $result);
    }
}
