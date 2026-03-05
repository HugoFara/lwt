<?php

/**
 * Term Tag Service
 *
 * Extracted from TagsFacade — handles term (word) tag associations,
 * HTML rendering, batch operations, and select options.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Tags\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Tags\Application\Services;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Shared\UI\Helpers\FormHelper;
use Lwt\Modules\Tags\Domain\TagAssociationInterface;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use Lwt\Modules\Tags\Infrastructure\MySqlTermTagRepository;
use Lwt\Modules\Tags\Infrastructure\MySqlWordTagAssociation;

/**
 * Service for term (word) tag operations.
 *
 * Manages word-tag associations, HTML rendering of tag lists,
 * batch add/remove operations, and select option generation.
 *
 * @since 3.0.0
 */
class TermTagService
{
    private static ?TagRepositoryInterface $repository = null;
    private static ?TagAssociationInterface $association = null;

    /**
     * Get the term tag repository.
     *
     * @return TagRepositoryInterface
     */
    public static function getRepository(): TagRepositoryInterface
    {
        if (self::$repository === null) {
            self::$repository = new MySqlTermTagRepository();
        }
        return self::$repository;
    }

    /**
     * Get the word tag association handler.
     *
     * @return TagAssociationInterface
     */
    public static function getAssociation(): TagAssociationInterface
    {
        if (self::$association === null) {
            self::$association = new MySqlWordTagAssociation(self::getRepository());
        }
        return self::$association;
    }

    // =====================
    // ASSOCIATION METHODS
    // =====================

    /**
     * Save tags for a word.
     *
     * @param int      $wordId   Word ID
     * @param string[] $tagNames Tag names
     *
     * @return void
     */
    public static function saveWordTags(int $wordId, array $tagNames): void
    {
        self::getAssociation()->setTagsByName($wordId, $tagNames);
    }

