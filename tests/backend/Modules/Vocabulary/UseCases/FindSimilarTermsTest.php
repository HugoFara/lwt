<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\UseCases;

use Lwt\Core\Globals;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Application\Services\SimilarityCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the FindSimilarTerms use case.
 *
 * Note: The execute() method depends on QueryBuilder which requires a database.
 * These tests focus on the constructor and SimilarityCalculator integration.
 * Full integration tests would require a database setup.
 */
class FindSimilarTermsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorCreatesDefaultCalculator(): void
    {
        $useCase = new FindSimilarTerms();

        $this->assertInstanceOf(FindSimilarTerms::class, $useCase);
    }

    public function testConstructorAcceptsCustomCalculator(): void
    {
        $calculator = new SimilarityCalculator();
        $useCase = new FindSimilarTerms($calculator);

        $this->assertInstanceOf(FindSimilarTerms::class, $useCase);
    }

    // =========================================================================
    // Integration with SimilarityCalculator
    // =========================================================================

    public function testSimilarityCalculatorGetsCombinedRanking(): void
    {
        $calculator = new SimilarityCalculator();

        // Test the underlying calculator which is used by the use case
        $similarity = $calculator->getCombinedSimilarityRanking('hello', 'hallo', 0.3);

        $this->assertIsFloat($similarity);
        $this->assertGreaterThan(0.0, $similarity);
        $this->assertLessThanOrEqual(1.0, $similarity);
    }

    public function testSimilarityCalculatorGetsStatusWeight(): void
    {
        $calculator = new SimilarityCalculator();

        // Learning statuses (1-5) should have weights
        $this->assertGreaterThan(0.0, $calculator->getStatusWeight(1));
        $this->assertGreaterThan(0.0, $calculator->getStatusWeight(5));

        // Special statuses
        $this->assertGreaterThan(0.0, $calculator->getStatusWeight(98)); // Ignored
        $this->assertGreaterThan(0.0, $calculator->getStatusWeight(99)); // Well-known
    }

    public function testSimilarityCalculatorHigherStatusGetsHigherWeight(): void
    {
        $calculator = new SimilarityCalculator();

        // Higher learning statuses should generally have higher weights
        $weight1 = $calculator->getStatusWeight(1);
        $weight5 = $calculator->getStatusWeight(5);

        $this->assertGreaterThanOrEqual($weight1, $weight5);
    }

    // =========================================================================
    // Edge Cases for Text Comparison
    // =========================================================================

    public function testSimilarityForIdenticalStrings(): void
    {
        $calculator = new SimilarityCalculator();

        $similarity = $calculator->getCombinedSimilarityRanking('test', 'test', 0.3);

        $this->assertEquals(1.0, $similarity);
    }

    public function testSimilarityForCompletelyDifferentStrings(): void
    {
        $calculator = new SimilarityCalculator();

        $similarity = $calculator->getCombinedSimilarityRanking('abc', 'xyz', 0.3);

        $this->assertLessThan(0.5, $similarity);
    }

    public function testSimilarityForEmptyString(): void
    {
        $calculator = new SimilarityCalculator();

        $similarity = $calculator->getCombinedSimilarityRanking('', 'test', 0.3);

        $this->assertIsFloat($similarity);
    }

    public function testSimilarityForUnicodeStrings(): void
    {
        $calculator = new SimilarityCalculator();

        $similarity = $calculator->getCombinedSimilarityRanking('日本語', '日本人', 0.3);

        $this->assertIsFloat($similarity);
        $this->assertGreaterThan(0.0, $similarity);
    }

    /**
     * @dataProvider phoneticWeightProvider
     */
    public function testPhoneticWeightAffectsSimilarity(float $weight): void
    {
        $calculator = new SimilarityCalculator();

        $similarity = $calculator->getCombinedSimilarityRanking('hello', 'hallo', $weight);

        $this->assertIsFloat($similarity);
        $this->assertGreaterThanOrEqual(0.0, $similarity);
        $this->assertLessThanOrEqual(1.0, $similarity);
    }

    /**
     * @return array<string, array{float}>
     */
    public static function phoneticWeightProvider(): array
    {
        return [
            'no phonetic' => [0.0],
            'low phonetic' => [0.1],
            'default phonetic' => [0.3],
            'high phonetic' => [0.5],
            'max phonetic' => [1.0],
        ];
    }
}
