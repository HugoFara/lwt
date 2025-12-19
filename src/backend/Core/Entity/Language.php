<?php declare(strict_types=1);
/**
 * Language Entity
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Entity
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-classes-language.html
 * @since    2.7.0
 */

namespace Lwt\Core\Entity;

use InvalidArgumentException;
use Lwt\Core\Entity\ValueObject\LanguageId;

/**
 * A language represented as a rich domain object.
 *
 * Languages define how texts are parsed (word boundaries, sentence splitting),
 * dictionary URLs for lookups, and display settings (RTL, text size).
 *
 * This class enforces domain invariants and encapsulates business logic.
 *
 * @since 2.10.0-fork Get new ttsvoiceapi, showromanization properties
 * @since 3.0.0 Refactored to rich domain model
 */
class Language
{
    private LanguageId $id;
    private string $name;
    private string $dict1Uri;
    private string $dict2Uri;
    private string $translatorUri;
    private string $exportTemplate;
    private int $textSize;
    private string $characterSubstitutions;
    private string $regexpSplitSentences;
    private string $exceptionsSplitSentences;
    private string $regexpWordCharacters;
    private bool $removeSpaces;
    private bool $splitEachChar;
    private bool $rightToLeft;
    private string $ttsVoiceApi;
    private bool $showRomanization;

    /**
     * Private constructor - use factory methods instead.
     */
    private function __construct(
        LanguageId $id,
        string $name,
        string $dict1Uri,
        string $dict2Uri,
        string $translatorUri,
        string $exportTemplate,
        int $textSize,
        string $characterSubstitutions,
        string $regexpSplitSentences,
        string $exceptionsSplitSentences,
        string $regexpWordCharacters,
        bool $removeSpaces,
        bool $splitEachChar,
        bool $rightToLeft,
        string $ttsVoiceApi,
        bool $showRomanization
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->dict1Uri = $dict1Uri;
        $this->dict2Uri = $dict2Uri;
        $this->translatorUri = $translatorUri;
        $this->exportTemplate = $exportTemplate;
        $this->textSize = $textSize;
        $this->characterSubstitutions = $characterSubstitutions;
        $this->regexpSplitSentences = $regexpSplitSentences;
        $this->exceptionsSplitSentences = $exceptionsSplitSentences;
        $this->regexpWordCharacters = $regexpWordCharacters;
        $this->removeSpaces = $removeSpaces;
        $this->splitEachChar = $splitEachChar;
        $this->rightToLeft = $rightToLeft;
        $this->ttsVoiceApi = $ttsVoiceApi;
        $this->showRomanization = $showRomanization;
    }

    /**
     * Create a new language with required settings.
     *
     * @param string $name                    Language name
     * @param string $dict1Uri                Primary dictionary URL (### is replaced with word)
     * @param string $regexpSplitSentences    Regex for sentence splitting
     * @param string $regexpWordCharacters    Regex for word characters
     *
     * @return self
     *
     * @throws InvalidArgumentException If name is empty
     */
    public static function create(
        string $name,
        string $dict1Uri,
        string $regexpSplitSentences,
        string $regexpWordCharacters
    ): self {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new InvalidArgumentException('Language name cannot be empty');
        }

