<?php declare(strict_types=1);
/**
 * Term Translation API Handler
 *
 * Handles API operations for term translations, dictionary lookups, and tags.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Modules\Tags\Application\TagsFacade;

/**
 * Handler for term translation, dictionary, and tag API operations.
 *
 * Provides endpoints for:
 * - Getting similar terms for autocomplete
 * - Dictionary link generation
 * - Term tag management
 * - Translation creation and updates
 *
 * @since 3.0.0
 */
class TermTranslationApiHandler
{
    private FindSimilarTerms $findSimilarTerms;
    private DictionaryAdapter $dictionaryAdapter;

    /**
     * Constructor.
     *
     * @param FindSimilarTerms|null  $findSimilarTerms  Find similar terms use case
     * @param DictionaryAdapter|null $dictionaryAdapter Dictionary adapter
     */
    public function __construct(
        ?FindSimilarTerms $findSimilarTerms = null,
        ?DictionaryAdapter $dictionaryAdapter = null
    ) {
        $this->findSimilarTerms = $findSimilarTerms ?? new FindSimilarTerms();
        $this->dictionaryAdapter = $dictionaryAdapter ?? new DictionaryAdapter();
    }

    // =========================================================================
    // Similar Terms
    // =========================================================================

    /**
     * Get similar terms.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array{similar_terms: string}
     */
    public function getSimilarTerms(int $langId, string $term): array
    {
        return [
            'similar_terms' => $this->findSimilarTerms->getFormattedTerms($langId, $term)
        ];
    }

    /**
     * Format response for similar terms.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array
     */
    public function formatSimilarTerms(int $langId, string $term): array
    {
        return $this->getSimilarTerms($langId, $term);
    }

    // =========================================================================
    // Dictionary
    // =========================================================================

    /**
     * Get dictionary links for a term.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array Dictionary URLs
     */
    public function getDictionaryLinks(int $langId, string $term): array
    {
        $dicts = $this->dictionaryAdapter->getLanguageDictionaries($langId);

        return [
            'dict1' => $dicts['dict1'] !== ''
                ? DictionaryAdapter::createDictLink($dicts['dict1'], $term)
                : '',
            'dict2' => $dicts['dict2'] !== ''
                ? DictionaryAdapter::createDictLink($dicts['dict2'], $term)
                : '',
            'translator' => $dicts['translator'] !== ''
                ? DictionaryAdapter::createDictLink($dicts['translator'], $term)
                : '',
        ];
    }

    /**
     * Format response for dictionary links.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array
     */
    public function formatDictionaryLinks(int $langId, string $term): array
    {
        return $this->getDictionaryLinks($langId, $term);
    }

    // =========================================================================
    // Tags
    // =========================================================================

    /**
     * Get tags for a term.
     *
     * @param int $termId Term ID
     *
     * @return array{tags: string[]}
     */
    public function getTermTags(int $termId): array
    {
        $tagsResult = QueryBuilder::table('wordtags')
            ->select(['tags.TgText'])
            ->join('tags', 'tags.TgID', '=', 'wordtags.WtTgID')
            ->where('wordtags.WtWoID', '=', $termId)
            ->orderBy('tags.TgText')
            ->getPrepared();

        $tags = array_map(fn($row) => (string)$row['TgText'], $tagsResult);
        return ['tags' => $tags];
    }

    /**
     * Set tags for a term.
     *
     * @param int      $termId Term ID
     * @param string[] $tags   Tag names
     *
     * @return array{success: bool}
     */
    public function setTermTags(int $termId, array $tags): array
    {
        TagsFacade::saveWordTagsFromArray($termId, $tags);
        return ['success' => true];
    }

    // =========================================================================
    // Translation Management
    // =========================================================================

