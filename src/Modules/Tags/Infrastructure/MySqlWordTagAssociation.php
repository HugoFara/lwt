<?php declare(strict_types=1);
/**
 * MySQL Word Tag Association
 *
 * Infrastructure adapter for word-tag associations using MySQL.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Tags\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Tags\Infrastructure;

use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Modules\Tags\Domain\TagAssociationInterface;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;

/**
 * MySQL implementation of TagAssociationInterface for word-tag links.
 *
 * Operates on the 'wordtags' junction table.
 *
 * @since 3.0.0
 */
class MySqlWordTagAssociation implements TagAssociationInterface
{
    private const TABLE_NAME = 'wordtags';
    private const ITEM_COLUMN = 'WtWoID';
    private const TAG_COLUMN = 'WtTgID';

    private TagRepositoryInterface $tagRepository;

    /**
     * Constructor.
     *
     * @param TagRepositoryInterface $tagRepository Term tag repository
     */
    public function __construct(TagRepositoryInterface $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Get a query builder for this association's table.
     *
     * @return QueryBuilder
     */
    private function query(): QueryBuilder
    {
        return QueryBuilder::table(self::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getTagIdsForItem(int $itemId): array
    {
        $rows = $this->query()
            ->select([self::TAG_COLUMN])
            ->where(self::ITEM_COLUMN, '=', $itemId)
            ->getPrepared();

        return array_column($rows, self::TAG_COLUMN);
    }

    /**
     * {@inheritdoc}
     */
    public function getTagTextsForItem(int $itemId): array
    {
        $rows = Connection::preparedFetchAll(
            'SELECT TgText FROM wordtags, tags WHERE TgID = WtTgID AND WtWoID = ? ORDER BY TgText',
            [$itemId]
        );

        return array_column($rows, 'TgText');
    }

    /**
     * {@inheritdoc}
     */
    public function setTagsForItem(int $itemId, array $tagIds): void
    {
        // Delete existing associations
        $this->clearTagsForItem($itemId);

        // Insert new associations
        foreach ($tagIds as $tagId) {
            $this->addTag($itemId, (int) $tagId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setTagsByName(int $itemId, array $tagNames): void
    {
        // Delete existing associations
        $this->clearTagsForItem($itemId);

        // Create/get tags and associate them
        foreach ($tagNames as $tagName) {
            $tagName = trim((string) $tagName);
            if ($tagName === '') {
                continue;
            }

            // Get or create the tag
            $tagId = $this->tagRepository->getOrCreate($tagName);

            // Associate using INSERT...SELECT to handle concurrent inserts
            Connection::preparedExecute(
                'INSERT IGNORE INTO wordtags (WtWoID, WtTgID)
                SELECT ?, TgID FROM tags WHERE TgText = ?',
                [$itemId, $tagName]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addTag(int $itemId, int $tagId): bool
    {
        if ($this->hasTag($itemId, $tagId)) {
            return false;
        }

        $this->query()->insertPrepared([
            self::ITEM_COLUMN => $itemId,
            self::TAG_COLUMN => $tagId,
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTag(int $itemId, int $tagId): bool
    {
        $affected = $this->query()
            ->where(self::ITEM_COLUMN, '=', $itemId)
            ->where(self::TAG_COLUMN, '=', $tagId)
            ->deletePrepared();

        return $affected > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function addTagToItems(int $tagId, array $itemIds): int
    {
        if (empty($itemIds)) {
            return 0;
        }

        $count = 0;
        foreach ($itemIds as $itemId) {
            if ($this->addTag((int) $itemId, $tagId)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTagFromItems(int $tagId, array $itemIds): int
    {
        if (empty($itemIds)) {
            return 0;
        }

        $itemIds = array_map('intval', $itemIds);
        return $this->query()
            ->where(self::TAG_COLUMN, '=', $tagId)
            ->whereIn(self::ITEM_COLUMN, $itemIds)
            ->deletePrepared();
    }

    /**
     * {@inheritdoc}
     */
    public function clearTagsForItem(int $itemId): int
    {
        return $this->query()
            ->where(self::ITEM_COLUMN, '=', $itemId)
            ->deletePrepared();
    }

    /**
     * {@inheritdoc}
     */
    public function clearItemsForTag(int $tagId): int
    {
        return $this->query()
            ->where(self::TAG_COLUMN, '=', $tagId)
            ->deletePrepared();
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupOrphanedLinks(): int
    {
        // Delete wordtags where the tag no longer exists
        return Connection::preparedExecute(
            'DELETE FROM wordtags WHERE WtTgID NOT IN (SELECT TgID FROM tags)',
            []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getItemCount(int $tagId): int
    {
        return $this->query()
            ->where(self::TAG_COLUMN, '=', $tagId)
            ->count();
    }

    /**
     * {@inheritdoc}
     */
    public function hasTag(int $itemId, int $tagId): bool
    {
        return $this->query()
            ->where(self::ITEM_COLUMN, '=', $itemId)
            ->where(self::TAG_COLUMN, '=', $tagId)
            ->existsPrepared();
    }
}
