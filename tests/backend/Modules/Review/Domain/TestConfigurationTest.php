<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\Domain;

use InvalidArgumentException;
use Lwt\Modules\Review\Domain\TestConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TestConfiguration value object.
 *
 * Tests factory methods, SQL generation, and URL generation.
 */
class TestConfigurationTest extends TestCase
{
    // ===== Constants Tests =====

    public function testKeyConstants(): void
    {
        $this->assertEquals('lang', TestConfiguration::KEY_LANG);
        $this->assertEquals('text', TestConfiguration::KEY_TEXT);
        $this->assertEquals('words', TestConfiguration::KEY_WORDS);
        $this->assertEquals('texts', TestConfiguration::KEY_TEXTS);
        $this->assertEquals('raw_sql', TestConfiguration::KEY_RAW_SQL);
    }

    public function testTypeConstants(): void
    {
        $this->assertEquals(1, TestConfiguration::TYPE_TERM_TO_TRANSLATION);
        $this->assertEquals(2, TestConfiguration::TYPE_TRANSLATION_TO_TERM);
        $this->assertEquals(3, TestConfiguration::TYPE_SENTENCE_TO_TERM);
        $this->assertEquals(4, TestConfiguration::TYPE_TERM_TO_TRANSLATION_WORD);
        $this->assertEquals(5, TestConfiguration::TYPE_TRANSLATION_TO_TERM_WORD);
    }

    // ===== Constructor Tests =====

