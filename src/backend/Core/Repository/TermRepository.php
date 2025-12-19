<?php declare(strict_types=1);
/**
 * Term Repository
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Repository
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Repository;

use DateTimeImmutable;
use Lwt\Core\Entity\Term;
use Lwt\Core\Entity\ValueObject\TermId;
use Lwt\Core\Entity\ValueObject\TermStatus;
use Lwt\Database\Connection;

/**
 * Repository for Term entities.
 *
 * Provides database access for vocabulary/word management operations.
 * Handles both basic CRUD and term-specific queries.
 *
 * @extends AbstractRepository<Term>
 *
 * @since 3.0.0
 */
class TermRepository extends AbstractRepository
{
    /**
     * @var string Table name without prefix
     */
    protected string $tableName = 'words';

    /**
     * @var string Primary key column
     */
    protected string $primaryKey = 'WoID';

    /**
     * @var array<string, string> Property to column mapping
     */
    protected array $columnMap = [
        'id' => 'WoID',
        'languageId' => 'WoLgID',
        'text' => 'WoText',
        'textLowercase' => 'WoTextLC',
        'status' => 'WoStatus',
        'translation' => 'WoTranslation',
        'sentence' => 'WoSentence',
        'romanization' => 'WoRomanization',
        'wordCount' => 'WoWordCount',
        'createdAt' => 'WoCreated',
        'statusChangedAt' => 'WoStatusChanged',
        'todayScore' => 'WoTodayScore',
        'tomorrowScore' => 'WoTomorrowScore',
        'random' => 'WoRandom',
    ];