        return new self(
            LanguageId::new(),
            $trimmedName,
            trim($dict1Uri),
            '',
            '',
            '',
            100,
            '',
            trim($regexpSplitSentences),
            '',
            trim($regexpWordCharacters),
            false,
            false,
            false,
            '',
            true
        );
    }

    /**
     * Reconstitute a language from persistence.
     *
     * @param int    $id                        The language ID
     * @param string $name                      Language name
     * @param string $dict1Uri                  Primary dictionary URI
     * @param string $dict2Uri                  Secondary dictionary URI
     * @param string $translatorUri             Translator URI
     * @param string $exportTemplate            Export template
     * @param int    $textSize                  Text size percentage
     * @param string $characterSubstitutions    Character substitutions
     * @param string $regexpSplitSentences      Sentence split regex
     * @param string $exceptionsSplitSentences  Split exceptions
     * @param string $regexpWordCharacters      Word character regex
     * @param bool   $removeSpaces              Remove spaces flag
     * @param bool   $splitEachChar             Split each character flag
     * @param bool   $rightToLeft               Right-to-left flag
     * @param string $ttsVoiceApi               TTS API URL
     * @param bool   $showRomanization          Show romanization flag
     *
     * @return self
     *
     * @internal This method is for repository use only
     */
    public static function reconstitute(
        int $id,
        string $name,
        string $dict1Uri,
        string $dict2Uri,
        string $translatorUri,
        string $exportTemplate,
        int $textSize,
        string $characterSubstitutions,
        string $regexpSplitSentences,
        string $exceptionsSplitSentences,
        string $regexpWordCharacters,
        bool $removeSpaces,
        bool $splitEachChar,
        bool $rightToLeft,
        string $ttsVoiceApi,
        bool $showRomanization
    ): self {
        return new self(
            LanguageId::fromInt($id),
            $name,
            $dict1Uri,
            $dict2Uri,
            $translatorUri,
            $exportTemplate,
            $textSize,
            $characterSubstitutions,
            $regexpSplitSentences,
            $exceptionsSplitSentences,
            $regexpWordCharacters,
            $removeSpaces,
            $splitEachChar,
            $rightToLeft,
            $ttsVoiceApi,
            $showRomanization
        );
    }

    // Domain behavior methods

    /**
     * Update the language name.
     *
     * @param string $name The new name
     *
     * @return void
     *
     * @throws InvalidArgumentException If name is empty
     */
    public function rename(string $name): void
    {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new InvalidArgumentException('Language name cannot be empty');
        }
        $this->name = $trimmedName;
    }

    /**
     * Configure dictionaries.
     *
     * @param string $primary   Primary dictionary URL
     * @param string $secondary Secondary dictionary URL
     *
     * @return void
     */
    public function configureDictionaries(string $primary, string $secondary = ''): void
    {
        $this->dict1Uri = trim($primary);
        $this->dict2Uri = trim($secondary);
    }

    /**
     * Configure the translator.
     *
     * @param string $translatorUri Translator URL
     *
     * @return void
     */
    public function configureTranslator(string $translatorUri): void
    {
        $this->translatorUri = trim($translatorUri);
    }

    /**
     * Configure text parsing rules.
     *
     * @param string $sentenceSplitRegex  Regex for splitting sentences
     * @param string $sentenceExceptions  Exceptions for sentence splitting
     * @param string $wordCharRegex       Regex for word characters
     * @param string $charSubstitutions   Character substitutions
     *
     * @return void
     */
    public function configureTextParsing(
        string $sentenceSplitRegex,
        string $sentenceExceptions,
        string $wordCharRegex,
        string $charSubstitutions = ''
    ): void {
        $this->regexpSplitSentences = trim($sentenceSplitRegex);
        $this->exceptionsSplitSentences = trim($sentenceExceptions);
        $this->regexpWordCharacters = trim($wordCharRegex);
        $this->characterSubstitutions = $charSubstitutions;
    }

    /**
     * Configure CJK-style language settings.
     *
     * For languages like Chinese/Japanese that don't use spaces between words.
     *
     * @param bool $removeSpaces   Whether to remove spaces
     * @param bool $splitEachChar  Whether to split each character
     *
     * @return void
     */
    public function configureCjkMode(bool $removeSpaces, bool $splitEachChar): void
    {
        $this->removeSpaces = $removeSpaces;
        $this->splitEachChar = $splitEachChar;
    }

    /**
     * Set right-to-left display mode.
     *
     * @param bool $rtl Whether the language is right-to-left
     *
     * @return void
     */
    public function setRightToLeft(bool $rtl): void
    {
        $this->rightToLeft = $rtl;
    }

    /**
     * Configure text display size.
     *
     * @param int $percentage Text size percentage (typically 50-200)
     *
     * @return void
     *
     * @throws InvalidArgumentException If percentage is invalid
     */
    public function setTextSize(int $percentage): void
    {
        if ($percentage < 50 || $percentage > 300) {
            throw new InvalidArgumentException('Text size must be between 50 and 300 percent');
        }
        $this->textSize = $percentage;
    }

    /**
     * Configure export template.
     *
     * @param string $template The export template
     *
     * @return void
     */
    public function setExportTemplate(string $template): void
    {
        $this->exportTemplate = trim($template);
    }

    /**
     * Configure TTS (text-to-speech) API.
     *
     * @param string $apiUrl TTS API URL
     *
     * @return void
     */
    public function configureTts(string $apiUrl): void
    {
        $this->ttsVoiceApi = trim($apiUrl);
    }

    /**
     * Set whether to show romanization.
     *
     * @param bool $show Whether to show romanization
     *
     * @return void
     */
    public function setShowRomanization(bool $show): void
    {
        $this->showRomanization = $show;
    }

    // Query methods

    /**
     * Check if this is a CJK-style language (no spaces between words).
     *
     * @return bool
     */
    public function isCjkStyle(): bool
    {
        return $this->removeSpaces || $this->splitEachChar;
    }

    /**
     * Check if the language uses MeCab for parsing.
     *
     * @return bool
     */
    public function usesMecab(): bool
    {
        return $this->regexpWordCharacters === 'mecab';
    }

    /**
     * Check if the language has a translator configured.
     *
     * @return bool
     */
    public function hasTranslator(): bool
    {
        return $this->translatorUri !== '';
    }

    /**
     * Check if the language has a secondary dictionary.
     *
     * @return bool
     */
    public function hasSecondaryDictionary(): bool
    {
        return $this->dict2Uri !== '';
    }

    /**
     * Check if the language has an export template.
     *
     * @return bool
     */
    public function hasExportTemplate(): bool
    {
        return $this->exportTemplate !== '';
    }

    /**
     * Check if TTS is configured.
     *
     * @return bool
     */
    public function hasTts(): bool
    {
        return $this->ttsVoiceApi !== '';
    }

    /**
     * Get dictionary URL for a word.
     *
     * @param string $word     The word to look up
     * @param int    $dictNum  Which dictionary (1 or 2)
     *
     * @return string The URL with word substituted
     */
    public function getDictionaryUrl(string $word, int $dictNum = 1): string
    {
        $uri = $dictNum === 2 ? $this->dict2Uri : $this->dict1Uri;
        return str_replace('###', urlencode($word), $uri);
    }

    /**
     * Get translator URL for a word.
     *
     * @param string $word The word to translate
     *
     * @return string The URL with word substituted
     */
    public function getTranslatorUrl(string $word): string
    {
        return str_replace('###', urlencode($word), $this->translatorUri);
    }

    /**
     * Get RTL direction attribute for HTML.
     *
     * @return string ' dir="rtl" ' or empty string
     */
    public function getDirectionAttribute(): string
    {
        return $this->rightToLeft ? ' dir="rtl" ' : '';
    }

    /**
     * Export word data as a JSON dictionary for JavaScript.
     *
     * @return string|false JSON dictionary or false on error
     */
    public function exportJsDict(): string|false
    {
        return json_encode([
            'lgid'               => $this->id->toInt(),
            'dict1uri'           => $this->dict1Uri,
            'dict2uri'           => $this->dict2Uri,
            'translator'         => $this->translatorUri,
            'exporttemplate'     => $this->exportTemplate,
            'textsize'           => $this->textSize,
            'charactersubst'     => $this->characterSubstitutions,
            'regexpsplitsent'    => $this->regexpSplitSentences,
            'exceptionsplitsent' => $this->exceptionsSplitSentences,
            'regexpwordchar'     => $this->regexpWordCharacters,
            'removespaces'       => $this->removeSpaces,
            'spliteachchar'      => $this->splitEachChar,
            'rightoleft'         => $this->rightToLeft,
            'ttsvoiceapi'        => $this->ttsVoiceApi,
            'showromanization'   => $this->showRomanization,
        ]);
    }

    // Getters

    public function id(): LanguageId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function dict1Uri(): string
    {
        return $this->dict1Uri;
    }

    public function dict2Uri(): string
    {
        return $this->dict2Uri;
    }

    public function translatorUri(): string
    {
        return $this->translatorUri;
    }

    public function exportTemplate(): string
    {
        return $this->exportTemplate;
    }

    public function textSize(): int
    {
        return $this->textSize;
    }

    public function characterSubstitutions(): string
    {
        return $this->characterSubstitutions;
    }

    public function regexpSplitSentences(): string
    {
        return $this->regexpSplitSentences;
    }

    public function exceptionsSplitSentences(): string
    {
        return $this->exceptionsSplitSentences;
    }

    public function regexpWordCharacters(): string
    {
        return $this->regexpWordCharacters;
    }

    public function removeSpaces(): bool
    {
        return $this->removeSpaces;
    }

    public function splitEachChar(): bool
    {
        return $this->splitEachChar;
    }

    public function rightToLeft(): bool
    {
        return $this->rightToLeft;
    }

    public function ttsVoiceApi(): string
    {
        return $this->ttsVoiceApi;
    }

    public function showRomanization(): bool
    {
        return $this->showRomanization;
    }

    /**
     * Internal method to set the ID after persistence.
     *
     * @param LanguageId $id The new ID
     *
     * @return void
     *
     * @internal This method is for repository use only
     */
    public function setId(LanguageId $id): void
    {
        if (!$this->id->isNew()) {
            throw new \LogicException('Cannot change ID of a persisted language');
        }
        $this->id = $id;
    }
}
