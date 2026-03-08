<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services;

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

/**
 * Enriches imported vocabulary with translations from kaikki.org
 * (Wiktextract structured data) or monolingual definitions from
 * Wiktionary APIs.
 *
 * Designed to be called in small batches via AJAX polling, so the
 * UI can show progress without blocking.
 */
class WiktionaryEnrichmentService
{
    private const KAIKKI_BASE_URL = 'https://kaikki.org/dictionary';

    private const WIKTIONARY_API_TEMPLATE = 'https://%s.wiktionary.org/w/api.php';

    private const BATCH_SIZE = 20;

    private const FETCH_TIMEOUT = 10;

    private const MAX_CONSECUTIVE_FAILURES = 5;

    /**
     * Get the next batch of unenriched words for a language.
     *
     * @return list<array{WoID: int, WoText: string}>
     */
    public function getUnenrichedWords(int $langId, int $batchSize = self::BATCH_SIZE): array
    {
        $userScope = UserScopedQuery::forTable('words');
        $sql = "SELECT WoID, WoText FROM words
                WHERE WoLgID = ?
                AND (WoTranslation IS NULL OR WoTranslation = '' OR WoTranslation = '*')
                $userScope
                ORDER BY WoID ASC
                LIMIT ?";

        /** @var list<array{WoID: int, WoText: string}> */
        return Connection::preparedFetchAll($sql, [$langId, $batchSize]);
    }

    /**
     * Count remaining unenriched words for progress tracking.
     */
    public function countUnenriched(int $langId): int
    {
        $userScope = UserScopedQuery::forTable('words');
        $sql = "SELECT COUNT(*) as value FROM words
                WHERE WoLgID = ?
                AND (WoTranslation IS NULL OR WoTranslation = '' OR WoTranslation = '*')
                $userScope";

        /** @var int|string|null $result */
        $result = Connection::preparedFetchValue($sql, [$langId]);
        return (int) $result;
    }

    /**
     * Count total words for a language (for progress calculation).
     */
    public function countTotal(int $langId): int
    {
        $userScope = UserScopedQuery::forTable('words');
        $sql = "SELECT COUNT(*) as value FROM words
                WHERE WoLgID = ? $userScope";

        /** @var int|string|null $result */
        $result = Connection::preparedFetchValue($sql, [$langId]);
        return (int) $result;
    }

    /**
     * Enrich a batch of words with English translations from kaikki.org.
     *
     * @return array{enriched: int, failed: int, remaining: int, total: int, warning: string}
     */
    public function enrichBatchTranslation(int $langId, string $languageName): array
    {
        $kaikkiName = FrequencyLanguageMap::getKaikkiLanguageName($languageName);
        if ($kaikkiName === null) {
            return [
                'enriched' => 0,
                'failed' => 0,
                'remaining' => 0,
                'total' => 0,
                'warning' => "Language $languageName not supported for enrichment.",
            ];
        }

        $words = $this->getUnenrichedWords($langId);
        if (empty($words)) {
            return [
                'enriched' => 0,
                'failed' => 0,
                'remaining' => 0,
                'total' => $this->countTotal($langId),
                'warning' => '',
            ];
        }

        $enriched = 0;
        $failed = 0;
        $consecutiveFailures = 0;
        $warning = '';

        foreach ($words as $word) {
            $translation = $this->fetchKaikkiTranslation($word['WoText'], $kaikkiName);

            if ($translation !== null) {
                $this->updateTranslation($word['WoID'], $translation);
                $enriched++;
                $consecutiveFailures = 0;
            } else {
                $failed++;
                $consecutiveFailures++;
            }

            if ($consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
                $warning = 'Multiple consecutive lookups failed. '
                    . 'The dictionary service may be unavailable.';
                break;
            }
        }

        return [
            'enriched' => $enriched,
            'failed' => $failed,
            'remaining' => $this->countUnenriched($langId),
            'total' => $this->countTotal($langId),
            'warning' => $warning,
        ];
    }

