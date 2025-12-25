<?php declare(strict_types=1);
/**
 * Test Configuration Value Object
 *
 * Represents the configuration for a test/review session.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Domain
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Review\Domain;

/**
 * Value object representing test configuration parameters.
 *
 * Immutable after creation. Encapsulates test type, selection mode,
 * and other test parameters.
 *
 * @since 3.0.0
 */
final readonly class TestConfiguration
{
    /**
     * Test key types.
     */
    public const KEY_LANG = 'lang';
    public const KEY_TEXT = 'text';
    public const KEY_WORDS = 'words';
    public const KEY_TEXTS = 'texts';
    public const KEY_RAW_SQL = 'raw_sql';

    /**
     * Test types.
     */
    public const TYPE_TERM_TO_TRANSLATION = 1;
    public const TYPE_TRANSLATION_TO_TERM = 2;
    public const TYPE_SENTENCE_TO_TERM = 3;
    public const TYPE_TERM_TO_TRANSLATION_WORD = 4;
    public const TYPE_TRANSLATION_TO_TERM_WORD = 5;

    /**
     * Constructor.
     *
     * @param string          $testKey     Test key type (lang, text, words, texts, raw_sql)
     * @param int|int[]|string $selection   Selection value (ID, array of IDs, or SQL string)
     * @param int             $testType    Test type (1-5)
     * @param bool            $wordMode    Whether in word mode (no sentence)
     * @param bool            $isTableMode Whether in table test mode
     */
    public function __construct(
        public string $testKey,
        public int|array|string $selection,
        public int $testType = 1,
        public bool $wordMode = false,
        public bool $isTableMode = false
    ) {
    }

    /**
     * Create configuration for testing a language.
     *
     * @param int  $langId   Language ID
     * @param int  $testType Test type (1-5)
     * @param bool $wordMode Word mode flag
     *
     * @return self
     */
    public static function fromLanguage(int $langId, int $testType = 1, bool $wordMode = false): self
    {
        return new self(
            self::KEY_LANG,
            $langId,
            self::clampTestType($testType),
            $wordMode || $testType > 3,
            false
        );
    }

    /**
     * Create configuration for testing a text.
     *
     * @param int  $textId   Text ID
     * @param int  $testType Test type (1-5)
     * @param bool $wordMode Word mode flag
     *
     * @return self
     */
    public static function fromText(int $textId, int $testType = 1, bool $wordMode = false): self
    {
        return new self(
            self::KEY_TEXT,
            $textId,
            self::clampTestType($testType),
            $wordMode || $testType > 3,
            false
        );
    }

    /**
     * Create configuration for testing specific words.
     *
     * @param int[] $wordIds  Array of word IDs
     * @param int   $testType Test type (1-5)
     * @param bool  $wordMode Word mode flag
     *
     * @return self
     */
    public static function fromWords(array $wordIds, int $testType = 1, bool $wordMode = false): self
    {
        return new self(
            self::KEY_WORDS,
            array_map('intval', $wordIds),
            self::clampTestType($testType),
            $wordMode || $testType > 3,
            false
        );
    }

    /**
     * Create configuration for testing words from specific texts.
     *
     * @param int[] $textIds  Array of text IDs
     * @param int   $testType Test type (1-5)
     * @param bool  $wordMode Word mode flag
     *
     * @return self
     */
    public static function fromTexts(array $textIds, int $testType = 1, bool $wordMode = false): self
    {
        return new self(
            self::KEY_TEXTS,
            array_map('intval', $textIds),
            self::clampTestType($testType),
            $wordMode || $testType > 3,
            false
        );
    }

    /**
     * Create table mode configuration.
     *
     * @param string          $testKey   Test key type
     * @param int|int[]|string $selection Selection value
     *
     * @return self
     */
    public static function forTableMode(string $testKey, int|array|string $selection): self
    {
        return new self($testKey, $selection, 1, false, true);
    }

    /**
     * Get base test type (1-3, strips word mode offset).
     *
     * @return int Base test type
     */
    public function getBaseType(): int
    {
        return $this->testType > 3 ? $this->testType - 3 : $this->testType;
    }

    /**
     * Get SQL projection string for this configuration.
     *
     * @return string SQL fragment for FROM/WHERE clause
     *
     * @throws \InvalidArgumentException If test key is invalid
     */
    public function toSqlProjection(): string
    {
        $selectionInt = is_int($this->selection) ? $this->selection : (int) (is_array($this->selection) ? ($this->selection[0] ?? 0) : $this->selection);
        return match ($this->testKey) {
            self::KEY_LANG => " words WHERE WoLgID = {$selectionInt} ",
            self::KEY_TEXT => " words, textitems2
                WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = {$selectionInt} ",
            self::KEY_WORDS => " words WHERE WoID IN (" . implode(',', (array) $this->selection) . ") ",
            self::KEY_TEXTS => " words, textitems2
                WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ("
                . implode(',', (array) $this->selection) . ") ",
            self::KEY_RAW_SQL => is_string($this->selection) ? $this->selection : '',
            default => throw new \InvalidArgumentException("Invalid test key: {$this->testKey}")
        };
    }

    /**
     * Get selection as string for URL parameters.
     *
     * @return string Selection as comma-separated string
     */
    public function getSelectionString(): string
    {
        if (is_array($this->selection)) {
            return implode(',', $this->selection);
        }
        return (string) $this->selection;
    }

    /**
     * Get URL property string for this configuration.
     *
     * @return string URL property (e.g., "lang=1" or "text=42")
     */
    public function toUrlProperty(): string
    {
        $selectionStr = $this->getSelectionString();
        return match ($this->testKey) {
            self::KEY_LANG => "lang={$selectionStr}",
            self::KEY_TEXT => "text={$selectionStr}",
            self::KEY_WORDS => "selection=2",
            self::KEY_TEXTS => "selection=3",
            default => ''
        };
    }

    /**
     * Check if configuration is valid (has a test key).
     *
     * @return bool True if valid
     */
    public function isValid(): bool
    {
        return $this->testKey !== '';
    }

    /**
     * Clamp test type to valid range.
     *
     * @param int $testType Raw test type
     *
     * @return int Clamped to 1-5
     */
    private static function clampTestType(int $testType): int
    {
        return max(1, min(5, $testType));
    }
}
