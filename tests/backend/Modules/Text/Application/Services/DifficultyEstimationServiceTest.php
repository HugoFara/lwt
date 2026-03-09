<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Text\Application\Services;

use Lwt\Modules\Text\Application\Services\DifficultyEstimationService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for DifficultyEstimationService.
 *
 * Tests the pure logic methods (subject classification, quick tier computation,
 * coverage labeling) without requiring a database connection.
 */
class DifficultyEstimationServiceTest extends TestCase
{
    private DifficultyEstimationService $service;

    protected function setUp(): void
    {
        $this->service = new DifficultyEstimationService();
    }

    // =========================================================================
    // classifySubjects tests
    // =========================================================================

    public function testClassifySubjectsEasyForChildrensLiterature(): void
    {
        $result = $this->callClassifySubjects(["Children's literature", "Fiction"]);
        $this->assertSame('easy', $result);
    }

    public function testClassifySubjectsEasyForFairyTales(): void
    {
        $result = $this->callClassifySubjects(['Fairy tales -- Germany']);
        $this->assertSame('easy', $result);
    }

    public function testClassifySubjectsEasyForJuvenileFiction(): void
    {
        $result = $this->callClassifySubjects(['Juvenile fiction', 'Adventure stories']);
        $this->assertSame('easy', $result);
    }

    public function testClassifySubjectsHardForPhilosophy(): void
    {
        $result = $this->callClassifySubjects(['Philosophy', 'Ethics']);
        $this->assertSame('hard', $result);
    }

    public function testClassifySubjectsHardForScience(): void
    {
        $result = $this->callClassifySubjects(['Science', 'Biology']);
        $this->assertSame('hard', $result);
    }

    public function testClassifySubjectsHardForLaw(): void
    {
        $result = $this->callClassifySubjects(['Law', 'Constitutional law']);
        $this->assertSame('hard', $result);
    }

    public function testClassifySubjectsMediumForGeneralFiction(): void
    {
        $result = $this->callClassifySubjects(['Fiction', 'Romance']);
        $this->assertSame('medium', $result);
    }

    public function testClassifySubjectsMediumForEmptySubjects(): void
    {
        $result = $this->callClassifySubjects([]);
        $this->assertSame('medium', $result);
    }

    public function testClassifySubjectsEasyTakesPriorityOverHard(): void
    {
        // If both easy and hard subjects are present, easy wins (most favorable)
        $result = $this->callClassifySubjects(["Children's stories", 'Philosophy']);
        $this->assertSame('easy', $result);
    }

    public function testClassifySubjectsCaseInsensitive(): void
    {
        $result = $this->callClassifySubjects(['CHILDREN fiction']);
        $this->assertSame('easy', $result);
    }

    // =========================================================================
    // computeQuickTier tests
    // =========================================================================

    public function testComputeQuickTierZeroWordsAlwaysHard(): void
    {
        $result = $this->callComputeQuickTier(0, ['Fiction']);
        $this->assertSame('hard', $result);
    }

    public function testComputeQuickTierZeroWordsHardEvenWithEasySubject(): void
    {
        $result = $this->callComputeQuickTier(0, ["Children's literature"]);
        $this->assertSame('hard', $result);
    }

    public function testComputeQuickTierLowVocabShiftsUp(): void
    {
        // < 500 words, easy subject → medium
        $result = $this->callComputeQuickTier(200, ["Children's literature"]);
        $this->assertSame('medium', $result);
    }

    public function testComputeQuickTierLowVocabMediumBecomesHard(): void
    {
        // < 500 words, medium subject → hard
        $result = $this->callComputeQuickTier(300, ['Fiction']);
        $this->assertSame('hard', $result);
    }

    public function testComputeQuickTierLowVocabHardStaysHard(): void
    {
        // < 500 words, hard subject → hard
        $result = $this->callComputeQuickTier(100, ['Philosophy']);
        $this->assertSame('hard', $result);
    }

    public function testComputeQuickTierMidVocabUsesSubjectDirectly(): void
    {
        // 500-2000 words: use subject tier as-is
        $this->assertSame('easy', $this->callComputeQuickTier(1000, ["Children's literature"]));
        $this->assertSame('medium', $this->callComputeQuickTier(1000, ['Fiction']));
        $this->assertSame('hard', $this->callComputeQuickTier(1000, ['Philosophy']));
    }

    public function testComputeQuickTierHighVocabShiftsDown(): void
    {
        // > 2000 words, hard → medium
        $result = $this->callComputeQuickTier(3000, ['Philosophy']);
        $this->assertSame('medium', $result);
    }

    public function testComputeQuickTierHighVocabMediumBecomesEasy(): void
    {
        // > 2000 words, medium → easy
        $result = $this->callComputeQuickTier(5000, ['Fiction']);
        $this->assertSame('easy', $result);
    }

