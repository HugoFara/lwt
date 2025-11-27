<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Word;

require_once __DIR__ . '/../../../../src/backend/Core/settings.php';
require_once __DIR__ . '/../../../../src/backend/Core/Word/word_scoring.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for word_scoring.php functions
 */
final class WordScoringTest extends TestCase
{
    /**
     * Test make_score_random_insert_update with different types
     */
    public function testMakeScoreRandomInsertUpdate(): void
    {
        // Test 'iv' type - column names only
        $result = make_score_random_insert_update('iv');
        $this->assertStringContainsString('WoTodayScore', $result);
        $this->assertStringContainsString('WoTomorrowScore', $result);
        $this->assertStringContainsString('WoRandom', $result);
        $this->assertStringNotContainsString('=', $result);

        // Test 'id' type - values only (for INSERT)
        $result = make_score_random_insert_update('id');
        $this->assertStringContainsString('RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);
        $this->assertStringContainsString('WoStatus', $result);
        // Note: The result contains '=' in CASE conditions like "WoStatus = 1"
        // but not in assignment context (no "column = value")

        // Test 'u' type - key=value pairs for UPDATE
        $result = make_score_random_insert_update('u');
        $this->assertStringContainsString('WoTodayScore =', $result);
        $this->assertStringContainsString('WoTomorrowScore =', $result);
        $this->assertStringContainsString('WoRandom = RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);

        // Test default case (should return empty string)
        $result = make_score_random_insert_update('anything_else');
        $this->assertEquals('', $result);
    }
}
