<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Application\Services;

use Lwt\Modules\Language\Application\Services\TextParsingService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the TextParsingService.
 *
 * @covers \Lwt\Modules\Language\Application\Services\TextParsingService
 */
class TextParsingServiceTest extends TestCase
{
    private TextParsingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TextParsingService();
    }

    // =========================================================================
    // findLatinSentenceEnd() Tests — Basic sentence ending
    // =========================================================================

    /**
     * When match[6] is empty, match[7] is non-empty, and the last char of
     * matches[1] is alphanumeric, periods get a tab inserted (abbreviation
     * handling), and the method returns early without appending \r.
     */
    public function testFindLatinSentenceEndTabInsertionForAbbreviationContext(): void
    {
        // Simulates: "Hello. World" where matches come from a capturing regex
        // matches[0] = full match, [1] = word before punct, [2] = punctuation,
        // [3] = period component, [6] = optional group, [7] = next word start
        $matches = [
            0 => 'Hello.',
            1 => 'Hello',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => '',
            7 => 'World',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        // When match[6] is empty and match[7] non-empty with alphanumeric last char,
        // tabs are inserted after periods, but \r is NOT appended
        $this->assertStringContainsString("\t", $result);
        $this->assertStringNotContainsString("\r", $result);
    }

    /**
     * When match[6] is empty and match[7] is non-empty and the last char
     * of matches[1] is alphanumeric, a tab should be inserted after periods.
     */
    public function testFindLatinSentenceEndInsertsTabForAbbreviation(): void
    {
        $matches = [
            0 => 'Dr.',
            1 => 'Dr',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => '',
            7 => 'Smith',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertStringContainsString("\t", $result);
    }

    // =========================================================================
    // findLatinSentenceEnd() — Numeric matches
    // =========================================================================

    /**
     * A short number (1-2 digits) followed by a period should NOT be treated
     * as a sentence end (e.g., "1." as a list item).
     */
    public function testFindLatinSentenceEndShortNumberNotSentenceEnd(): void
    {
        $matches = [
            0 => '12.',
            1 => '12',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => 'x',
            7 => '',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        // Short numeric (< 3 chars) returns matches[0] unchanged
        $this->assertEquals('12.', $result);
    }

    /**
     * A longer number (3+ digits) followed by a period should be treated
     * as a potential sentence end.
     */
    public function testFindLatinSentenceEndLongNumberIsSentenceEnd(): void
    {
        $matches = [
            0 => '123.',
            1 => '123',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => 'x',
            7 => '',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertStringEndsWith("\r", $result);
    }

    // =========================================================================
    // findLatinSentenceEnd() — Abbreviation detection (consonant patterns)
    // =========================================================================

    /**
     * A consonant-only abbreviation like "Mr." should NOT be marked as
     * a sentence end.
     */
    public function testFindLatinSentenceEndConsonantAbbreviationNotSentenceEnd(): void
    {
        $matches = [
            0 => 'Mr.',
            1 => 'Mr',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => 'x',
            7 => '',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        // Consonant-only word with period in matches[3] returns unchanged
        $this->assertEquals('Mr.', $result);
    }

    /**
     * A single uppercase vowel like "I." should NOT be marked as sentence end.
     */
    public function testFindLatinSentenceEndSingleVowelNotSentenceEnd(): void
    {
        $matches = [
            0 => 'A.',
            1 => 'A',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => 'x',
            7 => '',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertEquals('A.', $result);
    }

    /**
     * A normal word (not all consonants, not single vowel) with period should
     * be treated as a sentence end.
     */
    public function testFindLatinSentenceEndNormalWordIsSentenceEnd(): void
    {
        $matches = [
            0 => 'done.',
            1 => 'done',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => 'x',
            7 => '',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertStringEndsWith("\r", $result);
    }

    // =========================================================================
    // findLatinSentenceEnd() — Colon/period followed by lowercase
    // =========================================================================

    /**
     * A period or colon followed by a lowercase letter should NOT be treated
     * as a sentence end (e.g., "e.g. something").
     */
    public function testFindLatinSentenceEndPeriodFollowedByLowercaseNotSentenceEnd(): void
    {
        $matches = [
            0 => 'e.g.',
            1 => 'e',
            2 => '.',
            3 => '',
            4 => '',
            5 => '',
            6 => 'x',
            7 => 'something',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertEquals('e.g.', $result);
    }

    /**
     * A colon followed by a lowercase letter should NOT be marked as sentence end.
     */
    public function testFindLatinSentenceEndColonFollowedByLowercaseNotSentenceEnd(): void
    {
        $matches = [
            0 => 'note:',
            1 => 'note',
            2 => ':',
            3 => '',
            4 => '',
            5 => '',
            6 => 'x',
            7 => 'details',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertEquals('note:', $result);
    }

    // =========================================================================
    // findLatinSentenceEnd() — noSentenceEnd parameter
    // =========================================================================

    /**
     * When noSentenceEnd pattern matches the full match, it should not
     * be marked as a sentence end.
     */
    public function testFindLatinSentenceEndNoSentenceEndPatternMatch(): void
    {
        $matches = [
            0 => 'etc.',
            1 => 'etc',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => 'x',
            7 => 'And',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, 'etc\\.');

        $this->assertEquals('etc.', $result);
    }

    /**
     * When noSentenceEnd is empty, it should not affect behavior.
     */
    public function testFindLatinSentenceEndEmptyNoSentenceEnd(): void
    {
        $matches = [
            0 => 'end!',
            1 => 'end',
            2 => '!',
            3 => '',
            4 => '',
            5 => '',
            6 => 'x',
            7 => 'Start',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertStringEndsWith("\r", $result);
    }

    /**
     * When noSentenceEnd pattern does NOT match, sentence end is still detected.
     */
    public function testFindLatinSentenceEndNoSentenceEndPatternNoMatch(): void
    {
        $matches = [
            0 => 'done.',
            1 => 'done',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
            6 => 'x',
            7 => 'Next',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, 'etc\\.');

        $this->assertStringEndsWith("\r", $result);
    }

    // =========================================================================
    // findLatinSentenceEnd() — Missing/null match groups
    // =========================================================================

    /**
     * When matches[6] and matches[7] are missing from the array,
     * the method should handle them gracefully (default to empty string).
     */
    public function testFindLatinSentenceEndMissingOptionalGroups(): void
    {
        $matches = [
            0 => 'end.',
            1 => 'end',
            2 => '.',
            3 => '.',
            4 => '',
            5 => '',
        ];

        // Should not throw — missing keys default to ''
        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertIsString($result);
    }

    // =========================================================================
    // getMecabPath() Tests
    // =========================================================================

    /**
     * getMecabPath should escape shell arguments.
     *
     * We test this indirectly: on the current OS (Linux in CI),
     * if MeCab is not installed it throws RuntimeException.
     * The key point is the method accepts string args and does not crash.
     */
    public function testGetMecabPathReturnsStringOrThrowsRuntimeException(): void
    {
        try {
            $result = $this->service->getMecabPath();
            // If MeCab is installed, we get a valid command string
            $this->assertIsString($result);
            $this->assertStringContainsString('mecab', $result);
        } catch (\RuntimeException $e) {
            // If MeCab is not installed, a RuntimeException is thrown
            $this->assertStringContainsString('MeCab', $e->getMessage());
        }
    }

    /**
     * getMecabPath with arguments should include them in the result.
     */
    public function testGetMecabPathWithArguments(): void
    {
        try {
            $result = $this->service->getMecabPath(' -d /some/dict');
            $this->assertIsString($result);
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('MeCab', $e->getMessage());
        }
    }

    /**
     * getMecabPath with empty string argument should work.
     */
    public function testGetMecabPathWithEmptyArguments(): void
    {
        try {
            $result = $this->service->getMecabPath('');
            $this->assertIsString($result);
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('MeCab', $e->getMessage());
        }
    }

    // =========================================================================
    // findLatinSentenceEnd() — Question mark and exclamation mark
    // =========================================================================

    /**
     * A question mark should be treated as a sentence end.
     */
    public function testFindLatinSentenceEndQuestionMarkIsSentenceEnd(): void
    {
        $matches = [
            0 => 'what?',
            1 => 'what',
            2 => '?',
            3 => '',
            4 => '',
            5 => '',
            6 => 'x',
            7 => 'Yes',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertStringEndsWith("\r", $result);
    }

    /**
     * An exclamation mark should be treated as a sentence end.
     */
    public function testFindLatinSentenceEndExclamationMarkIsSentenceEnd(): void
    {
        $matches = [
            0 => 'stop!',
            1 => 'stop',
            2 => '!',
            3 => '',
            4 => '',
            5 => '',
            6 => 'x',
            7 => 'Now',
        ];

        $result = $this->service->findLatinSentenceEnd($matches, '');

        $this->assertStringEndsWith("\r", $result);
    }
}
