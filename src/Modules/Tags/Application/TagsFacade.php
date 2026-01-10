<?php declare(strict_types=1);
/**
 * Tags Facade
 *
 * Backward-compatible facade for tag operations.
 * Delegates to use case classes for actual implementation.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Tags\Application
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Tags\Application;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Tags\Application\UseCases\CreateTag;
use Lwt\Shared\UI\Helpers\FormHelper;
use Lwt\Modules\Tags\Application\UseCases\DeleteTag;
use Lwt\Modules\Tags\Application\UseCases\GetAllTagNames;
use Lwt\Modules\Tags\Application\UseCases\GetTagById;
use Lwt\Modules\Tags\Application\UseCases\ListTags;
use Lwt\Modules\Tags\Application\UseCases\UpdateTag;
use Lwt\Modules\Tags\Domain\Tag;
use Lwt\Modules\Tags\Domain\TagAssociationInterface;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use Lwt\Modules\Tags\Domain\TagType;
use Lwt\Modules\Tags\Infrastructure\MySqlArchivedTextTagAssociation;
use Lwt\Modules\Tags\Infrastructure\MySqlTermTagRepository;
use Lwt\Modules\Tags\Infrastructure\MySqlTextTagAssociation;
use Lwt\Modules\Tags\Infrastructure\MySqlTextTagRepository;
use Lwt\Modules\Tags\Infrastructure\MySqlWordTagAssociation;

/**
 * Facade for tag module operations.
 *
 * Provides a unified interface to all tag-related use cases.
 * Designed for backward compatibility with existing TagService callers.
 *
 * @since 3.0.0
 */
class TagsFacade
{
    private TagType $tagType;
    private TagRepositoryInterface $repository;
    private TagAssociationInterface $association;

    // Term tag instances (static for caching)
    private static ?TagRepositoryInterface $termRepository = null;
    private static ?TagRepositoryInterface $textRepository = null;
    private static ?TagAssociationInterface $wordAssociation = null;
    private static ?TagAssociationInterface $textAssociation = null;
    private static ?TagAssociationInterface $archivedTextAssociation = null;
    private static ?GetAllTagNames $getAllTagNames = null;

    /**
     * Constructor.
     *
     * @param TagType                      $tagType     Tag type (TERM or TEXT)
     * @param TagRepositoryInterface|null  $repository  Tag repository
     * @param TagAssociationInterface|null $association Tag association handler
     */
    public function __construct(
        TagType $tagType = TagType::TERM,
        ?TagRepositoryInterface $repository = null,
        ?TagAssociationInterface $association = null
    ) {
        $this->tagType = $tagType;

        // Initialize repositories based on type
        if ($tagType === TagType::TERM) {
            $this->repository = $repository ?? self::getTermRepository();
            $this->association = $association ?? self::getWordAssociation();
        } else {
            $this->repository = $repository ?? self::getTextRepository();
            $this->association = $association ?? self::getTextAssociation();
        }
    }

    // =====================
    // FACTORY METHODS
    // =====================

    /**
     * Create a facade for term tags.
     *
     * @return self
     */
    public static function forTermTags(): self
    {
        return new self(TagType::TERM);
    }

    /**
     * Create a facade for text tags.
     *
     * @return self
     */
    public static function forTextTags(): self
    {
        return new self(TagType::TEXT);
    }

    // =====================
    // SINGLETON GETTERS
    // =====================

    private static function getTermRepository(): TagRepositoryInterface
    {
        if (self::$termRepository === null) {
            self::$termRepository = new MySqlTermTagRepository();
        }
        return self::$termRepository;
    }

    private static function getTextRepository(): TagRepositoryInterface
    {
        if (self::$textRepository === null) {
            self::$textRepository = new MySqlTextTagRepository();
        }
        return self::$textRepository;
    }

    private static function getWordAssociation(): TagAssociationInterface
    {
        if (self::$wordAssociation === null) {
            self::$wordAssociation = new MySqlWordTagAssociation(self::getTermRepository());
        }
        return self::$wordAssociation;
    }

    private static function getTextAssociation(): TagAssociationInterface
    {
        if (self::$textAssociation === null) {
            self::$textAssociation = new MySqlTextTagAssociation(self::getTextRepository());
        }
        return self::$textAssociation;
    }