    /**
     * Enrich a batch of words with monolingual definitions from Wiktionary.
     *
     * @return array{enriched: int, failed: int, remaining: int, total: int, warning: string}
     */
    public function enrichBatchDefinition(int $langId, string $languageName): array
    {
        $wiktCode = FrequencyLanguageMap::getWiktionaryCode($languageName);
        $kaikkiName = FrequencyLanguageMap::getKaikkiLanguageName($languageName);
        if ($wiktCode === null || $kaikkiName === null) {
            return [
                'enriched' => 0,
                'failed' => 0,
                'remaining' => 0,
                'total' => 0,
                'warning' => "Language $languageName not supported for enrichment.",
            ];
        }

        $words = $this->getUnenrichedWords($langId);
        if (empty($words)) {
            return [
                'enriched' => 0,
                'failed' => 0,
                'remaining' => 0,
                'total' => $this->countTotal($langId),
                'warning' => '',
            ];
        }

        $enriched = 0;
        $failed = 0;
        $consecutiveFailures = 0;
        $warning = '';

        foreach ($words as $word) {
            $definition = $this->fetchWiktionaryDefinition(
                $word['WoText'],
                $wiktCode,
                $kaikkiName
            );

            if ($definition !== null) {
                $this->updateTranslation($word['WoID'], $definition);
                $enriched++;
                $consecutiveFailures = 0;
            } else {
                $failed++;
                $consecutiveFailures++;
            }

            if ($consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
                $warning = 'Multiple consecutive lookups failed. '
                    . 'The dictionary service may be unavailable.';
                break;
            }
        }

        return [
            'enriched' => $enriched,
            'failed' => $failed,
            'remaining' => $this->countUnenriched($langId),
            'total' => $this->countTotal($langId),
            'warning' => $warning,
        ];
    }

    /**
     * Fetch English translation from kaikki.org for a single word.
     *
     * @return string|null First English gloss, or null on failure
     */
    public function fetchKaikkiTranslation(string $word, string $kaikkiLangName): ?string
    {
        $url = $this->buildKaikkiUrl($word, $kaikkiLangName);
        $content = $this->httpGet($url);
        if ($content === null) {
            return null;
        }

        return $this->parseKaikkiResponse($content);
    }

    /**
     * Fetch monolingual definition from Wiktionary API.
     *
     * Strategy: first try kaikki.org for the raw_glosses/glosses in the
     * target language. If that fails, fall back to the Wiktionary parse API
     * and extract the first definition line from wikitext.
     *
     * @return string|null First definition, or null on failure
     */
    public function fetchWiktionaryDefinition(
        string $word,
        string $wiktCode,
        string $kaikkiLangName
    ): ?string {
        // Try Wiktionary parse API for monolingual definition
        $definition = $this->fetchFromWiktionaryApi($word, $wiktCode);
        if ($definition !== null) {
            return $definition;
        }

        // Fallback: use kaikki.org English gloss
        return $this->fetchKaikkiTranslation($word, $kaikkiLangName);
    }

    /**
     * Build the kaikki.org URL for a word.
     *
     * Path format: /dictionary/{Language}/meaning/{w[0]}/{w[0:2]}/{word}.jsonl
     */
    public function buildKaikkiUrl(string $word, string $kaikkiLangName): string
    {
        $encoded = rawurlencode($word);
        $firstChar = mb_substr($word, 0, 1, 'UTF-8');
        $firstTwo = mb_strlen($word, 'UTF-8') >= 2
            ? mb_substr($word, 0, 2, 'UTF-8')
            : $firstChar;

        $encodedLang = rawurlencode($kaikkiLangName);
        $encodedFirst = rawurlencode($firstChar);
        $encodedFirstTwo = rawurlencode($firstTwo);

        return self::KAIKKI_BASE_URL
            . "/$encodedLang/meaning/$encodedFirst/$encodedFirstTwo/$encoded.jsonl";
    }

    /**
     * Parse kaikki.org JSONL response to extract the first English gloss.
     *
     * Prefers non-form-of entries (lexical definitions over inflection forms).
     *
     * @return string|null First gloss or null
     */
    public function parseKaikkiResponse(string $jsonl): ?string
    {
        $lines = explode("\n", trim($jsonl));

        // First pass: look for non-form-of entries
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            /** @var mixed $entry */
            $entry = json_decode($line, true);
            if (!is_array($entry) || !isset($entry['senses'])) {
                continue;
            }

            foreach ($entry['senses'] as $sense) {
                /** @var mixed $sense */
                if (!is_array($sense)) {
                    continue;
                }

                // Skip form-of senses (inflections like "third-person singular of...")
                if (isset($sense['form_of']) || isset($sense['tags']) && is_array($sense['tags']) && in_array('form-of', $sense['tags'], true)) {
                    continue;
                }

                if (isset($sense['glosses']) && is_array($sense['glosses']) && !empty($sense['glosses'])) {
                    $gloss = (string) $sense['glosses'][0];
                    if ($gloss !== '') {
                        return $gloss;
                    }
                }
            }
        }

        // Second pass: accept any gloss (including form-of)
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            /** @var mixed $entry */
            $entry = json_decode($line, true);
            if (!is_array($entry) || !isset($entry['senses'])) {
                continue;
            }