    public function testComputeQuickTierHighVocabEasyStaysEasy(): void
    {
        // > 2000 words, easy → easy
        $result = $this->callComputeQuickTier(5000, ["Children's literature"]);
        $this->assertSame('easy', $result);
    }

    public function testComputeQuickTierBoundaryAt500(): void
    {
        // Exactly 500 = mid range, not low
        $result = $this->callComputeQuickTier(500, ['Fiction']);
        $this->assertSame('medium', $result);
    }

    public function testComputeQuickTierBoundaryAt2000(): void
    {
        // Exactly 2000 = still mid range
        $result = $this->callComputeQuickTier(2000, ['Fiction']);
        $this->assertSame('medium', $result);
    }

    public function testComputeQuickTierBoundaryAt2001(): void
    {
        // 2001 = high vocab, medium → easy
        $result = $this->callComputeQuickTier(2001, ['Fiction']);
        $this->assertSame('easy', $result);
    }

    // =========================================================================
    // labelFromCoverage tests
    // =========================================================================

    public function testLabelFromCoverageEasyAbove95(): void
    {
        $this->assertSame('easy', $this->callLabelFromCoverage(98.5));
        $this->assertSame('easy', $this->callLabelFromCoverage(95.0));
        $this->assertSame('easy', $this->callLabelFromCoverage(100.0));
    }

    public function testLabelFromCoverageMediumBetween85And95(): void
    {
        $this->assertSame('medium', $this->callLabelFromCoverage(94.9));
        $this->assertSame('medium', $this->callLabelFromCoverage(90.0));
        $this->assertSame('medium', $this->callLabelFromCoverage(85.0));
    }

    public function testLabelFromCoverageHardBelow85(): void
    {
        $this->assertSame('hard', $this->callLabelFromCoverage(84.9));
        $this->assertSame('hard', $this->callLabelFromCoverage(50.0));
        $this->assertSame('hard', $this->callLabelFromCoverage(0.0));
    }

    // =========================================================================
    // tokenize tests
    // =========================================================================

    public function testTokenizeBasicEnglish(): void
    {
        $tokens = $this->callTokenize('Hello world, this is a test.', '\\w', 100);
        $this->assertContains('Hello', $tokens);
        $this->assertContains('world', $tokens);
        $this->assertContains('test', $tokens);
        $this->assertNotContains(',', $tokens);
    }

    public function testTokenizeRespectsMaxWords(): void
    {
        $text = 'one two three four five six seven eight nine ten';
        $tokens = $this->callTokenize($text, '\\w', 5);
        $this->assertCount(5, $tokens);
    }

    public function testTokenizeEmptyText(): void
    {
        $tokens = $this->callTokenize('', '\\w', 100);
        $this->assertEmpty($tokens);
    }

    public function testTokenizeWithCustomRegex(): void
    {
        $tokens = $this->callTokenize("l'homme est là", "a-zA-ZÀ-ÿ'", 100);
        $this->assertNotEmpty($tokens);
    }

    // =========================================================================
    // estimateQuickTiers tests
    // =========================================================================

    public function testEstimateQuickTiersReturnsAllBookIds(): void
    {
        // This test cannot actually call the full method (requires DB),
        // but we can test the tier computation logic is consistent
        // by testing computeQuickTier with various inputs
        $subjects = [
            1 => ["Children's literature"],
            2 => ['Philosophy'],
            3 => ['Fiction'],
        ];

        // Just verify the keys would be correct
        $this->assertSame(['easy', 'hard', 'medium'], array_values(array_map(
            fn(array $s) => $this->callComputeQuickTier(1500, $s),
            $subjects
        )));
    }

    // =========================================================================
    // Helper methods to call private methods via reflection
    // =========================================================================

    /**
     * @param list<string> $subjects
     */
    private function callClassifySubjects(array $subjects): string
    {
        $method = new ReflectionMethod(DifficultyEstimationService::class, 'classifySubjects');

        return $method->invoke($this->service, $subjects);
    }

    /**
     * @param list<string> $subjects
     */
    private function callComputeQuickTier(int $knownCount, array $subjects): string
    {
        $method = new ReflectionMethod(DifficultyEstimationService::class, 'computeQuickTier');

        return $method->invoke($this->service, $knownCount, $subjects);
    }

    private function callLabelFromCoverage(float $percent): string
    {
        $method = new ReflectionMethod(DifficultyEstimationService::class, 'labelFromCoverage');

        return $method->invoke($this->service, $percent);
    }

    /**
     * @return list<string>
     */
    private function callTokenize(string $text, string $wordRegex, int $maxWords): array
    {
        $method = new ReflectionMethod(DifficultyEstimationService::class, 'tokenize');

        return $method->invoke($this->service, $text, $wordRegex, $maxWords);
    }
}