    private static function getArchivedTextAssociation(): TagAssociationInterface
    {
        if (self::$archivedTextAssociation === null) {
            self::$archivedTextAssociation = new MySqlArchivedTextTagAssociation(self::getTextRepository());
        }
        return self::$archivedTextAssociation;
    }

    private static function getGetAllTagNames(): GetAllTagNames
    {
        if (self::$getAllTagNames === null) {
            self::$getAllTagNames = new GetAllTagNames(
                self::getTermRepository(),
                self::getTextRepository()
            );
        }
        return self::$getAllTagNames;
    }

    // =====================
    // CRUD OPERATIONS
    // =====================

    /**
     * Create a new tag.
     *
     * @param string $text    Tag text
     * @param string $comment Tag comment
     *
     * @return string Result message
     */
    public function create(string $text, string $comment = ''): string
    {
        $useCase = new CreateTag($this->repository);
        return $useCase->executeWithMessage($text, $comment);
    }

    /**
     * Update an existing tag.
     *
     * @param int    $id      Tag ID
     * @param string $text    New tag text
     * @param string $comment New tag comment
     *
     * @return string Result message
     */
    public function update(int $id, string $text, string $comment): string
    {
        $useCase = new UpdateTag($this->repository);
        return $useCase->executeWithMessage($id, $text, $comment);
    }

    /**
     * Delete a single tag.
     *
     * @param int $id Tag ID
     *
     * @return string Result message
     */
    public function delete(int $id): string
    {
        $useCase = new DeleteTag($this->repository, $this->association);
        return $useCase->executeWithMessage($id);
    }

    /**
     * Delete multiple tags.
     *
     * @param int[] $ids Tag IDs
     *
     * @return string Result message
     */
    public function deleteMultiple(array $ids): string
    {
        $useCase = new DeleteTag($this->repository, $this->association);
        return $useCase->executeMultipleWithMessage($ids);
    }

    /**
     * Delete all tags matching filter.
     *
     * @param string $query Filter query
     *
     * @return string Result message
     */
    public function deleteAll(string $query = ''): string
    {
        $useCase = new DeleteTag($this->repository, $this->association);
        return $useCase->executeAllWithMessage($query);
    }

    /**
     * Get a tag by ID.
     *
     * @param int $id Tag ID
     *
     * @return array|null Tag data or null
     */
    public function getById(int $id): ?array
    {
        $useCase = new GetTagById($this->repository);
        return $useCase->executeAsArray($id);
    }

    /**
     * Get paginated list of tags.
     *
     * @param string $query   Filter query
     * @param string $orderBy Sort column
     * @param int    $page    Page number
     * @param int    $perPage Items per page
     *
     * @return array Tag list with usage counts
     */
    public function getList(
        string $query = '',
        string $orderBy = 'text',
        int $page = 1,
        int $perPage = 0
    ): array {
        $useCase = new ListTags($this->repository);
        $result = $useCase->execute($page, $perPage, $query, $orderBy);

        // Convert to backward-compatible format
        $tags = [];
        foreach ($result['tags'] as $tag) {
            $tagData = [
                'id' => $tag->id()->toInt(),
                'text' => $tag->text(),
                'comment' => $tag->comment(),
                'usageCount' => $result['usageCounts'][$tag->id()->toInt()] ?? 0,
            ];

            // Add archived count for text tags
            if ($this->tagType === TagType::TEXT) {
                $tagData['archivedUsageCount'] = $this->getArchivedUsageCount($tag->id()->toInt());
            }

            $tags[] = $tagData;
        }

        return $tags;
    }

    /**
     * Get total count of tags.
     *
     * @param string $query Filter query
     *
     * @return int
     */
    public function getCount(string $query = ''): int
    {
        $useCase = new ListTags($this->repository);
        return $useCase->count($query);
    }

    /**
     * Get usage count for a tag.
     *
     * @param int $tagId Tag ID
     *
     * @return int
     */
    public function getUsageCount(int $tagId): int
    {
        return $this->repository->getUsageCount($tagId);
    }