            /** @var mixed $sense */
            foreach ($entry['senses'] as $sense) {
                if (!is_array($sense) || !isset($sense['glosses']) || !is_array($sense['glosses'])) {
                    continue;
                }
                $gloss = (string) ($sense['glosses'][0] ?? '');
                if ($gloss !== '') {
                    return $gloss;
                }
            }
        }

        return null;
    }

    /**
     * Fetch a definition from the Wiktionary parse API.
     *
     * Uses {lang}.wiktionary.org to get a monolingual definition.
     *
     * @return string|null First definition line or null
     */
    private function fetchFromWiktionaryApi(
        string $word,
        string $wiktCode
    ): ?string {
        // Build API URL: get sections first to find the right language section
        $baseUrl = sprintf(self::WIKTIONARY_API_TEMPLATE, rawurlencode($wiktCode));
        $url = $baseUrl . '?' . http_build_query([
            'action' => 'parse',
            'page' => $word,
            'prop' => 'wikitext',
            'format' => 'json',
            'section' => 1,
            'redirects' => 1,
        ]);

        $content = $this->httpGet($url);
        if ($content === null) {
            return null;
        }

        /** @var mixed $data */
        $data = json_decode($content, true);
        if (!is_array($data)
            || !isset($data['parse'])
            || !is_array($data['parse'])
            || !isset($data['parse']['wikitext'])
            || !is_array($data['parse']['wikitext'])
            || !isset($data['parse']['wikitext']['*'])
        ) {
            return null;
        }

        $wikitext = (string) $data['parse']['wikitext']['*'];
        return $this->parseWikitext($wikitext);
    }

    /**
     * Parse wikitext to extract the first definition line.
     *
     * Wikitext definitions look like:
     *   # [[house]]
     *   # {{lb|es|architecture}} [[building]]
     *
     * @return string|null Cleaned definition or null
     */
    public function parseWikitext(string $wikitext): ?string
    {
        $lines = explode("\n", $wikitext);

        foreach ($lines as $line) {
            $line = trim($line);

            // Definition lines start with "# " (not "## " which is subdefinitions)
            if (!preg_match('/^#\s+(.+)$/', $line, $matches)) {
                continue;
            }

            $definition = $matches[1];

            // Skip lines that are just cross-references
            if (preg_match('/^\{\{(inflection|form) of\b/i', $definition)) {
                continue;
            }

            $definition = $this->cleanWikitext($definition);

            if ($definition !== '') {
                return $definition;
            }
        }

        return null;
    }

    /**
     * Clean wikitext markup to produce readable text.
     */
    private function cleanWikitext(string $text): string
    {
        // Remove template calls like {{lb|es|...}} but keep simple ones
        // Replace {{l|lang|word}} and {{m|lang|word}} with just word
        $text = preg_replace('/\{\{[lm]\|[^|]+\|([^}|]+)[^}]*\}\}/', '$1', $text) ?? $text;

        // Remove label templates {{lb|...}}, {{label|...}}, {{context|...}}
        $text = preg_replace('/\{\{(?:lb|label|context|lbl)\|[^}]*\}\}\s*/', '', $text) ?? $text;

        // Remove gloss templates {{gloss|text}} → text
        $text = preg_replace('/\{\{gloss\|([^}]+)\}\}/', '$1', $text) ?? $text;

        // Remove remaining templates (keep the first parameter if any)
        $text = preg_replace('/\{\{[^|{}]+\|([^|{}]+)(?:\|[^{}]*)?\}\}/', '$1', $text) ?? $text;
        $text = preg_replace('/\{\{[^}]*\}\}/', '', $text) ?? $text;

        // Convert [[word|display]] → display, [[word]] → word
        $text = preg_replace('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/', '$1', $text) ?? $text;

        // Remove bold/italic markup
        $text = str_replace(["'''", "''"], '', $text);

        // Clean up extra spaces
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Update a word's translation in the database.
     */
    private function updateTranslation(int $wordId, string $translation): void
    {
        Connection::preparedExecute(
            "UPDATE words SET WoTranslation = ? WHERE WoID = ?",
            [$translation, $wordId]
        );
    }

    /**
     * Perform an HTTP GET with timeout.
     *
     * @return string|null Response body or null on failure
     */
    private function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => self::FETCH_TIMEOUT,
                'user_agent' => 'LWT/3.0 (Learning with Texts)',
                'ignore_errors' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return null;
        }

        // Check for HTTP errors via response headers
        // @psalm-suppress RedundantCondition — $http_response_header is set by file_get_contents
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('/\b(4\d{2}|5\d{2})\b/', $statusLine)) {
                return null;
            }
        }

        return $content;
    }
}
