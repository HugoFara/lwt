<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\Domain;

use InvalidArgumentException;
use Lwt\Modules\Review\Domain\ReviewConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReviewConfiguration value object.
 *
 * Tests factory methods, SQL generation, and URL generation.
 */
class ReviewConfigurationTest extends TestCase
{
    // ===== Constants Tests =====

    public function testKeyConstants(): void
    {
        $this->assertEquals('lang', ReviewConfiguration::KEY_LANG);
        $this->assertEquals('text', ReviewConfiguration::KEY_TEXT);
        $this->assertEquals('words', ReviewConfiguration::KEY_WORDS);
        $this->assertEquals('texts', ReviewConfiguration::KEY_TEXTS);
        $this->assertEquals('raw_sql', ReviewConfiguration::KEY_RAW_SQL);
    }

    public function testTypeConstants(): void
    {
        $this->assertEquals(1, ReviewConfiguration::TYPE_TERM_TO_TRANSLATION);
        $this->assertEquals(2, ReviewConfiguration::TYPE_TRANSLATION_TO_TERM);
        $this->assertEquals(3, ReviewConfiguration::TYPE_SENTENCE_TO_TERM);
        $this->assertEquals(4, ReviewConfiguration::TYPE_TERM_TO_TRANSLATION_WORD);
        $this->assertEquals(5, ReviewConfiguration::TYPE_TRANSLATION_TO_TERM_WORD);
    }

    // ===== Constructor Tests =====

