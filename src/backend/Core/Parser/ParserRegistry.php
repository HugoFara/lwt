<?php declare(strict_types=1);
/**
 * Parser Registry
 *
 * PHP version 8.1
 *
 * @category Parser
 * @package  Lwt\Core\Parser
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Parser;

use Lwt\Core\Entity\Language;
use Lwt\Core\Parser\Parsers\RegexParser;
use Lwt\Core\Parser\Parsers\CharacterParser;
use Lwt\Core\Parser\Parsers\MecabParser;

/**
 * Registry for parser implementations.
 *
 * Handles parser discovery, registration, and instantiation.
 * Provides methods to select the appropriate parser for a language.
 *
 * @since 3.0.0
 */
class ParserRegistry
{
    /** @var array<string, ParserInterface> Registered parsers by type */
    private array $parsers = [];

    /** @var string Default parser type when none specified */
    private const DEFAULT_PARSER = 'regex';

    /**
     * Create a new parser registry with default parsers.
     */
    public function __construct()
    {
        $this->registerDefaultParsers();
    }

    /**
     * Register the default built-in parsers.
     *
     * @return void
     */
    private function registerDefaultParsers(): void
    {
        $this->register(new RegexParser());
        $this->register(new CharacterParser());
        $this->register(new MecabParser());
    }

    /**
     * Register a parser.
     *
     * @param ParserInterface $parser Parser to register
     *
     * @return void
     */
    public function register(ParserInterface $parser): void
    {
        $this->parsers[$parser->getType()] = $parser;
    }

    /**
     * Get a parser by type.
     *
     * @param string $type Parser type identifier
     *
     * @return ParserInterface|null Parser instance or null if not found
     */
    public function get(string $type): ?ParserInterface
    {
        return $this->parsers[$type] ?? null;
    }

    /**
     * Check if a parser type is registered.
     *
     * @param string $type Parser type identifier
     *
     * @return bool True if parser is registered
     */
    public function has(string $type): bool
    {
        return isset($this->parsers[$type]);
    }

    /**
     * Get all registered parsers.
     *
     * @return array<string, ParserInterface> All parsers indexed by type
     */
    public function getAll(): array
    {
        return $this->parsers;
    }

    /**
     * Get all available parsers (those that can run on this system).
     *
     * @return array<string, ParserInterface> Available parsers indexed by type
     */
    public function getAvailable(): array
    {
        return array_filter(
            $this->parsers,
            fn(ParserInterface $parser) => $parser->isAvailable()
        );
    }

    /**
     * Get parser information for UI display.
     *
     * @return array<string, array{type: string, name: string, available: bool, message: string}>
     */
    public function getParserInfo(): array
    {
        $info = [];
        foreach ($this->parsers as $type => $parser) {
            $info[$type] = [
                'type' => $parser->getType(),
                'name' => $parser->getName(),
                'available' => $parser->isAvailable(),
                'message' => $parser->getAvailabilityMessage(),
            ];
        }
        return $info;
    }

    /**
     * Get the default parser type.
     *
     * @return string Default parser type
     */
    public function getDefaultType(): string
    {
        return self::DEFAULT_PARSER;
    }

    /**
     * Resolve the parser type for a language.
     *
     * Determines which parser to use based on language settings.
     * Supports both explicit parser type and legacy detection.
     *
     * @param Language $language Language entity
     *
     * @return string Resolved parser type
     */
    public function resolveParserType(Language $language): string
    {
        // Check explicit parser type first (new field)
        $parserType = $language->parserType();
        if ($parserType !== null && $parserType !== '') {
            return $parserType;
        }

        // Legacy detection: check magic word in regexpWordCharacters
        $wordChars = strtoupper(trim($language->regexpWordCharacters()));
        if ($wordChars === 'MECAB') {
            return 'mecab';
        }

        // Legacy detection: check splitEachChar flag
        if ($language->splitEachChar()) {
            return 'character';
        }

        return self::DEFAULT_PARSER;
    }

    /**
     * Resolve parser type from a database row.
     *
     * @param array $row Database row with Lg* prefixed columns
     *
     * @return string Resolved parser type
     */
    public function resolveParserTypeFromRow(array $row): string
    {
        // Check explicit parser type first
        $parserType = $row['LgParserType'] ?? null;
        if ($parserType !== null && $parserType !== '') {
            return $parserType;
        }

        // Legacy detection: check magic word
        $wordChars = strtoupper(trim($row['LgRegexpWordCharacters'] ?? ''));
        if ($wordChars === 'MECAB') {
            return 'mecab';
        }

        // Legacy detection: check splitEachChar flag
        if (!empty($row['LgSplitEachChar'])) {
            return 'character';
        }

        return self::DEFAULT_PARSER;
    }

    /**
     * Get a parser for a language, with fallback to default.
     *
     * @param Language $language Language entity
     *
     * @return ParserInterface Parser instance (never null)
     */
    public function getParserForLanguage(Language $language): ParserInterface
    {
        $type = $this->resolveParserType($language);
        $parser = $this->get($type);

        // Check availability and fall back if needed
        if ($parser === null || !$parser->isAvailable()) {
            $parser = $this->get(self::DEFAULT_PARSER);
        }

        // This should never happen, but ensure we always return a parser
        if ($parser === null) {
            throw new \RuntimeException('No parsers registered in ParserRegistry');
        }

        return $parser;
    }
}
