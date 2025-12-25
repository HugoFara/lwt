<?php declare(strict_types=1);
/**
 * MySQL Article Repository
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Feed\Infrastructure;

use Lwt\Core\Repository\AbstractRepository;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Feed\Domain\Article;
use Lwt\Modules\Feed\Domain\ArticleRepositoryInterface;

/**
 * MySQL repository for Article entities (feedlinks).
 *
 * Provides database access for article management operations.
 *
 * @extends AbstractRepository<Article>
 *
 * @since 3.0.0
 */
class MySqlArticleRepository extends AbstractRepository implements ArticleRepositoryInterface
{
    /**
     * @var string Table name without prefix
     */
    protected string $tableName = 'feedlinks';

    /**
     * @var string Primary key column
     */
    protected string $primaryKey = 'FlID';

    /**
     * @var array<string, string> Property to column mapping
     */
    protected array $columnMap = [
        'id' => 'FlID',
        'feedId' => 'FlNfID',
        'title' => 'FlTitle',
        'link' => 'FlLink',
        'description' => 'FlDescription',
        'date' => 'FlDate',
        'audio' => 'FlAudio',
        'text' => 'FlText',
    ];

    /**
     * {@inheritdoc}
     */
    protected function mapToEntity(array $row): Article
    {
        return Article::reconstitute(
            (int) $row['FlID'],
            (int) $row['FlNfID'],
            (string) $row['FlTitle'],
            (string) $row['FlLink'],
            (string) ($row['FlDescription'] ?? ''),
            (string) ($row['FlDate'] ?? ''),
            (string) ($row['FlAudio'] ?? ''),
            (string) ($row['FlText'] ?? '')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param Article $entity
     *
     * @return array<string, mixed>
     */
    protected function mapToRow(object $entity): array
    {
        return [
            'FlNfID' => $entity->feedId(),
            'FlTitle' => $entity->title(),
            'FlLink' => $entity->link(),
            'FlDescription' => $entity->description(),
            'FlDate' => $entity->date(),
            'FlAudio' => $entity->audio(),
            'FlText' => $entity->text(),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param Article $entity
     */
    protected function getEntityId(object $entity): int
    {
        return $entity->id() ?? 0;
    }

    /**
     * {@inheritdoc}
     *
     * @param Article $entity
     */
    protected function setEntityId(object $entity, int $id): void
    {
        $entity->setId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?Article
    {
        $row = $this->query()
            ->where($this->primaryKey, '=', $id)
            ->firstPrepared();

        if ($row === null) {
            return null;
        }

        return $this->mapToEntity($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findByFeed(
        int $feedId,
        int $offset = 0,
        int $limit = 50,
        string $orderBy = 'FlDate',
        string $direction = 'DESC'
    ): array {
        $rows = $this->query()
            ->where('FlNfID', '=', $feedId)
            ->orderBy($orderBy, $direction)
            ->limit($limit)
            ->offset($offset)
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = $this->query()
            ->whereIn($this->primaryKey, $ids)
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByFeedsWithStatus(
        array $feedIds,
        int $offset = 0,
        int $limit = 50,
        string $orderBy = 'FlDate',
        string $direction = 'DESC',
        string $search = ''
    ): array {
        if (empty($feedIds)) {
            return [];
        }

        $feedIdList = implode(',', array_map('intval', $feedIds));
        $bindings = [];

        // Build WHERE clause for search
        $searchClause = '';
        if ($search !== '') {
            $searchClause = " AND (FlTitle LIKE ? OR FlDescription LIKE ?)";
            $bindings[] = '%' . $search . '%';
            $bindings[] = '%' . $search . '%';
        }

        // Complex query with LEFT JOINs to texts and archivedtexts
        $sql = "SELECT FlID, FlNfID, FlTitle, FlLink, FlDescription, FlDate, FlAudio, FlText,
                       TxID, AtID
                FROM feedlinks
                LEFT JOIN texts ON TxSourceURI = TRIM(FlLink)"
                . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts')
                . " LEFT JOIN archivedtexts ON AtSourceURI = TRIM(FlLink)"
                . UserScopedQuery::forTablePrepared('archivedtexts', $bindings, 'archivedtexts')
                . " WHERE FlNfID IN ($feedIdList) $searchClause"
                . " ORDER BY $orderBy $direction"
                . " LIMIT $offset, $limit";

        $result = [];

        if (!empty($bindings)) {
            $rows = Connection::preparedFetchAll($sql, $bindings);
        } else {
            $res = Connection::query($sql);
            $rows = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $rows[] = $row;
            }
            mysqli_free_result($res);
        }

        foreach ($rows as $row) {
            $article = $this->mapToEntity($row);
            $textId = isset($row['TxID']) ? (int) $row['TxID'] : null;
            $archivedId = isset($row['AtID']) ? (int) $row['AtID'] : null;

            $result[] = [
                'article' => $article,
                'text_id' => $textId ?: null,
                'archived_id' => $archivedId ?: null,
                'status' => $article->determineStatus($textId, $archivedId),
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function countByFeed(int $feedId, string $search = ''): int
    {
        if ($search !== '') {
            // Use raw SQL for OR condition
            $searchPattern = '%' . $search . '%';
            $bindings = [$feedId, $searchPattern, $searchPattern];
            $sql = "SELECT COUNT(*) as cnt FROM feedlinks
                    WHERE FlNfID = ?
                    AND (FlTitle LIKE ? OR FlDescription LIKE ?)";
            $row = Connection::preparedFetchOne($sql, $bindings);
            return (int) ($row['cnt'] ?? 0);
        }

        return $this->query()
            ->where('FlNfID', '=', $feedId)
            ->countPrepared();
    }

    /**
     * {@inheritdoc}
     */
    public function countByFeeds(array $feedIds, string $search = ''): int
    {
        if (empty($feedIds)) {
            return 0;
        }

        if ($search !== '') {
            // Use raw SQL for OR condition with IN clause
            $feedIdList = implode(',', array_map('intval', $feedIds));
            $searchPattern = '%' . $search . '%';
            $bindings = [$searchPattern, $searchPattern];
            $sql = "SELECT COUNT(*) as cnt FROM feedlinks
                    WHERE FlNfID IN ($feedIdList)
                    AND (FlTitle LIKE ? OR FlDescription LIKE ?)";
            $row = Connection::preparedFetchOne($sql, $bindings);
            return (int) ($row['cnt'] ?? 0);
        }

        return $this->query()
            ->whereIn('FlNfID', $feedIds)
            ->countPrepared();
    }

    /**
     * {@inheritdoc}
     *
     * @param Article $entity
     */
    public function save(object $entity): int
    {
        assert($entity instanceof Article);
        $data = $this->mapToRow($entity);

        if ($entity->isNew()) {
            // Insert
            $id = (int) $this->query()->insertPrepared($data);
            $entity->setId($id);
            return $id;
        }

        // Update - entity must have an ID at this point
        $id = (int) $entity->id();
        $this->query()
            ->where($this->primaryKey, '=', $id)
            ->updatePrepared($data);

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBatch(array $articles, int $feedId): array
    {
        $inserted = 0;
        $duplicates = 0;

        foreach ($articles as $article) {
            // Check for duplicate by title (unique key: FlNfID, FlTitle)
            if ($this->titleExistsForFeed($feedId, $article->title())) {
                $duplicates++;
                continue;
            }

            try {
                $this->save($article);
                $inserted++;
            } catch (\Exception $e) {
                // Likely duplicate key error
                $duplicates++;
            }
        }

        return [
            'inserted' => $inserted,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(object|int $entityOrId): bool
    {
        $id = is_int($entityOrId) ? $entityOrId : $this->getEntityId($entityOrId);
        $deleted = $this->query()
            ->where($this->primaryKey, '=', $id)
            ->delete();

        return $deleted > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByFeed(int $feedId): int
    {
        return $this->query()
            ->where('FlNfID', '=', $feedId)
            ->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByFeeds(array $feedIds): int
    {
        if (empty($feedIds)) {
            return 0;
        }

        return $this->query()
            ->whereIn('FlNfID', $feedIds)
            ->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return $this->query()
            ->whereIn($this->primaryKey, $ids)
            ->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function resetErrorsByFeeds(array $feedIds): int
    {
        if (empty($feedIds)) {
            return 0;
        }

        $feedIdList = implode(',', array_map('intval', $feedIds));

        // Use raw SQL for TRIM() expression
        return (int) Connection::execute(
            "UPDATE feedlinks SET FlLink = TRIM(FlLink)
             WHERE FlNfID IN ($feedIdList)"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function markAsError(string $link): void
    {
        // Add space prefix to mark as error
        $this->query()
            ->where('FlLink', '=', $link)
            ->updatePrepared(['FlLink' => ' ' . $link]);
    }

    /**
     * {@inheritdoc}
     */
    public function titleExistsForFeed(int $feedId, string $title): bool
    {
        return $this->query()
            ->where('FlNfID', '=', $feedId)
            ->where('FlTitle', '=', $title)
            ->existsPrepared();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountPerFeed(array $feedIds = []): array
    {
        $sql = "SELECT FlNfID, COUNT(*) as cnt FROM feedlinks";

        if (!empty($feedIds)) {
            $feedIdList = implode(',', array_map('intval', $feedIds));
            $sql .= " WHERE FlNfID IN ($feedIdList)";
        }

        $sql .= " GROUP BY FlNfID";

        $res = Connection::query($sql);
        $counts = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $counts[(int) $row['FlNfID']] = (int) $row['cnt'];
        }
        mysqli_free_result($res);

        return $counts;
    }
}