    /**
     * Get archived text usage count (text tags only).
     *
     * @param int $tagId Tag ID
     *
     * @return int
     */
    public function getArchivedUsageCount(int $tagId): int
    {
        if ($this->tagType !== TagType::TEXT) {
            return 0;
        }

        return self::getArchivedTextAssociation()->getItemCount($tagId);
    }

    // =====================
    // PAGINATION & SORTING
    // =====================

    /**
     * Get pagination info.
     *
     * @param int $totalCount   Total count
     * @param int $currentPage  Current page
     *
     * @return array{pages: int, currentPage: int, perPage: int}
     */
    public function getPagination(int $totalCount, int $currentPage): array
    {
        $useCase = new ListTags($this->repository);
        return $useCase->getPagination($totalCount, $currentPage, $useCase->getMaxPerPage());
    }

    /**
     * Get maximum items per page.
     *
     * @return int
     */
    public function getMaxPerPage(): int
    {
        $useCase = new ListTags($this->repository);
        return $useCase->getMaxPerPage();
    }

    /**
     * Get sort options for dropdown.
     *
     * @return array
     */
    public function getSortOptions(): array
    {
        $useCase = new ListTags($this->repository);
        return $useCase->getSortOptions();
    }

    /**
     * Get sort column from index.
     *
     * @param int $index Sort index
     *
     * @return string
     */
    public function getSortColumn(int $index): string
    {
        $useCase = new ListTags($this->repository);
        return $useCase->getSortColumn($index);
    }

    // =====================
    // TAG TYPE INFO
    // =====================

    /**
     * Get the current tag type.
     *
     * @return TagType
     */
    public function getTagType(): TagType
    {
        return $this->tagType;
    }

    /**
     * Get the tag type label.
     *
     * @return string
     */
    public function getTagTypeLabel(): string
    {
        return $this->tagType->label();
    }

    /**
     * Get the base URL for this tag type.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->tagType->baseUrl();
    }

    /**
     * Get URL to view items with a tag.
     *
     * @param int $tagId Tag ID
     *
     * @return string
     */
    public function getItemsUrl(int $tagId): string
    {
        return sprintf($this->tagType->itemsUrlPattern(), $tagId);
    }

    /**
     * Get URL to view archived texts with a tag.
     *
     * @param int $tagId Tag ID
     *
     * @return string
     */
    public function getArchivedItemsUrl(int $tagId): string
    {
        if ($this->tagType !== TagType::TEXT) {
            return '';
        }
        return sprintf('/archived?tag=%d', $tagId);
    }

    // =====================
    // STATIC TAG CACHE METHODS (backward compatibility)
    // =====================

    /**
     * Get all term tag names with session caching.
     *
     * @param bool $refresh Force refresh
     *
     * @return string[]
     */
    public static function getAllTermTags(bool $refresh = false): array
    {
        return self::getGetAllTagNames()->getTermTags($refresh);
    }

    /**
     * Get all text tag names with session caching.
     *
     * @param bool $refresh Force refresh
     *
     * @return string[]
     */
    public static function getAllTextTags(bool $refresh = false): array
    {
        return self::getGetAllTagNames()->getTextTags($refresh);
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
        self::getWordAssociation()->setTagsByName($wordId, $tagNames);
        self::getAllTermTags(true); // Refresh cache
    }

    /**
     * Save tags for a text.
     *
     * @param int      $textId   Text ID
     * @param string[] $tagNames Tag names
     *
     * @return void
     */
    public static function saveTextTags(int $textId, array $tagNames): void
    {
        self::getTextAssociation()->setTagsByName($textId, $tagNames);
        self::getAllTextTags(true); // Refresh cache
    }

    /**
     * Save tags for an archived text.
     *
     * @param int      $textId   Archived text ID
     * @param string[] $tagNames Tag names
     *
     * @return void
     */
    public static function saveArchivedTextTags(int $textId, array $tagNames): void
    {
        self::getArchivedTextAssociation()->setTagsByName($textId, $tagNames);
        self::getAllTextTags(true); // Refresh cache
    }

    // =====================
    // HTML RENDERING (backward compatibility)
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
            $tagNames = self::getWordAssociation()->getTagTextsForItem($wordId);
            foreach ($tagNames as $name) {
                $html .= '<li>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        return $html . '</ul>';
    }