    public function testConstructorWithDefaults(): void
    {
        $config = new ReviewConfiguration('lang', 1);

        $this->assertEquals('lang', $config->reviewKey);
        $this->assertEquals(1, $config->selection);
        $this->assertEquals(1, $config->reviewType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    public function testConstructorWithAllParameters(): void
    {
        $config = new ReviewConfiguration('text', 42, 3, true, true);

        $this->assertEquals('text', $config->reviewKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(3, $config->reviewType);
        $this->assertTrue($config->wordMode);
        $this->assertTrue($config->isTableMode);
    }

    public function testConstructorWithArraySelection(): void
    {
        $wordIds = [1, 2, 3, 4];
        $config = new ReviewConfiguration('words', $wordIds);

        $this->assertEquals($wordIds, $config->selection);
    }

    public function testConstructorWithStringSelection(): void
    {
        $sql = 'SELECT * FROM words WHERE WoStatus = 1';
        $config = new ReviewConfiguration('raw_sql', $sql);

        $this->assertEquals($sql, $config->selection);
    }

    // ===== fromLanguage Factory Tests =====

    public function testFromLanguageCreatesConfig(): void
    {
        $config = ReviewConfiguration::fromLanguage(42);

        $this->assertEquals(ReviewConfiguration::KEY_LANG, $config->reviewKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(1, $config->reviewType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    public function testFromLanguageWithTestType(): void
    {
        $config = ReviewConfiguration::fromLanguage(42, 3);

        $this->assertEquals(3, $config->reviewType);
        $this->assertFalse($config->wordMode);
    }

    public function testFromLanguageWithWordModeTestType(): void
    {
        // Test types 4 and 5 should automatically enable word mode
        $config = ReviewConfiguration::fromLanguage(42, 4);

        $this->assertEquals(4, $config->reviewType);
        $this->assertTrue($config->wordMode);
    }

    public function testFromLanguageWithExplicitWordMode(): void
    {
        $config = ReviewConfiguration::fromLanguage(42, 1, true);

        $this->assertEquals(1, $config->reviewType);
        $this->assertTrue($config->wordMode);
    }

    public function testFromLanguageClampsTestType(): void
    {
        // Test type should be clamped to 1-5
        $config = ReviewConfiguration::fromLanguage(42, 10);
        $this->assertEquals(5, $config->reviewType);

        $config = ReviewConfiguration::fromLanguage(42, 0);
        $this->assertEquals(1, $config->reviewType);

        $config = ReviewConfiguration::fromLanguage(42, -1);
        $this->assertEquals(1, $config->reviewType);
    }

    // ===== fromText Factory Tests =====

    public function testFromTextCreatesConfig(): void
    {
        $config = ReviewConfiguration::fromText(123);

        $this->assertEquals(ReviewConfiguration::KEY_TEXT, $config->reviewKey);
        $this->assertEquals(123, $config->selection);
        $this->assertEquals(1, $config->reviewType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    public function testFromTextWithTestType(): void
    {
        $config = ReviewConfiguration::fromText(123, 2);

        $this->assertEquals(2, $config->reviewType);
    }

    public function testFromTextWithWordModeTestType(): void
    {
        $config = ReviewConfiguration::fromText(123, 5);

        $this->assertEquals(5, $config->reviewType);
        $this->assertTrue($config->wordMode);
    }

    // ===== fromWords Factory Tests =====

    public function testFromWordsCreatesConfig(): void
    {
        $wordIds = [10, 20, 30];
        $config = ReviewConfiguration::fromWords($wordIds);

        $this->assertEquals(ReviewConfiguration::KEY_WORDS, $config->reviewKey);
        $this->assertEquals([10, 20, 30], $config->selection);
        $this->assertEquals(1, $config->reviewType);
        $this->assertFalse($config->isTableMode);
    }

    public function testFromWordsConvertsToIntegers(): void
    {
        $wordIds = ['10', '20', '30'];
        $config = ReviewConfiguration::fromWords($wordIds);

        $this->assertEquals([10, 20, 30], $config->selection);
    }

    public function testFromWordsWithTestType(): void
    {
        $config = ReviewConfiguration::fromWords([1, 2], 3);

        $this->assertEquals(3, $config->reviewType);
    }

    // ===== fromTexts Factory Tests =====

    public function testFromTextsCreatesConfig(): void
    {
        $textIds = [100, 200, 300];
        $config = ReviewConfiguration::fromTexts($textIds);

        $this->assertEquals(ReviewConfiguration::KEY_TEXTS, $config->reviewKey);
        $this->assertEquals([100, 200, 300], $config->selection);
        $this->assertEquals(1, $config->reviewType);
    }

    public function testFromTextsConvertsToIntegers(): void
    {
        $textIds = ['100', '200'];
        $config = ReviewConfiguration::fromTexts($textIds);

        $this->assertEquals([100, 200], $config->selection);
    }

    // ===== forTableMode Factory Tests =====

    public function testForTableModeCreatesConfig(): void
    {
        $config = ReviewConfiguration::forTableMode('lang', 42);

        $this->assertEquals('lang', $config->reviewKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(1, $config->reviewType);
        $this->assertFalse($config->wordMode);
        $this->assertTrue($config->isTableMode);
    }

    public function testForTableModeWithArraySelection(): void
    {
        $config = ReviewConfiguration::forTableMode('words', [1, 2, 3]);

        $this->assertEquals([1, 2, 3], $config->selection);
        $this->assertTrue($config->isTableMode);
    }

    // ===== getBaseType Tests =====

    public function testGetBaseTypeForTypes1To3(): void
    {
        $config1 = ReviewConfiguration::fromLanguage(1, 1);
        $this->assertEquals(1, $config1->getBaseType());

        $config2 = ReviewConfiguration::fromLanguage(1, 2);
        $this->assertEquals(2, $config2->getBaseType());

        $config3 = ReviewConfiguration::fromLanguage(1, 3);
        $this->assertEquals(3, $config3->getBaseType());
    }

    public function testGetBaseTypeForWordModeTypes(): void
    {
        // Types 4 and 5 are word-mode versions of 1 and 2
        $config4 = ReviewConfiguration::fromLanguage(1, 4);
        $this->assertEquals(1, $config4->getBaseType());

        $config5 = ReviewConfiguration::fromLanguage(1, 5);
        $this->assertEquals(2, $config5->getBaseType());
    }

    // ===== toSqlProjection Tests =====

    public function testToSqlProjectionForLanguage(): void
    {
        $config = ReviewConfiguration::fromLanguage(42);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('WoLgID = 42', $sql);
    }

    public function testToSqlProjectionForText(): void
    {
        $config = ReviewConfiguration::fromText(123);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('textitems2', $sql);
        $this->assertStringContainsString('Ti2TxID = 123', $sql);
    }

    public function testToSqlProjectionForWords(): void
    {
        $config = ReviewConfiguration::fromWords([10, 20, 30]);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('WoID IN (10,20,30)', $sql);
    }

    public function testToSqlProjectionForTexts(): void
    {
        $config = ReviewConfiguration::fromTexts([100, 200]);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('textitems2', $sql);
        $this->assertStringContainsString('Ti2TxID IN (100,200)', $sql);
    }

    public function testToSqlProjectionForRawSql(): void
    {
        $rawSql = ' custom_table WHERE custom_condition ';
        $config = new ReviewConfiguration(ReviewConfiguration::KEY_RAW_SQL, $rawSql);
        $sql = $config->toSqlProjection();

        $this->assertEquals($rawSql, $sql);
    }

    public function testToSqlProjectionThrowsForInvalidKey(): void
    {
        $config = new ReviewConfiguration('invalid_key', 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid review key');

        $config->toSqlProjection();
    }

    // ===== getSelectionString Tests =====

    public function testGetSelectionStringForInteger(): void
    {
        $config = ReviewConfiguration::fromLanguage(42);
        $this->assertEquals('42', $config->getSelectionString());
    }

    public function testGetSelectionStringForArray(): void
    {
        $config = ReviewConfiguration::fromWords([10, 20, 30]);
        $this->assertEquals('10,20,30', $config->getSelectionString());
    }

    public function testGetSelectionStringForString(): void
    {
        $config = new ReviewConfiguration('raw_sql', 'custom sql');
        $this->assertEquals('custom sql', $config->getSelectionString());
    }

    // ===== toUrlProperty Tests =====

    public function testToUrlPropertyForLanguage(): void
    {
        $config = ReviewConfiguration::fromLanguage(42);
        $this->assertEquals('lang=42', $config->toUrlProperty());
    }

    public function testToUrlPropertyForText(): void
    {
        $config = ReviewConfiguration::fromText(123);
        $this->assertEquals('text=123', $config->toUrlProperty());
    }

    public function testToUrlPropertyForWords(): void
    {
        $config = ReviewConfiguration::fromWords([1, 2, 3]);
        $this->assertEquals('selection=2', $config->toUrlProperty());
    }

    public function testToUrlPropertyForTexts(): void
    {
        $config = ReviewConfiguration::fromTexts([100, 200]);
        $this->assertEquals('selection=3', $config->toUrlProperty());
    }

    public function testToUrlPropertyForUnknownKey(): void
    {
        $config = new ReviewConfiguration('unknown', 1);
        $this->assertEquals('', $config->toUrlProperty());
    }

    // ===== isValid Tests =====

    public function testIsValidReturnsTrueForValidConfig(): void
    {
        $config = ReviewConfiguration::fromLanguage(1);
        $this->assertTrue($config->isValid());
    }

    public function testIsValidReturnsFalseForEmptyKey(): void
    {
        $config = new ReviewConfiguration('', 1);
        $this->assertFalse($config->isValid());
    }

    // ===== Immutability Tests =====

    public function testConfigIsReadonly(): void
    {
        $config = ReviewConfiguration::fromLanguage(42, 2);

        // All properties are public readonly
        $this->assertEquals('lang', $config->reviewKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(2, $config->reviewType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    // ===== Edge Cases Tests =====

    public function testFromWordsWithEmptyArray(): void
    {
        $config = ReviewConfiguration::fromWords([]);
        $this->assertEquals([], $config->selection);
    }

    public function testFromTextsWithSingleId(): void
    {
        $config = ReviewConfiguration::fromTexts([100]);
        $this->assertEquals([100], $config->selection);
    }

    public function testWordModeAutoEnabledForType4(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 4, false);
        // Word mode should be true because reviewType > 3
        $this->assertTrue($config->wordMode);
    }

    public function testWordModeAutoEnabledForType5(): void
    {
        $config = ReviewConfiguration::fromText(1, 5, false);
        // Word mode should be true because reviewType > 3
        $this->assertTrue($config->wordMode);
    }
}