    public function testConstructorWithDefaults(): void
    {
        $config = new TestConfiguration('lang', 1);

        $this->assertEquals('lang', $config->testKey);
        $this->assertEquals(1, $config->selection);
        $this->assertEquals(1, $config->testType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    public function testConstructorWithAllParameters(): void
    {
        $config = new TestConfiguration('text', 42, 3, true, true);

        $this->assertEquals('text', $config->testKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(3, $config->testType);
        $this->assertTrue($config->wordMode);
        $this->assertTrue($config->isTableMode);
    }

    public function testConstructorWithArraySelection(): void
    {
        $wordIds = [1, 2, 3, 4];
        $config = new TestConfiguration('words', $wordIds);

        $this->assertEquals($wordIds, $config->selection);
    }

    public function testConstructorWithStringSelection(): void
    {
        $sql = 'SELECT * FROM words WHERE WoStatus = 1';
        $config = new TestConfiguration('raw_sql', $sql);

        $this->assertEquals($sql, $config->selection);
    }

    // ===== fromLanguage Factory Tests =====

    public function testFromLanguageCreatesConfig(): void
    {
        $config = TestConfiguration::fromLanguage(42);

        $this->assertEquals(TestConfiguration::KEY_LANG, $config->testKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(1, $config->testType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    public function testFromLanguageWithTestType(): void
    {
        $config = TestConfiguration::fromLanguage(42, 3);

        $this->assertEquals(3, $config->testType);
        $this->assertFalse($config->wordMode);
    }

    public function testFromLanguageWithWordModeTestType(): void
    {
        // Test types 4 and 5 should automatically enable word mode
        $config = TestConfiguration::fromLanguage(42, 4);

        $this->assertEquals(4, $config->testType);
        $this->assertTrue($config->wordMode);
    }

    public function testFromLanguageWithExplicitWordMode(): void
    {
        $config = TestConfiguration::fromLanguage(42, 1, true);

        $this->assertEquals(1, $config->testType);
        $this->assertTrue($config->wordMode);
    }

    public function testFromLanguageClampsTestType(): void
    {
        // Test type should be clamped to 1-5
        $config = TestConfiguration::fromLanguage(42, 10);
        $this->assertEquals(5, $config->testType);

        $config = TestConfiguration::fromLanguage(42, 0);
        $this->assertEquals(1, $config->testType);

        $config = TestConfiguration::fromLanguage(42, -1);
        $this->assertEquals(1, $config->testType);
    }

    // ===== fromText Factory Tests =====

    public function testFromTextCreatesConfig(): void
    {
        $config = TestConfiguration::fromText(123);

        $this->assertEquals(TestConfiguration::KEY_TEXT, $config->testKey);
        $this->assertEquals(123, $config->selection);
        $this->assertEquals(1, $config->testType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    public function testFromTextWithTestType(): void
    {
        $config = TestConfiguration::fromText(123, 2);

        $this->assertEquals(2, $config->testType);
    }

    public function testFromTextWithWordModeTestType(): void
    {
        $config = TestConfiguration::fromText(123, 5);

        $this->assertEquals(5, $config->testType);
        $this->assertTrue($config->wordMode);
    }

    // ===== fromWords Factory Tests =====

    public function testFromWordsCreatesConfig(): void
    {
        $wordIds = [10, 20, 30];
        $config = TestConfiguration::fromWords($wordIds);

        $this->assertEquals(TestConfiguration::KEY_WORDS, $config->testKey);
        $this->assertEquals([10, 20, 30], $config->selection);
        $this->assertEquals(1, $config->testType);
        $this->assertFalse($config->isTableMode);
    }

    public function testFromWordsConvertsToIntegers(): void
    {
        $wordIds = ['10', '20', '30'];
        $config = TestConfiguration::fromWords($wordIds);

        $this->assertEquals([10, 20, 30], $config->selection);
    }

    public function testFromWordsWithTestType(): void
    {
        $config = TestConfiguration::fromWords([1, 2], 3);

        $this->assertEquals(3, $config->testType);
    }

    // ===== fromTexts Factory Tests =====

    public function testFromTextsCreatesConfig(): void
    {
        $textIds = [100, 200, 300];
        $config = TestConfiguration::fromTexts($textIds);

        $this->assertEquals(TestConfiguration::KEY_TEXTS, $config->testKey);
        $this->assertEquals([100, 200, 300], $config->selection);
        $this->assertEquals(1, $config->testType);
    }

    public function testFromTextsConvertsToIntegers(): void
    {
        $textIds = ['100', '200'];
        $config = TestConfiguration::fromTexts($textIds);

        $this->assertEquals([100, 200], $config->selection);
    }

    // ===== forTableMode Factory Tests =====

    public function testForTableModeCreatesConfig(): void
    {
        $config = TestConfiguration::forTableMode('lang', 42);

        $this->assertEquals('lang', $config->testKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(1, $config->testType);
        $this->assertFalse($config->wordMode);
        $this->assertTrue($config->isTableMode);
    }

    public function testForTableModeWithArraySelection(): void
    {
        $config = TestConfiguration::forTableMode('words', [1, 2, 3]);

        $this->assertEquals([1, 2, 3], $config->selection);
        $this->assertTrue($config->isTableMode);
    }

    // ===== getBaseType Tests =====

    public function testGetBaseTypeForTypes1To3(): void
    {
        $config1 = TestConfiguration::fromLanguage(1, 1);
        $this->assertEquals(1, $config1->getBaseType());

        $config2 = TestConfiguration::fromLanguage(1, 2);
        $this->assertEquals(2, $config2->getBaseType());

        $config3 = TestConfiguration::fromLanguage(1, 3);
        $this->assertEquals(3, $config3->getBaseType());
    }

    public function testGetBaseTypeForWordModeTypes(): void
    {
        // Types 4 and 5 are word-mode versions of 1 and 2
        $config4 = TestConfiguration::fromLanguage(1, 4);
        $this->assertEquals(1, $config4->getBaseType());

        $config5 = TestConfiguration::fromLanguage(1, 5);
        $this->assertEquals(2, $config5->getBaseType());
    }

    // ===== toSqlProjection Tests =====

    public function testToSqlProjectionForLanguage(): void
    {
        $config = TestConfiguration::fromLanguage(42);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('WoLgID = 42', $sql);
    }

    public function testToSqlProjectionForText(): void
    {
        $config = TestConfiguration::fromText(123);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('textitems2', $sql);
        $this->assertStringContainsString('Ti2TxID = 123', $sql);
    }

    public function testToSqlProjectionForWords(): void
    {
        $config = TestConfiguration::fromWords([10, 20, 30]);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('WoID IN (10,20,30)', $sql);
    }

    public function testToSqlProjectionForTexts(): void
    {
        $config = TestConfiguration::fromTexts([100, 200]);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('textitems2', $sql);
        $this->assertStringContainsString('Ti2TxID IN (100,200)', $sql);
    }

    public function testToSqlProjectionForRawSql(): void
    {
        $rawSql = ' custom_table WHERE custom_condition ';
        $config = new TestConfiguration(TestConfiguration::KEY_RAW_SQL, $rawSql);
        $sql = $config->toSqlProjection();

        $this->assertEquals($rawSql, $sql);
    }

    public function testToSqlProjectionThrowsForInvalidKey(): void
    {
        $config = new TestConfiguration('invalid_key', 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid test key');

        $config->toSqlProjection();
    }

    // ===== getSelectionString Tests =====

    public function testGetSelectionStringForInteger(): void
    {
        $config = TestConfiguration::fromLanguage(42);
        $this->assertEquals('42', $config->getSelectionString());
    }

    public function testGetSelectionStringForArray(): void
    {
        $config = TestConfiguration::fromWords([10, 20, 30]);
        $this->assertEquals('10,20,30', $config->getSelectionString());
    }

    public function testGetSelectionStringForString(): void
    {
        $config = new TestConfiguration('raw_sql', 'custom sql');
        $this->assertEquals('custom sql', $config->getSelectionString());
    }

    // ===== toUrlProperty Tests =====

    public function testToUrlPropertyForLanguage(): void
    {
        $config = TestConfiguration::fromLanguage(42);
        $this->assertEquals('lang=42', $config->toUrlProperty());
    }

    public function testToUrlPropertyForText(): void
    {
        $config = TestConfiguration::fromText(123);
        $this->assertEquals('text=123', $config->toUrlProperty());
    }

    public function testToUrlPropertyForWords(): void
    {
        $config = TestConfiguration::fromWords([1, 2, 3]);
        $this->assertEquals('selection=2', $config->toUrlProperty());
    }

    public function testToUrlPropertyForTexts(): void
    {
        $config = TestConfiguration::fromTexts([100, 200]);
        $this->assertEquals('selection=3', $config->toUrlProperty());
    }

    public function testToUrlPropertyForUnknownKey(): void
    {
        $config = new TestConfiguration('unknown', 1);
        $this->assertEquals('', $config->toUrlProperty());
    }

    // ===== isValid Tests =====

    public function testIsValidReturnsTrueForValidConfig(): void
    {
        $config = TestConfiguration::fromLanguage(1);
        $this->assertTrue($config->isValid());
    }

    public function testIsValidReturnsFalseForEmptyKey(): void
    {
        $config = new TestConfiguration('', 1);
        $this->assertFalse($config->isValid());
    }

    // ===== Immutability Tests =====

    public function testConfigIsReadonly(): void
    {
        $config = TestConfiguration::fromLanguage(42, 2);

        // All properties are public readonly
        $this->assertEquals('lang', $config->testKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(2, $config->testType);
        $this->assertFalse($config->wordMode);
        $this->assertFalse($config->isTableMode);
    }

    // ===== Edge Cases Tests =====

    public function testFromWordsWithEmptyArray(): void
    {
        $config = TestConfiguration::fromWords([]);
        $this->assertEquals([], $config->selection);
    }

    public function testFromTextsWithSingleId(): void
    {
        $config = TestConfiguration::fromTexts([100]);
        $this->assertEquals([100], $config->selection);
    }

    public function testWordModeAutoEnabledForType4(): void
    {
        $config = TestConfiguration::fromLanguage(1, 4, false);
        // Word mode should be true because testType > 3
        $this->assertTrue($config->wordMode);
    }

    public function testWordModeAutoEnabledForType5(): void
    {
        $config = TestConfiguration::fromText(1, 5, false);
        // Word mode should be true because testType > 3
        $this->assertTrue($config->wordMode);
    }
}