    /**
     * Get HTML list of tags for a text.
     *
     * @param int $textId Text ID
     *
     * @return string HTML UL element
     */
    public static function getTextTagsHtml(int $textId): string
    {
        $html = '<ul id="text_tag_map" class="respinput">';

        if ($textId > 0) {
            $tagNames = self::getTextAssociation()->getTagTextsForItem($textId);
            foreach ($tagNames as $name) {
                $html .= '<li>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        return $html . '</ul>';
    }

    /**
     * Get HTML list of tags for an archived text.
     *
     * @param int $textId Archived text ID
     *
     * @return string HTML UL element
     */
    public static function getArchivedTextTagsHtml(int $textId): string
    {
        $html = '<ul id="text_tag_map" class="respinput">';

        if ($textId > 0) {
            $tagNames = self::getArchivedTextAssociation()->getTagTextsForItem($textId);
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

        $tagNames = self::getWordAssociation()->getTagTextsForItem($wordId);
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

        return self::getWordAssociation()->getTagTextsForItem($wordId);
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

        // Refresh cache
        self::getAllTermTags(true);

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

        // Refresh cache again after changes
        self::getAllTermTags(true);
    }

    /**
     * Cleanup orphaned tag links.
     *
     * @return void
     */
    public function cleanupOrphanedLinks(): void
    {
        $this->association->cleanupOrphanedLinks();
    }

    // =====================
    // FORM-READING SAVE METHODS (backward compatibility)
    // =====================

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
            self::getWordAssociation()->setTagsByName($wordId, []);
            return;
        }

        /** @var array<int|string, scalar> $tagList */
        $tagList = $termTags['TagList'];
        $tagNames = array_map('strval', $tagList);
        self::saveWordTags($wordId, $tagNames);
    }

    /**
     * Save tags for a text from form input.
     *
     * @param int        $textId   Text ID
     * @param array|null $textTags Optional tags array. If null, reads from request.
     *
     * @return void
     */
    public static function saveTextTagsFromForm(int $textId, ?array $textTags = null): void
    {
        if ($textTags === null) {
            $textTags = InputValidator::getArray('TextTags');
        }

        if (
            empty($textTags)
            || !isset($textTags['TagList'])
            || !is_array($textTags['TagList'])
        ) {
            // Clear existing tags if no tags submitted
            self::getTextAssociation()->setTagsByName($textId, []);
            return;
        }

        /** @var array<int|string, scalar> $tagList */
        $tagList = $textTags['TagList'];
        $tagNames = array_map('strval', $tagList);
        self::saveTextTags($textId, $tagNames);
    }

    /**
     * Save tags for an archived text from form input.
     *
     * @param int $textId Archived text ID
     *
     * @return void
     */
    public static function saveArchivedTextTagsFromForm(int $textId): void
    {
        $textTags = InputValidator::getArray('TextTags');

        if (
            empty($textTags)
            || !isset($textTags['TagList'])
            || !is_array($textTags['TagList'])
        ) {
            // Clear existing tags if no tags submitted
            self::getArchivedTextAssociation()->setTagsByName($textId, []);
            return;
        }

        /** @var array<int|string, scalar> $tagList */
        $tagList = $textTags['TagList'];
        $tagNames = array_map('strval', $tagList);
        self::saveArchivedTextTags($textId, $tagNames);
    }

    // =====================
    // BATCH OPERATIONS
    // =====================

    /**
     * Add a tag to multiple words.
     *
     * @param string $tagText Tag text to add
     * @param string $idList  SQL list of word IDs, e.g. "(1,2,3)"
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function addTagToWords(string $tagText, string $idList): array
    {
        if ($idList === '()') {
            return ['count' => 0, 'error' => null];
        }

        $tagId = self::getOrCreateTermTag($tagText);
        if ($tagId === null) {
            return ['count' => 0, 'error' => 'Failed to create tag'];
        }

        // Use raw SQL for LEFT JOIN with dynamic IN clause
        $sql = 'SELECT WoID
            FROM words
            LEFT JOIN word_tag_map ON WoID = WtWoID AND WtTgID = ' . $tagId . '
            WHERE WtTgID IS NULL AND WoID IN ' . $idList
            . UserScopedQuery::forTable('words');
        $res = Connection::query($sql);

        $count = 0;
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $count += (int) Connection::execute(
                    'INSERT IGNORE INTO word_tag_map (WtWoID, WtTgID)
                    VALUES(' . (int)$record['WoID'] . ', ' . $tagId . ')'
                );
            }
            mysqli_free_result($res);
        }

        self::getAllTermTags(true);

        return ['count' => $count, 'error' => null];
    }

    /**
     * Remove a tag from multiple words.
     *
     * @param string $tagText Tag text to remove
     * @param string $idList  SQL list of word IDs, e.g. "(1,2,3)"
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function removeTagFromWords(string $tagText, string $idList): array
    {
        if ($idList === '()') {
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

        $sql = 'SELECT WoID FROM words WHERE WoID IN ' . $idList
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

    /**
     * Add a tag to multiple texts.
     *
     * @param string $tagText Tag text to add
     * @param string $idList  SQL list of text IDs, e.g. "(1,2,3)"
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function addTagToTexts(string $tagText, string $idList): array
    {
        if ($idList === '()') {
            return ['count' => 0, 'error' => null];
        }

        $tagId = self::getOrCreateTextTag($tagText);
        if ($tagId === null) {
            return ['count' => 0, 'error' => 'Failed to create tag'];
        }

        $sql = 'SELECT TxID FROM texts
            LEFT JOIN text_tag_map ON TxID = TtTxID AND TtT2ID = ' . $tagId . '
            WHERE TtT2ID IS NULL AND TxID IN ' . $idList
            . UserScopedQuery::forTable('texts');
        $res = Connection::query($sql);

        $count = 0;
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $count += (int) Connection::execute(
                    'INSERT IGNORE INTO text_tag_map (TtTxID, TtT2ID)
                    VALUES(' . (int)$record['TxID'] . ', ' . $tagId . ')'
                );
            }
            mysqli_free_result($res);
        }

        self::getAllTextTags(true);

        return ['count' => $count, 'error' => null];
    }

    /**
     * Remove a tag from multiple texts.
     *
     * @param string $tagText Tag text to remove
     * @param string $idList  SQL list of text IDs, e.g. "(1,2,3)"
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function removeTagFromTexts(string $tagText, string $idList): array
    {
        if ($idList === '()') {
            return ['count' => 0, 'error' => null];
        }

        /** @var int|string|null $tagIdRaw */
        $tagIdRaw = Connection::preparedFetchValue(
            'SELECT T2ID FROM text_tags WHERE T2Text = ?',
            [$tagText],
            'T2ID'
        );

        if ($tagIdRaw === null) {
            return ['count' => 0, 'error' => "Tag {$tagText} not found"];
        }
        $tagId = (int) $tagIdRaw;

        $sql = 'SELECT TxID FROM texts WHERE TxID IN ' . $idList
            . UserScopedQuery::forTable('texts');
        $res = Connection::query($sql);

        $count = 0;
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $count++;
                QueryBuilder::table('text_tag_map')
                    ->where('TtTxID', '=', (int)$record['TxID'])
                    ->where('TtT2ID', '=', $tagId)
                    ->delete();
            }
            mysqli_free_result($res);
        }

        return ['count' => $count, 'error' => null];
    }

    /**
     * Add a tag to multiple archived texts.
     *
     * @param string $tagText Tag text to add
     * @param string $idList  SQL list of archived text IDs, e.g. "(1,2,3)"
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function addTagToArchivedTexts(string $tagText, string $idList): array
    {
        if ($idList === '()') {
            return ['count' => 0, 'error' => null];
        }

        $tagId = self::getOrCreateTextTag($tagText);
        if ($tagId === null) {
            return ['count' => 0, 'error' => 'Failed to create tag'];
        }

        // Archived texts are in texts table with TxArchivedAt IS NOT NULL
        $sql = 'SELECT TxID FROM texts
            LEFT JOIN text_tag_map ON TxID = TtTxID AND TtT2ID = ' . $tagId . '
            WHERE TtT2ID IS NULL AND TxArchivedAt IS NOT NULL AND TxID IN ' . $idList
            . UserScopedQuery::forTable('texts');
        $res = Connection::query($sql);

        $count = 0;
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $count += (int) Connection::execute(
                    'INSERT IGNORE INTO text_tag_map (TtTxID, TtT2ID)
                    VALUES(' . (int)$record['TxID'] . ', ' . $tagId . ')'
                );
            }
            mysqli_free_result($res);
        }

        self::getAllTextTags(true);

        return ['count' => $count, 'error' => null];
    }

    /**
     * Remove a tag from multiple archived texts.
     *
     * @param string $tagText Tag text to remove
     * @param string $idList  SQL list of archived text IDs, e.g. "(1,2,3)"
     *
     * @return array{count: int, error: ?string} Result with count and optional error
     */
    public static function removeTagFromArchivedTexts(
        string $tagText,
        string $idList
    ): array {
        if ($idList === '()') {
            return ['count' => 0, 'error' => null];
        }

        /** @var int|string|null $tagIdRaw */
        $tagIdRaw = Connection::preparedFetchValue(
            'SELECT T2ID FROM text_tags WHERE T2Text = ?',
            [$tagText],
            'T2ID'
        );

        if ($tagIdRaw === null) {
            return ['count' => 0, 'error' => "Tag {$tagText} not found"];
        }
        $tagId = (int) $tagIdRaw;

        // Archived texts are in texts table with TxArchivedAt IS NOT NULL
        $sql = 'SELECT TxID FROM texts WHERE TxArchivedAt IS NOT NULL AND TxID IN ' . $idList
            . UserScopedQuery::forTable('texts');
        $res = Connection::query($sql);

        $count = 0;
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $count++;
                QueryBuilder::table('text_tag_map')
                    ->where('TtTxID', '=', (int)$record['TxID'])
                    ->delete();
            }
            mysqli_free_result($res);
        }

        return ['count' => $count, 'error' => null];
    }

    // =====================
    // SELECT OPTIONS HELPERS
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

    /**
     * Get text tag select options HTML for filtering.
     *
     * @param int|string|null $selected Currently selected value
     * @param int|string      $langId   Language ID filter ('' for all)
     *
     * @return string HTML options
     */
    public static function getTextTagSelectOptions(
        int|string|null $selected,
        int|string $langId
    ): string {
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        if ($langId === '') {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM texts, text_tags, text_tag_map
                WHERE T2ID = TtT2ID AND TtTxID = TxID
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                []
            );
        } else {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM texts, text_tags, text_tag_map
                WHERE T2ID = TtT2ID AND TtTxID = TxID AND TxLgID = ?
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                [$langId]
            );
        }

        $count = 0;
        foreach ($rows as $record) {
            $count++;
            $tagId = (int) $record['T2ID'];
            $tagText = (string) ($record['T2Text'] ?? '');
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

    /**
     * Get text tag select options with text IDs for word list filtering.
     *
     * @param int|string      $langId   Language ID filter
     * @param int|string|null $selected Currently selected value
     *
     * @return string HTML options
     */
    public static function getTextTagSelectOptionsWithTextIds(
        int|string $langId,
        int|string|null $selected
    ): string {
        $selected = $selected ?? '';
        $untaggedOption = '';

        $html = '<option value="&amp;texttag"' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        if ($langId) {
            $rows = Connection::preparedFetchAll(
                'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID,
                GROUP_CONCAT(TxID ORDER BY TxID) AS TextID
                FROM texts
                LEFT JOIN text_tag_map ON TxID = TtTxID
                LEFT JOIN text_tags ON TtT2ID = T2ID
                WHERE TxLgID = ?
                GROUP BY UPPER(TagName)',
                [$langId]
            );
        } else {
            $rows = Connection::preparedFetchAll(
                'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID,
                GROUP_CONCAT(TxID ORDER BY TxID) AS TextID
                FROM texts
                LEFT JOIN text_tag_map ON TxID = TtTxID
                LEFT JOIN text_tags ON TtT2ID = T2ID
                GROUP BY UPPER(TagName)',
                []
            );
        }

        foreach ($rows as $record) {
            $tagName = (string) $record['TagName'];
            $textId = (string) $record['TextID'];
            $tagId = (int) $record['TagID'];
            if ($tagName === '1') {
                $untaggedOption = '<option disabled="disabled">--------</option>' .
                    '<option value="' . $textId . '&amp;texttag=-1"' .
                    FormHelper::getSelected($selected, "-1") . '>UNTAGGED</option>';
            } else {
                $html .= '<option value="' . $textId . '&amp;texttag=' .
                    $tagId . '"' . FormHelper::getSelected($selected, $tagId) .
                    '>' . $tagName . '</option>';
            }
        }

        return $html . $untaggedOption;
    }

    /**
     * Get archived text tag select options HTML for filtering.
     *
     * @param int|string|null $selected Currently selected value
     * @param int|string      $langId   Language ID filter ('' for all)
     *
     * @return string HTML options
     */
    public static function getArchivedTextTagSelectOptions(
        int|string|null $selected,
        int|string $langId
    ): string {
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        // Archived texts are in texts table with TxArchivedAt IS NOT NULL
        if ($langId === '') {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM texts, text_tags, text_tag_map
                WHERE T2ID = TtT2ID AND TtTxID = TxID AND TxArchivedAt IS NOT NULL
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                []
            );
        } else {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM texts, text_tags, text_tag_map
                WHERE T2ID = TtT2ID AND TtTxID = TxID AND TxArchivedAt IS NOT NULL AND TxLgID = ?
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                [$langId]
            );
        }

        $count = 0;
        foreach ($rows as $record) {
            $count++;
            $tagId = (int) $record['T2ID'];
            $tagText = (string) ($record['T2Text'] ?? '');
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
    // HELPER METHODS
    // =====================

    /**
     * Get or create a term tag, returning its ID.
     *
     * @param string $tagText Tag text
     *
     * @return int|null Tag ID or null on failure
     */
    private static function getOrCreateTermTag(string $tagText): ?int
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

    /**
     * Get or create a text tag, returning its ID.
     *
     * @param string $tagText Tag text
     *
     * @return int|null Tag ID or null on failure
     */
    private static function getOrCreateTextTag(string $tagText): ?int
    {
        /** @var int|string|null $tagIdRaw */
        $tagIdRaw = Connection::preparedFetchValue(
            'SELECT T2ID FROM text_tags WHERE T2Text = ?',
            [$tagText],
            'T2ID'
        );

        if ($tagIdRaw === null) {
            QueryBuilder::table('text_tags')->insertPrepared(['T2Text' => $tagText]);
            /** @var int|string|null $tagIdRaw */
            $tagIdRaw = Connection::preparedFetchValue(
                'SELECT T2ID FROM text_tags WHERE T2Text = ?',
                [$tagText],
                'T2ID'
            );
        }

        return $tagIdRaw !== null ? (int) $tagIdRaw : null;
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

    /**
     * Build WHERE clause for query filtering.
     *
     * @param string $query Filter query string
     *
     * @return array{clause: string, params: array} Array with SQL clause and parameters
     */
    public function buildWhereClause(string $query): array
    {
        if ($query === '') {
            return ['clause' => '', 'params' => []];
        }

        $prefix = $this->tagType === TagType::TEXT ? 'T2' : 'Tg';
        $searchValue = str_replace("*", "%", $query);
        $clause = ' AND (' . $prefix . 'Text LIKE ? OR ' .
                  $prefix . 'Comment LIKE ?)';

        return ['clause' => $clause, 'params' => [$searchValue, $searchValue]];
    }

    /**
     * Format duplicate entry error message for display.
     *
     * @param string $message Original error message
     *
     * @return string Formatted error message
     */
    public function formatDuplicateError(string $message): string
    {
        $keyName = $this->tagType === TagType::TEXT ? 'T2Text' : 'TgText';

        if (
            substr($message, 0, 24) == "Error: Duplicate entry '"
            && substr($message, -strlen("' for key '$keyName'")) == "' for key '$keyName'"
        ) {
            $tagName = substr($message, 24);
            $tagName = substr($tagName, 0, strlen($tagName) - strlen("' for key '$keyName'"));
            $tagTypeLabel = $this->tagType === TagType::TEXT ? 'Text Tag' : 'Term Tag';
            return "Error: $tagTypeLabel '" . $tagName .
                   "' already exists. Please go back and correct this!";
        }

        return $message;
    }
}