    /**
     * Save tags for a word from an array of tag names.
     *
     * @param int      $wordId   Word ID
     * @param string[] $tagNames Array of tag name strings
     *
     * @return void
     */
    public static function saveWordTagsFromArray(int $wordId, array $tagNames): void
    {
        // Delete existing tags for this word
        QueryBuilder::table('word_tag_map')
            ->where('WtWoID', '=', $wordId)
            ->delete();

        if (empty($tagNames)) {
            return;
        }

        foreach ($tagNames as $tag) {
            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }

            // Create tag if it doesn't exist
            // Use INSERT IGNORE to handle race condition / stale cache (Issue #120)
            $sessionTags = isset($_SESSION['TAGS']) && is_array($_SESSION['TAGS']) ? $_SESSION['TAGS'] : [];
            if (!in_array($tag, $sessionTags, true)) {
                Connection::preparedExecute(
                    'INSERT IGNORE INTO tags (TgText) VALUES (?)',
                    [$tag]
                );
            }

            // Link tag to word using raw SQL for INSERT...SELECT
            Connection::preparedExecute(
                "INSERT INTO word_tag_map (WtWoID, WtTgID)
                SELECT ?, TgID
                FROM tags
                WHERE TgText = ?",
                [$wordId, $tag]
            );
        }
    }

    /**
     * Save tags for a word from form input.
     *
     * Reads 'TermTags' from request and saves to word.
     *
     * @param int $wordId Word ID
     *
     * @return void
     */
    public static function saveWordTagsFromForm(int $wordId): void
    {
        $termTags = InputValidator::getArray('TermTags');
        if (
            empty($termTags)
            || !isset($termTags['TagList'])
            || !is_array($termTags['TagList'])
        ) {
            // Clear existing tags if no tags submitted
            self::getAssociation()->setTagsByName($wordId, []);
            return;
        }

        /** @var array<int|string, scalar> $tagList */
        $tagList = $termTags['TagList'];
        $tagNames = array_map('strval', $tagList);
        self::saveWordTags($wordId, $tagNames);
    }

    // =====================
    // HTML RENDERING
    // =====================

    /**
     * Get HTML list of tags for a word.
     *
     * @param int $wordId Word ID
     *
     * @return string HTML UL element
     */
    public static function getWordTagsHtml(int $wordId): string
    {
        $html = '<ul id="termtags">';

        if ($wordId > 0) {
            $tagNames = self::getAssociation()->getTagTextsForItem($wordId);
            foreach ($tagNames as $name) {
                $html .= '<li>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        return $html . '</ul>';
    }

    /**
     * Get comma-separated tag list for a word.
     *
     * @param int  $wordId     Word ID
     * @param bool $escapeHtml Whether to escape HTML
     *
     * @return string
     */
    public static function getWordTagList(int $wordId, bool $escapeHtml = true): string
    {
        if ($wordId <= 0) {
            return '';
        }

        $tagNames = self::getAssociation()->getTagTextsForItem($wordId);
        $list = implode(', ', $tagNames);

        return $escapeHtml ? htmlspecialchars($list, ENT_QUOTES, 'UTF-8') : $list;
    }

    /**
     * Get word tags as array.
     *
     * @param int $wordId Word ID
     *
     * @return string[]
     */
    public static function getWordTagsArray(int $wordId): array
    {
        if ($wordId <= 0) {
            return [];
        }

        return self::getAssociation()->getTagTextsForItem($wordId);
    }

    /**
     * Get formatted tag list as Bulma tag components for a word.
     *
     * @param int    $wordId  Word ID
     * @param string $size    Bulma size class (e.g., 'is-small', 'is-normal')
     * @param string $color   Bulma color class (e.g., 'is-info', 'is-primary')
     * @param bool   $isLight Whether to use light variant
     *
     * @return string HTML for Bulma tags
     */
    public static function getWordTagListHtml(
        int $wordId,
        string $size = 'is-small',
        string $color = 'is-info',
        bool $isLight = true
    ): string {
        $tagList = self::getWordTagList($wordId, false);
        return \Lwt\Shared\UI\Helpers\TagHelper::renderInline($tagList, $size, $color, $isLight);
    }

    // =====================
    // BATCH OPERATIONS
    // =====================

    /**
     * Add a tag to multiple words.
     *
     * @param string $tagText Tag text to add
     * @param int[]  $ids     Array of word IDs
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function addTagToWords(string $tagText, array $ids): array
    {
        $inClause = Connection::buildIntInClause($ids);
        if ($inClause === '()') {
            return ['count' => 0, 'error' => null];
        }

        $tagId = self::getOrCreateTermTag($tagText);
        if ($tagId === null) {
            return ['count' => 0, 'error' => 'Failed to create tag'];
        }

        $sql = 'SELECT WoID
            FROM words
            LEFT JOIN word_tag_map ON WoID = WtWoID AND WtTgID = ' . $tagId . '
            WHERE WtTgID IS NULL AND WoID IN ' . $inClause
            . UserScopedQuery::forTable('words');
        $res = Connection::query($sql);

        $count = 0;
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                Connection::preparedExecute(
                    'INSERT IGNORE INTO word_tag_map (WtWoID, WtTgID) VALUES(?, ?)',
                    [(int)$record['WoID'], $tagId]
                );
                $count++;
            }
            mysqli_free_result($res);
        }

        return ['count' => $count, 'error' => null];
    }

    /**
     * Remove a tag from multiple words.
     *
     * @param string $tagText Tag text to remove
     * @param int[]  $ids     Array of word IDs
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function removeTagFromWords(string $tagText, array $ids): array
    {
        $inClause = Connection::buildIntInClause($ids);
        if ($inClause === '()') {
            return ['count' => 0, 'error' => null];
        }

        /** @var int|string|null $tagIdRaw */
        $tagIdRaw = Connection::preparedFetchValue(
            'SELECT TgID FROM tags WHERE TgText = ?',
            [$tagText],
            'TgID'
        );

        if ($tagIdRaw === null) {
            return ['count' => 0, 'error' => "Tag {$tagText} not found"];
        }
        $tagId = (int) $tagIdRaw;

        $sql = 'SELECT WoID FROM words WHERE WoID IN ' . $inClause
            . UserScopedQuery::forTable('words');
        $res = Connection::query($sql);

        $count = 0;
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $count++;
                QueryBuilder::table('word_tag_map')
                    ->where('WtWoID', '=', (int)$record['WoID'])
                    ->where('WtTgID', '=', $tagId)
                    ->delete();
            }
            mysqli_free_result($res);
        }

        return ['count' => $count, 'error' => null];
    }

    // =====================
    // SELECT OPTIONS
    // =====================

    /**
     * Get term tag select options HTML for filtering.
     *
     * @param int|string|null $selected Currently selected value
     * @param int|string      $langId   Language ID filter ('' for all)
     *
     * @return string HTML options
     */
    public static function getTermTagSelectOptions(
        int|string|null $selected,
        int|string $langId
    ): string {
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        if ($langId === '') {
            $rows = Connection::preparedFetchAll(
                "SELECT TgID, TgText
                FROM words, tags, word_tag_map
                WHERE TgID = WtTgID AND WtWoID = WoID
                GROUP BY TgID
                ORDER BY UPPER(TgText)",
                []
            );
        } else {
            $rows = Connection::preparedFetchAll(
                "SELECT TgID, TgText
                FROM words, tags, word_tag_map
                WHERE TgID = WtTgID AND WtWoID = WoID AND WoLgID = ?
                GROUP BY TgID
                ORDER BY UPPER(TgText)",
                [$langId]
            );
        }

        $count = 0;
        foreach ($rows as $record) {
            $count++;
            $tagId = (int) $record['TgID'];
            $tagText = (string) ($record['TgText'] ?? '');
            $html .= '<option value="' . $tagId . '"' .
                FormHelper::getSelected($selected, $tagId) . '>' .
                htmlspecialchars($tagText, ENT_QUOTES, 'UTF-8') . '</option>';
        }

        if ($count > 0) {
            $html .= '<option disabled="disabled">--------</option>';
            $html .= '<option value="-1"' . FormHelper::getSelected($selected, -1) . '>UNTAGGED</option>';
        }

        return $html;
    }

    // =====================
    // HELPERS
    // =====================

    /**
     * Get or create a term tag, returning its ID.
     *
     * @param string $tagText Tag text
     *
     * @return int|null Tag ID or null on failure
     */
    public static function getOrCreateTermTag(string $tagText): ?int
    {
        /** @var int|string|null $tagIdRaw */
        $tagIdRaw = Connection::preparedFetchValue(
            'SELECT TgID FROM tags WHERE TgText = ?',
            [$tagText],
            'TgID'
        );

        if ($tagIdRaw === null) {
            QueryBuilder::table('tags')->insertPrepared(['TgText' => $tagText]);
            /** @var int|string|null $tagIdRaw */
            $tagIdRaw = Connection::preparedFetchValue(
                'SELECT TgID FROM tags WHERE TgText = ?',
                [$tagText],
                'TgID'
            );
        }

        return $tagIdRaw !== null ? (int) $tagIdRaw : null;
    }
}
