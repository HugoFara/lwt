<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Domain\ValueObject;

use InvalidArgumentException;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TermStatus value object.
 *
 * Tests all factory methods, state transitions, and business logic.
 */
class TermStatusTest extends TestCase
{
    // ===== Factory Method Tests =====

    public function testFromIntCreatesValidStatus(): void
    {
        $status = TermStatus::fromInt(1);
        $this->assertInstanceOf(TermStatus::class, $status);
        $this->assertEquals(1, $status->toInt());
    }
    #[DataProvider('validStatusProvider')]
    public function testFromIntAcceptsAllValidStatuses(int $value): void
    {
        $status = TermStatus::fromInt($value);
        $this->assertEquals($value, $status->toInt());
    }

    public static function validStatusProvider(): array
    {
        return [
            'new (1)' => [1],
            'learning 2' => [2],
            'learning 3' => [3],
            'learning 4' => [4],
            'learned (5)' => [5],
            'ignored (98)' => [98],
            'well-known (99)' => [99],
        ];
    }
    #[DataProvider('invalidStatusProvider')]
    public function testFromIntRejectsInvalidStatuses(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid term status');
        TermStatus::fromInt($value);
    }

    public static function invalidStatusProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'six' => [6],
            'fifty' => [50],
            'ninety-seven' => [97],
            'hundred' => [100],
        ];
    }

    public function testNewFactoryCreatesStatus1(): void
    {
        $status = TermStatus::new();
        $this->assertEquals(TermStatus::NEW, $status->toInt());
        $this->assertEquals(1, $status->toInt());
    }

    public function testLearnedFactoryCreatesStatus5(): void
    {
        $status = TermStatus::learned();
        $this->assertEquals(TermStatus::LEARNED, $status->toInt());
        $this->assertEquals(5, $status->toInt());
    }

    public function testIgnoredFactoryCreatesStatus98(): void
    {
        $status = TermStatus::ignored();
        $this->assertEquals(TermStatus::IGNORED, $status->toInt());
        $this->assertEquals(98, $status->toInt());
    }

    public function testWellKnownFactoryCreatesStatus99(): void
    {
        $status = TermStatus::wellKnown();
        $this->assertEquals(TermStatus::WELL_KNOWN, $status->toInt());
        $this->assertEquals(99, $status->toInt());
    }

    // ===== Advance Tests =====

    public function testAdvanceIncreasesLearningStage(): void
    {
        $status1 = TermStatus::new();
        $status2 = $status1->advance();
        $this->assertEquals(2, $status2->toInt());

        $status3 = $status2->advance();
        $this->assertEquals(3, $status3->toInt());

        $status4 = $status3->advance();
        $this->assertEquals(4, $status4->toInt());

        $status5 = $status4->advance();
        $this->assertEquals(5, $status5->toInt());
    }

    public function testAdvanceStopsAtLearned(): void
    {
        $status = TermStatus::learned();
        $advanced = $status->advance();
        $this->assertEquals(5, $advanced->toInt());
    }

    public function testAdvanceDoesNotChangeIgnored(): void
    {
        $status = TermStatus::ignored();
        $advanced = $status->advance();
        $this->assertEquals(98, $advanced->toInt());
    }

    public function testAdvanceDoesNotChangeWellKnown(): void
    {
        $status = TermStatus::wellKnown();
        $advanced = $status->advance();
        $this->assertEquals(99, $advanced->toInt());
    }

    public function testAdvanceReturnsNewInstance(): void
    {
        $status1 = TermStatus::new();
        $status2 = $status1->advance();
        $this->assertNotSame($status1, $status2);
        $this->assertEquals(1, $status1->toInt()); // Original unchanged
    }

    // ===== Decrease Tests =====

    public function testDecreaseReducesLearningStage(): void
    {
        $status5 = TermStatus::learned();
        $status4 = $status5->decrease();
        $this->assertEquals(4, $status4->toInt());

        $status3 = $status4->decrease();
        $this->assertEquals(3, $status3->toInt());

        $status2 = $status3->decrease();
        $this->assertEquals(2, $status2->toInt());

        $status1 = $status2->decrease();
        $this->assertEquals(1, $status1->toInt());
    }

    public function testDecreaseStopsAtNew(): void
    {
        $status = TermStatus::new();
        $decreased = $status->decrease();
        $this->assertEquals(1, $decreased->toInt());
    }

    public function testDecreaseDoesNotChangeIgnored(): void
    {
        $status = TermStatus::ignored();
        $decreased = $status->decrease();
        $this->assertEquals(98, $decreased->toInt());
    }

    public function testDecreaseDoesNotChangeWellKnown(): void
    {
        $status = TermStatus::wellKnown();
        $decreased = $status->decrease();
        $this->assertEquals(99, $decreased->toInt());
    }

    // ===== isKnown Tests =====

    public function testIsKnownReturnsTrueForLearned(): void
    {
        $this->assertTrue(TermStatus::learned()->isKnown());
    }

    public function testIsKnownReturnsTrueForWellKnown(): void
    {
        $this->assertTrue(TermStatus::wellKnown()->isKnown());
    }
    #[DataProvider('notKnownStatusProvider')]
    public function testIsKnownReturnsFalseForLearningStages(int $value): void
    {
        $status = TermStatus::fromInt($value);
        $this->assertFalse($status->isKnown());
    }

    public static function notKnownStatusProvider(): array
    {
        return [
            'new (1)' => [1],
            'learning 2' => [2],
            'learning 3' => [3],
            'learning 4' => [4],
            'ignored (98)' => [98],
        ];
    }

    // ===== isLearning Tests =====
    #[DataProvider('learningStatusProvider')]
    public function testIsLearningReturnsTrueForLearningStages(int $value): void
    {
        $status = TermStatus::fromInt($value);
        $this->assertTrue($status->isLearning());
    }

    public static function learningStatusProvider(): array
    {
        return [
            'new (1)' => [1],
            'learning 2' => [2],
            'learning 3' => [3],
            'learning 4' => [4],
        ];
    }

    public function testIsLearningReturnsFalseForLearned(): void
    {
        $this->assertFalse(TermStatus::learned()->isLearning());
    }

    public function testIsLearningReturnsFalseForIgnored(): void
    {
        $this->assertFalse(TermStatus::ignored()->isLearning());
    }

    public function testIsLearningReturnsFalseForWellKnown(): void
    {
        $this->assertFalse(TermStatus::wellKnown()->isLearning());
    }

    // ===== isSpecial Tests =====

    public function testIsSpecialReturnsTrueForIgnored(): void
    {
        $this->assertTrue(TermStatus::ignored()->isSpecial());
    }

    public function testIsSpecialReturnsTrueForWellKnown(): void
    {
        $this->assertTrue(TermStatus::wellKnown()->isSpecial());
    }
    #[DataProvider('normalStatusProvider')]
    public function testIsSpecialReturnsFalseForNormalStatuses(int $value): void
    {
        $status = TermStatus::fromInt($value);
        $this->assertFalse($status->isSpecial());
    }

    public static function normalStatusProvider(): array
    {
        return [
            'new (1)' => [1],
            'learning 2' => [2],
            'learning 3' => [3],
            'learning 4' => [4],
            'learned (5)' => [5],
        ];
    }

    // ===== isIgnored Tests =====

    public function testIsIgnoredReturnsTrueForIgnored(): void
    {
        $this->assertTrue(TermStatus::ignored()->isIgnored());
    }
    #[DataProvider('nonIgnoredStatusProvider')]
    public function testIsIgnoredReturnsFalseForOtherStatuses(int $value): void
    {
        $status = TermStatus::fromInt($value);
        $this->assertFalse($status->isIgnored());
    }

    public static function nonIgnoredStatusProvider(): array
    {
        return [
            'new (1)' => [1],
            'learning 2' => [2],
            'learning 3' => [3],
            'learning 4' => [4],
            'learned (5)' => [5],
            'well-known (99)' => [99],
        ];
    }

    // ===== needsReview Tests =====
    #[DataProvider('learningStatusProvider')]
    public function testNeedsReviewReturnsTrueForLearningStages(int $value): void
    {
        $status = TermStatus::fromInt($value);
        $this->assertTrue($status->needsReview());
    }

    public function testNeedsReviewReturnsFalseForLearned(): void
    {
        $this->assertFalse(TermStatus::learned()->needsReview());
    }

    public function testNeedsReviewReturnsFalseForIgnored(): void
    {
        $this->assertFalse(TermStatus::ignored()->needsReview());
    }

    public function testNeedsReviewReturnsFalseForWellKnown(): void
    {
        $this->assertFalse(TermStatus::wellKnown()->needsReview());
    }

    // ===== equals Tests =====

    public function testEqualsSameValue(): void
    {
        $status1 = TermStatus::new();
        $status2 = TermStatus::new();
        $this->assertTrue($status1->equals($status2));
    }

    public function testEqualsDifferentValue(): void
    {
        $status1 = TermStatus::new();
        $status2 = TermStatus::learned();
        $this->assertFalse($status1->equals($status2));
    }

    public function testEqualsFromDifferentFactoryMethods(): void
    {
        $status1 = TermStatus::fromInt(1);
        $status2 = TermStatus::new();
        $this->assertTrue($status1->equals($status2));
    }

    // ===== label Tests =====
    #[DataProvider('labelProvider')]
    public function testLabelReturnsCorrectString(int $value, string $expectedLabel): void
    {
        $status = TermStatus::fromInt($value);
        $this->assertEquals($expectedLabel, $status->label());
    }

    public static function labelProvider(): array
    {
        return [
            'new' => [1, 'New'],
            'learning 2' => [2, 'Learning (2)'],
            'learning 3' => [3, 'Learning (3)'],
            'learning 4' => [4, 'Learning (4)'],
            'learned' => [5, 'Learned'],
            'ignored' => [98, 'Ignored'],
            'well-known' => [99, 'Well Known'],
        ];
    }

    // ===== __toString Tests =====

    public function testToStringReturnsStringValue(): void
    {
        $status = TermStatus::new();
        $this->assertEquals('1', (string) $status);
    }

    public function testToStringForAllStatuses(): void
    {
        $this->assertEquals('1', (string) TermStatus::new());
        $this->assertEquals('5', (string) TermStatus::learned());
        $this->assertEquals('98', (string) TermStatus::ignored());
        $this->assertEquals('99', (string) TermStatus::wellKnown());
    }

    // ===== Constants Tests =====

    public function testConstantsHaveCorrectValues(): void
    {
        $this->assertEquals(1, TermStatus::NEW);
        $this->assertEquals(2, TermStatus::LEARNING_2);
        $this->assertEquals(3, TermStatus::LEARNING_3);
        $this->assertEquals(4, TermStatus::LEARNING_4);
        $this->assertEquals(5, TermStatus::LEARNED);
        $this->assertEquals(98, TermStatus::IGNORED);
        $this->assertEquals(99, TermStatus::WELL_KNOWN);
    }

    // ===== Immutability Tests =====

    public function testAdvanceDoesNotMutateOriginal(): void
    {
        $original = TermStatus::fromInt(2);
        $original->advance();
        $this->assertEquals(2, $original->toInt());
    }

    public function testDecreaseDoesNotMutateOriginal(): void
    {
        $original = TermStatus::fromInt(3);
        $original->decrease();
        $this->assertEquals(3, $original->toInt());
    }

    // ===== Static helper Tests =====

    #[DataProvider('validStatusProvider')]
    public function testIsValidReturnsTrueForValidStatuses(int $value): void
    {
        $this->assertTrue(TermStatus::isValid($value));
    }

    #[DataProvider('invalidStatusProvider')]
    public function testIsValidReturnsFalseForInvalidStatuses(int $value): void
    {
        $this->assertFalse(TermStatus::isValid($value));
    }

    public function testValuesReturnsAllSevenInCanonicalOrder(): void
    {
        $this->assertSame([1, 2, 3, 4, 5, 99, 98], TermStatus::values());
    }

    public function testIsKnownValue(): void
    {
        $this->assertTrue(TermStatus::isKnownValue(5));
        $this->assertTrue(TermStatus::isKnownValue(99));
        $this->assertFalse(TermStatus::isKnownValue(1));
        $this->assertFalse(TermStatus::isKnownValue(98));
        // Safe on invalid input (does not throw)
        $this->assertFalse(TermStatus::isKnownValue(0));
        $this->assertFalse(TermStatus::isKnownValue(42));
    }

    public function testIsIgnoredValue(): void
    {
        $this->assertTrue(TermStatus::isIgnoredValue(98));
        $this->assertFalse(TermStatus::isIgnoredValue(99));
        $this->assertFalse(TermStatus::isIgnoredValue(5));
        $this->assertFalse(TermStatus::isIgnoredValue(0));
    }

    public function testIsLearningValue(): void
    {
        foreach ([1, 2, 3, 4, 5] as $value) {
            $this->assertTrue(TermStatus::isLearningValue($value));
        }
        $this->assertFalse(TermStatus::isLearningValue(98));
        $this->assertFalse(TermStatus::isLearningValue(99));
        $this->assertFalse(TermStatus::isLearningValue(0));
        $this->assertFalse(TermStatus::isLearningValue(6));
    }

    // ===== Display-metadata Tests =====

    #[DataProvider('abbreviationProvider')]
    public function testAbbreviation(int $value, string $expected): void
    {
        $this->assertSame($expected, TermStatus::fromInt($value)->abbreviation());
    }

    public static function abbreviationProvider(): array
    {
        return [
            [1, '1'], [2, '2'], [3, '3'], [4, '4'], [5, '5'],
            // 98/99 fall back to the full name in display code.
            [98, ''], [99, ''],
        ];
    }

    #[DataProvider('cssClassProvider')]
    public function testCssClass(int $value, string $expected): void
    {
        $this->assertSame($expected, TermStatus::fromInt($value)->cssClass());
    }

    public static function cssClassProvider(): array
    {
        return [
            [1, 'status1'], [2, 'status2'], [3, 'status3'], [4, 'status4'],
            [5, 'status5'], [98, 'status98'], [99, 'status99'],
        ];
    }

    #[DataProvider('validStatusProvider')]
    public function testColourHexIsValidHex(int $value): void
    {
        $this->assertMatchesRegularExpression(
            '/^#[0-9A-Fa-f]{6}$/',
            TermStatus::fromInt($value)->colourHex()
        );
    }

    public function testOrderFollowsCanonicalSequence(): void
    {
        $this->assertSame(1, TermStatus::fromInt(1)->order());
        $this->assertSame(5, TermStatus::fromInt(5)->order());
        // Well-known precedes ignored in display order.
        $this->assertSame(6, TermStatus::fromInt(99)->order());
        $this->assertSame(7, TermStatus::fromInt(98)->order());
    }

    #[DataProvider('displayNameProvider')]
    public function testDisplayName(int $value, string $expected): void
    {
        $this->assertSame($expected, TermStatus::fromInt($value)->displayName());
    }

    public static function displayNameProvider(): array
    {
        // Localized names; learning stages 1-4 all read "Learning".
        return [
            [1, 'Learning'], [2, 'Learning'], [3, 'Learning'], [4, 'Learning'],
            [5, 'Learned'], [98, 'Ignored'], [99, 'Well Known'],
        ];
    }

    // ===== definitions() Tests =====

    public function testDefinitionsReturnsAllSevenInOrder(): void
    {
        $definitions = TermStatus::definitions();
        $this->assertCount(7, $definitions);
        $this->assertSame(
            [1, 2, 3, 4, 5, 99, 98],
            array_column($definitions, 'value')
        );
    }

    public function testDefinitionsEntriesHaveCompleteShape(): void
    {
        foreach (TermStatus::definitions() as $definition) {
            foreach (
                ['value', 'name', 'abbr', 'cssClass', 'colour',
                'order', 'isKnown', 'isLearning', 'isIgnored'] as $key
            ) {
                $this->assertArrayHasKey($key, $definition);
            }
            $this->assertIsString($definition['name']);
            $this->assertMatchesRegularExpression('/^status\d+$/', $definition['cssClass']);
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $definition['colour']);
        }
    }

    public function testDefinitionsClassificationFlagsAreConsistent(): void
    {
        $byValue = [];
        foreach (TermStatus::definitions() as $definition) {
            $byValue[$definition['value']] = $definition;
        }
        $this->assertTrue($byValue[5]['isKnown']);
        $this->assertTrue($byValue[99]['isKnown']);
        $this->assertFalse($byValue[1]['isKnown']);
        $this->assertTrue($byValue[98]['isIgnored']);
        $this->assertTrue($byValue[1]['isLearning']);
        $this->assertFalse($byValue[98]['isLearning']);
    }
}
