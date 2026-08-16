<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\UseCases;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Application\Services\SimilarityCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
    #[DataProvider('phoneticWeightProvider')]
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

    // =========================================================================
    // Coverage-based ranking (#137)
    // =========================================================================

    /**
     * Build candidates from a term => status map, numbering them from 1.
     *
     * @param array<string, int> $terms Terms and their status
     *
     * @return list<array{id: int, textLc: string, status: int}>
     */
    private static function candidates(array $terms): array
    {
        $candidates = [];
        $id = 1;
        foreach ($terms as $textLc => $status) {
            $candidates[] = ['id' => $id, 'textLc' => (string) $textLc, 'status' => $status];
            $id++;
        }
        return $candidates;
    }

    public function testCoveringTermBeatsASiblingSharingTheSameHalf(): void
    {
        // The reported case: every word built on "geschwindigkeit" scores on
        // that shared half, so the term explaining the *other* half used to be
        // pushed out. 1 = geschwindigkeit, 2 = begrenzung, 3 = …messer.
        $useCase = new FindSimilarTerms();

        $result = $useCase->rankByCoverage(
            self::candidates([
                'geschwindigkeit' => 1,
                'begrenzung' => 1,
                'geschwindigkeitsmesser' => 1,
            ]),
            'geschwindigkeitsbegrenzung',
            3,
            0.33
        );

        $this->assertSame([1, 2, 3], $result);
    }

    public function testASiblingDoesNotCrowdOutTheOtherHalf(): void
    {
        $useCase = new FindSimilarTerms();

        $result = $useCase->rankByCoverage(
            self::candidates([
                'great' => 1,
                'idea' => 1,
                'greatriver' => 1,
                'greatbuilding' => 1,
            ]),
            'greatidea',
            2,
            0.33
        );

        // "great" explains the head, "idea" the tail — the two siblings that
        // only repeat the head are left out of a two-slot list entirely.
        $this->assertSame([1, 2], $result);
    }

    public function testFirstPickIsStillThePlainPairwiseBest(): void
    {
        $useCase = new FindSimilarTerms();
        $calculator = new SimilarityCalculator();

        $terms = ['begrenzung' => 1, 'geschwindigkeit' => 1, 'geschwindigkeitsmesser' => 1];
        $result = $useCase->rankByCoverage(
            self::candidates($terms),
            'geschwindigkeitsbegrenzung',
            3,
            0.33
        );

        $best = '';
        $bestScore = -1.0;
        foreach (array_keys($terms) as $term) {
            $score = $calculator->getCombinedSimilarityRanking(
                'geschwindigkeitsbegrenzung',
                (string) $term,
                0.3
            );
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = (string) $term;
            }
        }

        $this->assertSame(
            array_search($best, array_keys($terms), true) + 1,
            $result[0]
        );
    }

    public function testStatusWeightStillPromotesKnownTerms(): void
    {
        $useCase = new FindSimilarTerms();

        $unweighted = $useCase->rankByCoverage(
            self::candidates(['great' => 1, 'greatman' => 1]),
            'greatidea',
            1,
            0.33
        );
        $weighted = $useCase->rankByCoverage(
            self::candidates(['great' => 1, 'greatman' => 5]),
            'greatidea',
            1,
            0.33
        );

        $this->assertSame([1], $unweighted);
        $this->assertSame([2], $weighted);
    }

    public function testRespectsMaxCount(): void
    {
        $useCase = new FindSimilarTerms();

        $result = $useCase->rankByCoverage(
            self::candidates(['great' => 1, 'idea' => 1, 'greatriver' => 1]),
            'greatidea',
            2,
            0.33
        );

        $this->assertCount(2, $result);
    }

    public function testMaxCountOfZeroReturnsNothing(): void
    {
        $useCase = new FindSimilarTerms();

        $result = $useCase->rankByCoverage(
            self::candidates(['great' => 1]),
            'greatidea',
            0,
            0.33
        );

        $this->assertSame([], $result);
    }

    public function testDropsCandidatesBelowTheMinimumRanking(): void
    {
        $useCase = new FindSimilarTerms();

        $result = $useCase->rankByCoverage(
            self::candidates(['great' => 1, 'xylophone' => 1]),
            'greatidea',
            5,
            0.33
        );

        $this->assertSame([1], $result);
    }

    public function testReturnsNothingWithoutCandidates(): void
    {
        $useCase = new FindSimilarTerms();

        $this->assertSame([], $useCase->rankByCoverage([], 'greatidea', 5, 0.33));
    }

    public function testFillsTheListOnceTheTermIsFullyExplained(): void
    {
        $useCase = new FindSimilarTerms();

        // "great" + "idea" leave nothing uncovered; the siblings still have to
        // fill the remaining slots rather than be dropped.
        $result = $useCase->rankByCoverage(
            self::candidates([
                'great' => 1,
                'idea' => 1,
                'greatriver' => 1,
                'greatbuilding' => 1,
            ]),
            'greatidea',
            4,
            0.33
        );

        $this->assertCount(4, $result);
        $this->assertSame([1, 2], array_slice($result, 0, 2));
        $this->assertContains(3, $result);
        $this->assertContains(4, $result);
    }
}
