<?php

declare(strict_types=1);

/**
 * Create Term From Hover Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Application\UseCases;

use Lwt\Core\Entity\GoogleTranslate;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;

/**
 * Use case for creating a term from the text reading hover action.
 *
 * When a user clicks on a word status in the reading view hover menu,
 * this use case creates the term with the specified status.
 *
 * @since 3.0.0
 */
class CreateTermFromHover
{
    private VocabularyFacade $vocabularyFacade;

    /**
     * Constructor.
     *
     * @param VocabularyFacade|null $vocabularyFacade Vocabulary facade
     */
    public function __construct(?VocabularyFacade $vocabularyFacade = null)
    {
        $this->vocabularyFacade = $vocabularyFacade ?? new VocabularyFacade();
    }

    /**
     * Execute the use case.
     *
     * @param int    $textId     Text ID
     * @param string $wordText   Word text to create
     * @param int    $status     Word status (1-5)
     * @param string $sourceLang Source language code (for translation)
     * @param string $targetLang Target language code (for translation)
     *
     * @return array{
     *     wid: int,
     *     word: string,
     *     wordRaw: string,
     *     translation: string,
     *     status: int,
     *     hex: string
     * }
     */
    public function execute(
        int $textId,
        string $wordText,
        int $status,
        string $sourceLang = '',
        string $targetLang = ''
    ): array {
        // Get translation if status is 1 (new word) and translation params provided
        $translation = '*';
        if ($status === 1 && $sourceLang !== '' && $targetLang !== '') {
            $translationResult = GoogleTranslate::staticTranslate(
                $wordText,
                $sourceLang,
                $targetLang
            );
            if ($translationResult !== false && isset($translationResult[0])) {
                $translation = $translationResult[0];
            }
            // Don't use word as its own translation
            if ($translation === $wordText) {
                $translation = '*';
            }
        }

        // Get language ID from text
        $wordlc = mb_strtolower($wordText, 'UTF-8');
        $bindings = [$textId];
        $langId = (int) Connection::preparedFetchValue(
            "SELECT TxLgID FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings,
            'TxLgID'
        );

        // Create the term using VocabularyFacade
        $term = $this->vocabularyFacade->createTerm(
            $langId,
            $wordText,
            $status,
            $translation,
            '', // sentence
            '', // notes
            '', // romanization
            1   // wordCount (single word)
        );

        $wid = $term->id()->toInt();

        // Link to text items (cross-module operation)
        Connection::preparedExecute(
            "UPDATE word_occurrences SET Ti2WoID = ?
             WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wid, $langId, $wordlc]
        );

        $hex = StringUtils::toClassName(
            Escaping::prepareTextdata($wordlc)
        );

        return [
            'wid' => $wid,
            'word' => $wordText,
            'wordRaw' => $wordText,
            'translation' => $translation,
            'status' => $status,
            'hex' => $hex
        ];
    }

    /**
     * Check if this is a new word (status 1) that should set no-cache headers.
     *
     * @param int $status Word status
     *
     * @return bool True if no-cache headers should be set
     */
    public function shouldSetNoCacheHeaders(int $status): bool
    {
        return $status === 1;
    }
}
