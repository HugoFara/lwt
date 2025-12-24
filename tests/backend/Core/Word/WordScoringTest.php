<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Word;

require_once __DIR__ . '/../../../../src/backend/Core/Globals.php';

use Lwt\Core\Globals;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use PHPUnit\Framework\TestCase;

Globals::initialize();

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
        $result = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $this->assertStringContainsString('WoTodayScore', $result);
        $this->assertStringContainsString('WoTomorrowScore', $result);
        $this->assertStringContainsString('WoRandom', $result);
        $this->assertStringNotContainsString('=', $result);

        // Test 'id' type - values only (for INSERT)
        $result = TermStatusService::makeScoreRandomInsertUpdate('id');
        $this->assertStringContainsString('RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);
        $this->assertStringContainsString('WoStatus', $result);
        // Note: The result contains '=' in CASE conditions like "WoStatus = 1"
        // but not in assignment context (no "column = value")

        // Test 'u' type - key=value pairs for UPDATE
        $result = TermStatusService::makeScoreRandomInsertUpdate('u');
        $this->assertStringContainsString('WoTodayScore =', $result);
        $this->assertStringContainsString('WoTomorrowScore =', $result);
        $this->assertStringContainsString('WoRandom = RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);

        // Test default case (should return empty string)
        $result = TermStatusService::makeScoreRandomInsertUpdate('anything_else');
        $this->assertEquals('', $result);
    }
}