    /**
     * {@inheritdoc}
     */
    protected function mapToEntity(array $row): Term
    {
        return Term::reconstitute(
            (int) $row['WoID'],
            (int) $row['WoLgID'],
            (string) $row['WoText'],
            (string) $row['WoTextLC'],
            (int) $row['WoStatus'],
            (string) ($row['WoTranslation'] ?? ''),
            (string) ($row['WoSentence'] ?? ''),
            (string) ($row['WoRomanization'] ?? ''),
            (int) ($row['WoWordCount'] ?? 1),
            $this->parseDateTime($row['WoCreated'] ?? null),
            $this->parseDateTime($row['WoStatusChanged'] ?? null),
            (float) ($row['WoTodayScore'] ?? 0.0),
            (float) ($row['WoTomorrowScore'] ?? 0.0),
            (float) ($row['WoRandom'] ?? 0.0)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param Term $entity
     *
     * @return array<string, mixed>
     */
    protected function mapToRow(object $entity): array
    {
        return [
            'WoID' => $entity->id()->toInt(),
            'WoLgID' => $entity->languageId()->toInt(),
            'WoText' => $entity->text(),
            'WoTextLC' => $entity->textLowercase(),
            'WoStatus' => $entity->status()->toInt(),
            'WoTranslation' => $entity->translation(),
            'WoSentence' => $entity->sentence(),
            'WoRomanization' => $entity->romanization(),
            'WoWordCount' => $entity->wordCount(),
            'WoCreated' => $entity->createdAt()->format('Y-m-d H:i:s'),
            'WoStatusChanged' => $entity->statusChangedAt()->format('Y-m-d H:i:s'),
            'WoTodayScore' => $entity->todayScore(),
            'WoTomorrowScore' => $entity->tomorrowScore(),
            'WoRandom' => $entity->random(),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param Term $entity
     */
    protected function getEntityId(object $entity): int
    {
        return $entity->id()->toInt();
    }

    /**
     * {@inheritdoc}
     *
     * @param Term $entity
     */
    protected function setEntityId(object $entity, int $id): void
    {
        $entity->setId(TermId::fromInt($id));
    }

    /**
     * Parse a datetime string into DateTimeImmutable.
     *
     * @param string|null $datetime The datetime string
     *
     * @return DateTimeImmutable
     */
    private function parseDateTime(?string $datetime): DateTimeImmutable
    {
        if ($datetime === null || $datetime === '' || $datetime === '0000-00-00 00:00:00') {
            return new DateTimeImmutable();
        }
        return new DateTimeImmutable($datetime);
    }

    /**
     * Find all terms for a specific language.
     *
     * @param int         $languageId Language ID
     * @param string|null $orderBy    Column to order by (default: 'WoText')
     * @param string      $direction  Sort direction (default: 'ASC')
     *
     * @return Term[]
     */
    public function findByLanguage(
        int $languageId,
        ?string $orderBy = 'WoText',
        string $direction = 'ASC'
    ): array {
        $rows = $this->query()
            ->where('WoLgID', '=', $languageId)
            ->orderBy($orderBy ?? 'WoText', $direction)
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find a term by lowercase text within a language.
     *
     * @param int    $languageId Language ID
     * @param string $textLc     Lowercase term text
     *
     * @return Term|null
     */
    public function findByTextLc(int $languageId, string $textLc): ?Term
    {
        $row = $this->query()
            ->where('WoLgID', '=', $languageId)
            ->where('WoTextLC', '=', $textLc)
            ->firstPrepared();

        return $row !== null ? $this->mapToEntity($row) : null;
    }

    /**
     * Check if a term exists within a language.
     *
     * @param int      $languageId Language ID
     * @param string   $textLc     Lowercase term text
     * @param int|null $excludeId  Term ID to exclude (for updates)
     *
     * @return bool
     */
    public function termExists(int $languageId, string $textLc, ?int $excludeId = null): bool
    {
        $query = $this->query()
            ->where('WoLgID', '=', $languageId)
            ->where('WoTextLC', '=', $textLc);

        if ($excludeId !== null) {
            $query->where('WoID', '!=', $excludeId);
        }

        return $query->existsPrepared();
    }

    /**
     * Count terms for a specific language.
     *
     * @param int $languageId Language ID
     *
     * @return int
     */
    public function countByLanguage(int $languageId): int
    {
        return $this->query()
            ->where('WoLgID', '=', $languageId)
            ->countPrepared();
    }

    /**
     * Find terms by status.
     *
     * @param int      $status     Status value (1-5, 98, 99)
     * @param int|null $languageId Language ID (null for all)
     *
     * @return Term[]
     */
    public function findByStatus(int $status, ?int $languageId = null): array
    {
        $query = $this->query()
            ->where('WoStatus', '=', $status)
            ->orderBy('WoText');

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find terms in learning stages (status 1-4).
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return Term[]
     */
    public function findLearning(?int $languageId = null): array
    {
        $query = $this->query()
            ->whereIn('WoStatus', [
                TermStatus::NEW,
                TermStatus::LEARNING_2,
                TermStatus::LEARNING_3,
                TermStatus::LEARNING_4
            ])
            ->orderBy('WoText');

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find terms that are known (status 5 or 99).
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return Term[]
     */
    public function findKnown(?int $languageId = null): array
    {
        $query = $this->query()
            ->whereIn('WoStatus', [TermStatus::LEARNED, TermStatus::WELL_KNOWN])
            ->orderBy('WoText');

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find ignored terms (status 98).
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return Term[]
     */
    public function findIgnored(?int $languageId = null): array
    {
        return $this->findByStatus(TermStatus::IGNORED, $languageId);
    }

    /**
     * Find multi-word expressions (word count > 1).
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return Term[]
     */
    public function findMultiWord(?int $languageId = null): array
    {
        $query = $this->query()
            ->where('WoWordCount', '>', 1)
            ->orderBy('WoText');

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find single-word terms (word count = 1).
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return Term[]
     */
    public function findSingleWord(?int $languageId = null): array
    {
        $query = $this->query()
            ->where('WoWordCount', '=', 1)
            ->orderBy('WoText');

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Update the status of a term.
     *
     * @param int $termId Term ID
     * @param int $status New status value
     *
     * @return bool True if updated
     */
    public function updateStatus(int $termId, int $status): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared([
                'WoStatus' => $status,
                'WoStatusChanged' => date('Y-m-d H:i:s'),
            ]);

        return $affected > 0;
    }

    /**
     * Update the translation of a term.
     *
     * @param int    $termId      Term ID
     * @param string $translation New translation
     *
     * @return bool True if updated
     */
    public function updateTranslation(int $termId, string $translation): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared(['WoTranslation' => $translation]);

        return $affected > 0;
    }

    /**
     * Update the romanization of a term.
     *
     * @param int    $termId       Term ID
     * @param string $romanization New romanization
     *
     * @return bool True if updated
     */
    public function updateRomanization(int $termId, string $romanization): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared(['WoRomanization' => $romanization]);

        return $affected > 0;
    }

    /**
     * Update the example sentence of a term.
     *
     * @param int    $termId   Term ID
     * @param string $sentence New sentence
     *
     * @return bool True if updated
     */
    public function updateSentence(int $termId, string $sentence): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared(['WoSentence' => $sentence]);

        return $affected > 0;
    }

    /**
     * Update review scores for a term.
     *
     * @param int   $termId        Term ID
     * @param float $todayScore    Today's score
     * @param float $tomorrowScore Tomorrow's score
     *
     * @return bool True if updated
     */
    public function updateScores(int $termId, float $todayScore, float $tomorrowScore): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared([
                'WoTodayScore' => $todayScore,
                'WoTomorrowScore' => $tomorrowScore,
            ]);

        return $affected > 0;
    }

    /**
     * Get terms formatted for select dropdown options.
     *
     * @param int $languageId    Language ID (0 for all languages)
     * @param int $maxNameLength Maximum text length before truncation
     *
     * @return array<int, array{id: int, text: string, language_id: int}>
     */
    public function getForSelect(int $languageId = 0, int $maxNameLength = 40): array
    {
        $query = $this->query()
            ->select(['WoID', 'WoText', 'WoLgID'])
            ->orderBy('WoText');

        if ($languageId > 0) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();
        $result = [];

        foreach ($rows as $row) {
            $text = (string) $row['WoText'];
            if (mb_strlen($text, 'UTF-8') > $maxNameLength) {
                $text = mb_substr($text, 0, $maxNameLength, 'UTF-8') . '...';
            }
            $result[] = [
                'id' => (int) $row['WoID'],
                'text' => $text,
                'language_id' => (int) $row['WoLgID'],
            ];
        }

        return $result;
    }

    /**
     * Get basic term info (minimal data for lists).
     *
     * @param int $termId Term ID
     *
     * @return array{id: int, text: string, language_id: int, status: int, has_translation: bool}|null
     */
    public function getBasicInfo(int $termId): ?array
    {
        $row = $this->query()
            ->select([
                'WoID',
                'WoText',
                'WoLgID',
                'WoStatus',
                'WoTranslation',
            ])
            ->where('WoID', '=', $termId)
            ->firstPrepared();

        if ($row === null) {
            return null;
        }

        $translation = (string) ($row['WoTranslation'] ?? '');

        return [
            'id' => (int) $row['WoID'],
            'text' => (string) $row['WoText'],
            'language_id' => (int) $row['WoLgID'],
            'status' => (int) $row['WoStatus'],
            'has_translation' => $translation !== '' && $translation !== '*',
        ];
    }

    /**
     * Get terms with pagination.
     *
     * @param int    $languageId Language ID (0 for all)
     * @param int    $page       Page number (1-based)
     * @param int    $perPage    Items per page
     * @param string $orderBy    Column to order by
     * @param string $direction  Sort direction
     *
     * @return array{items: Term[], total: int, page: int, per_page: int, total_pages: int}
     */
    public function findPaginated(
        int $languageId = 0,
        int $page = 1,
        int $perPage = 20,
        string $orderBy = 'WoText',
        string $direction = 'ASC'
    ): array {
        $query = $this->query();

        if ($languageId > 0) {
            $query->where('WoLgID', '=', $languageId);
        }

        $total = (clone $query)->countPrepared();
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

        // Ensure page is within bounds
        $page = max(1, min($page, max(1, $totalPages)));
        $offset = ($page - 1) * $perPage;

        $rows = $query
            ->orderBy($orderBy, $direction)
            ->limit($perPage)
            ->offset($offset)
            ->getPrepared();

        $items = array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Search terms by text.
     *
     * @param string   $query      Search query
     * @param int|null $languageId Language ID (null for all)
     * @param int      $limit      Maximum results
     *
     * @return Term[]
     */
    public function searchByText(string $query, ?int $languageId = null, int $limit = 50): array
    {
        $searchPattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';

        $dbQuery = $this->query()
            ->where('WoText', 'LIKE', $searchPattern)
            ->orderBy('WoText')
            ->limit($limit);

        if ($languageId !== null) {
            $dbQuery->where('WoLgID', '=', $languageId);
        }

        $rows = $dbQuery->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Search terms by translation.
     *
     * @param string   $query      Search query
     * @param int|null $languageId Language ID (null for all)
     * @param int      $limit      Maximum results
     *
     * @return Term[]
     */
    public function searchByTranslation(string $query, ?int $languageId = null, int $limit = 50): array
    {
        $searchPattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';

        $dbQuery = $this->query()
            ->where('WoTranslation', 'LIKE', $searchPattern)
            ->orderBy('WoText')
            ->limit($limit);

        if ($languageId !== null) {
            $dbQuery->where('WoLgID', '=', $languageId);
        }

        $rows = $dbQuery->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Get language IDs that have terms.
     *
     * @return int[] Array of language IDs
     */
    public function getLanguagesWithTerms(): array
    {
        $rows = $this->query()
            ->select('DISTINCT WoLgID')
            ->getPrepared();

        return array_map(
            fn(array $row) => (int) $row['WoLgID'],
            $rows
        );
    }

    /**
     * Get statistics for terms.
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return array{total: int, learning: int, known: int, ignored: int, multi_word: int}
     */
    public function getStatistics(?int $languageId = null): array
    {
        $baseQuery = $this->query();
        if ($languageId !== null) {
            $baseQuery->where('WoLgID', '=', $languageId);
        }

        $total = (clone $baseQuery)->countPrepared();

        $learning = (clone $baseQuery)
            ->whereIn('WoStatus', [
                TermStatus::NEW,
                TermStatus::LEARNING_2,
                TermStatus::LEARNING_3,
                TermStatus::LEARNING_4
            ])
            ->countPrepared();

        $known = (clone $baseQuery)
            ->whereIn('WoStatus', [TermStatus::LEARNED, TermStatus::WELL_KNOWN])
            ->countPrepared();

        $ignored = (clone $baseQuery)
            ->where('WoStatus', '=', TermStatus::IGNORED)
            ->countPrepared();

        $multiWord = (clone $baseQuery)
            ->where('WoWordCount', '>', 1)
            ->countPrepared();

        return [
            'total' => $total,
            'learning' => $learning,
            'known' => $known,
            'ignored' => $ignored,
            'multi_word' => $multiWord,
        ];
    }

    /**
     * Get status distribution counts.
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return array<int, int> Status value => count
     */
    public function getStatusDistribution(?int $languageId = null): array
    {
        $baseQuery = $this->query();
        if ($languageId !== null) {
            $baseQuery->where('WoLgID', '=', $languageId);
        }

        $statuses = [
            TermStatus::NEW,
            TermStatus::LEARNING_2,
            TermStatus::LEARNING_3,
            TermStatus::LEARNING_4,
            TermStatus::LEARNED,
            TermStatus::IGNORED,
            TermStatus::WELL_KNOWN,
        ];

        $distribution = [];
        foreach ($statuses as $status) {
            $distribution[$status] = (clone $baseQuery)
                ->where('WoStatus', '=', $status)
                ->countPrepared();
        }

        return $distribution;
    }

    /**
     * Find terms needing review (based on score thresholds).
     *
     * @param int|null $languageId    Language ID (null for all)
     * @param float    $scoreThreshold Score threshold for today
     * @param int      $limit         Maximum results
     *
     * @return Term[]
     */
    public function findForReview(
        ?int $languageId = null,
        float $scoreThreshold = 0.0,
        int $limit = 100
    ): array {
        $query = $this->query()
            ->whereIn('WoStatus', [
                TermStatus::NEW,
                TermStatus::LEARNING_2,
                TermStatus::LEARNING_3,
                TermStatus::LEARNING_4,
                TermStatus::LEARNED
            ])
            ->where('WoTodayScore', '<=', $scoreThreshold)
            ->orderBy('WoTodayScore', 'ASC')
            ->orderBy('WoRandom', 'ASC')
            ->limit($limit);

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find recently added terms.
     *
     * @param int|null $languageId Language ID (null for all)
     * @param int      $limit      Maximum results
     *
     * @return Term[]
     */
    public function findRecent(?int $languageId = null, int $limit = 50): array
    {
        $query = $this->query()
            ->orderBy('WoCreated', 'DESC')
            ->limit($limit);

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find terms with status changed recently.
     *
     * @param int|null $languageId Language ID (null for all)
     * @param int      $days       Number of days to look back
     * @param int      $limit      Maximum results
     *
     * @return Term[]
     */
    public function findRecentlyChanged(
        ?int $languageId = null,
        int $days = 7,
        int $limit = 50
    ): array {
        $sinceDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $query = $this->query()
            ->where('WoStatusChanged', '>=', $sinceDate)
            ->orderBy('WoStatusChanged', 'DESC')
            ->limit($limit);

        if ($languageId !== null) {
            $query->where('WoLgID', '=', $languageId);
        }

        $rows = $query->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Delete multiple terms by IDs.
     *
     * @param int[] $termIds Array of term IDs
     *
     * @return int Number of deleted terms
     */
    public function deleteMultiple(array $termIds): int
    {
        if (empty($termIds)) {
            return 0;
        }

        return $this->query()
            ->whereIn('WoID', array_map('intval', $termIds))
            ->deletePrepared();
    }

    /**
     * Update status for multiple terms.
     *
     * @param int[] $termIds Array of term IDs
     * @param int   $status  New status value
     *
     * @return int Number of updated terms
     */
    public function updateStatusMultiple(array $termIds, int $status): int
    {
        if (empty($termIds)) {
            return 0;
        }

        return $this->query()
            ->whereIn('WoID', array_map('intval', $termIds))
            ->updatePrepared([
                'WoStatus' => $status,
                'WoStatusChanged' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Find terms without translation.
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return Term[]
     */
    public function findWithoutTranslation(?int $languageId = null): array
    {
        // Use raw SQL for OR condition on translation (empty or '*')
        $rows = Connection::preparedFetchAll(
            "SELECT * FROM words WHERE (WoTranslation = '' OR WoTranslation = '*')"
            . ($languageId !== null ? " AND WoLgID = ?" : "")
            . " ORDER BY WoText",
            $languageId !== null ? [$languageId] : []
        );

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Get term count by word count.
     *
     * @param int|null $languageId Language ID (null for all)
     *
     * @return array<int, int> Word count => term count
     */
    public function getWordCountDistribution(?int $languageId = null): array
    {
        $sql = "SELECT WoWordCount, COUNT(*) as cnt FROM words";
        $params = [];

        if ($languageId !== null) {
            $sql .= " WHERE WoLgID = ?";
            $params[] = $languageId;
        }

        $sql .= " GROUP BY WoWordCount ORDER BY WoWordCount";

        $rows = Connection::preparedFetchAll($sql, $params);

        $distribution = [];
        foreach ($rows as $row) {
            $distribution[(int) $row['WoWordCount']] = (int) $row['cnt'];
        }

        return $distribution;
    }
}