    /**
     * Add the translation for a new term.
     *
     * @param string $text Associated text
     * @param int    $lang Language ID
     * @param string $data Translation
     *
     * @return array{0: int|string, 1: string}|string [new word ID, lowercase $text] if success, error message otherwise
     */
    public function addNewTermTranslation(string $text, int $lang, string $data): array|string
    {
        $textlc = mb_strtolower($text, 'UTF-8');

        // Insert new word using prepared statement
        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        // Use raw SQL for complex INSERT with dynamic columns
        $bindings = [$lang, $textlc, $text, $data, '', ''];
        $sql = "INSERT INTO words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                WoSentence, WoRomanization, WoStatusChanged,
                {$scoreColumns}
            ) VALUES(?, ?, ?, 1, ?, ?, ?, NOW(), {$scoreValues})"
            . UserScopedQuery::forTablePrepared('words', $bindings);

        $stmt = Connection::prepare($sql);
        $stmt->bind('isssss', $lang, $textlc, $text, $data, '', '');
        $affected = $stmt->execute();

        if ($affected != 1) {
            return "Error: $affected rows affected, expected 1!";
        }

        $wid = $stmt->insertId();

        // Update text items using prepared statement
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        Connection::preparedExecute(
            "UPDATE word_occurrences
            SET Ti2WoID = ?
            WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wid, $lang, $textlc]
        );

        return array($wid, $textlc);
    }

    /**
     * Edit the translation for an existing term.
     *
     * @param int    $wid      Word ID
     * @param string $newTrans New translation
     *
     * @return string WoTextLC, lowercase version of the word
     */
    public function editTermTranslation(int $wid, string $newTrans): string
    {
        $oldtrans = (string) QueryBuilder::table('words')
            ->select(['WoTranslation'])
            ->where('WoID', '=', $wid)
            ->valuePrepared('WoTranslation');

        $oldtransarr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $oldtrans);
        if ($oldtransarr === false) {
            return (string) QueryBuilder::table('words')
                ->select(['WoTextLC'])
                ->where('WoID', '=', $wid)
                ->valuePrepared('WoTextLC');
        }
        $oldtransarr = array_map('trim', $oldtransarr);

        if (!in_array($newTrans, $oldtransarr)) {
            if (trim($oldtrans) == '' || trim($oldtrans) == '*') {
                $oldtrans = $newTrans;
            } else {
                $oldtrans .= ' ' . StringUtils::getFirstSeparator() . ' ' . $newTrans;
            }
            QueryBuilder::table('words')
                ->where('WoID', '=', $wid)
                ->updatePrepared(['WoTranslation' => $oldtrans]);
        }

        return (string) QueryBuilder::table('words')
            ->select(['WoTextLC'])
            ->where('WoID', '=', $wid)
            ->valuePrepared('WoTextLC');
    }

    /**
     * Edit term translation if it exists.
     *
     * @param int    $wid      Word ID
     * @param string $newTrans New translation
     *
     * @return string Term in lower case, or error message if term does not exist
     */
    public function checkUpdateTranslation(int $wid, string $newTrans): string
    {
        $cntWords = QueryBuilder::table('words')
            ->where('WoID', '=', $wid)
            ->countPrepared();

        if ($cntWords == 1) {
            return $this->editTermTranslation($wid, $newTrans);
        }
        return "Error: " . $cntWords . " word ID found!";
    }

    /**
     * Format response for updating translation.
     *
     * @param int    $termId      Term ID
     * @param string $translation New translation
     *
     * @return array{update?: string, error?: string}
     */
    public function formatUpdateTranslation(int $termId, string $translation): array
    {
        $result = $this->checkUpdateTranslation($termId, trim($translation));
        if (str_starts_with($result, "Error")) {
            return ["error" => $result];
        }
        return ["update" => $result];
    }

    /**
     * Format response for adding translation.
     *
     * @param string $termText    Term text
     * @param int    $lgId        Language ID
     * @param string $translation Translation
     *
     * @return array{error?: string, add?: string, term_id?: int|string, term_lc?: string}
     */
    public function formatAddTranslation(string $termText, int $lgId, string $translation): array
    {
        $text = trim($termText);
        $result = $this->addNewTermTranslation($text, $lgId, trim($translation));

        if (is_array($result)) {
            return [
                "term_id" => $result[0],
                "term_lc" => $result[1]
            ];
        } elseif ($result == mb_strtolower($text, 'UTF-8')) {
            return ["add" => $result];
        }
        return ["error" => $result];
    }
}
