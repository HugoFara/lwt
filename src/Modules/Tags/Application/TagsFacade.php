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

use Lwt\Database\Connection;
use Lwt\Modules\Tags\Application\UseCases\CreateTag;
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
        $html = '<ul id="texttags" class="respinput">';

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
        $html = '<ul id="texttags" class="respinput">';

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
     * Cleanup orphaned tag links.
     *
     * @return void
     */
    public function cleanupOrphanedLinks(): void
    {
        $this->association->cleanupOrphanedLinks();
    }
}
