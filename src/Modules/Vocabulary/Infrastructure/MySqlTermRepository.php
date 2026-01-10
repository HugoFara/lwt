<?php declare(strict_types=1);
/**
 * MySQL Term Repository
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Infrastructure;

use DateTimeImmutable;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;

/**
 * MySQL implementation of Term Repository.
 *
 * Provides database access for vocabulary/word management operations.
 * Handles both basic CRUD and term-specific queries.
 *
 * @since 3.0.0
 */
class MySqlTermRepository implements TermRepositoryInterface
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
        'lemma' => 'WoLemma',
        'lemmaLc' => 'WoLemmaLC',
        'status' => 'WoStatus',
        'translation' => 'WoTranslation',
        'sentence' => 'WoSentence',
        'notes' => 'WoNotes',
        'romanization' => 'WoRomanization',
        'wordCount' => 'WoWordCount',
        'createdAt' => 'WoCreated',
        'statusChangedAt' => 'WoStatusChanged',
        'todayScore' => 'WoTodayScore',
        'tomorrowScore' => 'WoTomorrowScore',
        'random' => 'WoRandom',
    ];

    /**
     * Get a query builder for this repository's table.
     *
     * @return QueryBuilder
     */
    protected function query(): QueryBuilder
    {
        return QueryBuilder::table($this->tableName);
    }

    /**
     * Map a database row to a Term entity.
     *
     * @param array<string, mixed> $row Database row
     *
     * @return Term
     */
    protected function mapToEntity(array $row): Term
    {
        return Term::reconstitute(
            (int) $row['WoID'],
            (int) $row['WoLgID'],
            (string) $row['WoText'],
            (string) $row['WoTextLC'],
            isset($row['WoLemma']) && $row['WoLemma'] !== '' ? (string) $row['WoLemma'] : null,
            isset($row['WoLemmaLC']) && $row['WoLemmaLC'] !== '' ? (string) $row['WoLemmaLC'] : null,
            (int) $row['WoStatus'],
            (string) ($row['WoTranslation'] ?? ''),
            (string) ($row['WoSentence'] ?? ''),
            (string) ($row['WoNotes'] ?? ''),
            (string) ($row['WoRomanization'] ?? ''),
            (int) ($row['WoWordCount'] ?? 1),
            $this->parseDateTime(isset($row['WoCreated']) ? (string)$row['WoCreated'] : null),
            $this->parseDateTime(isset($row['WoStatusChanged']) ? (string)$row['WoStatusChanged'] : null),
            (float) ($row['WoTodayScore'] ?? 0.0),
            (float) ($row['WoTomorrowScore'] ?? 0.0),
            (float) ($row['WoRandom'] ?? 0.0)
        );
    }

    /**
     * Map a Term entity to a database row.
     *
     * @param Term $term The term entity
     *
     * @return array<string, mixed> Database column => value pairs
     */
    protected function mapToRow(Term $term): array
    {
        return [
            'WoID' => $term->id()->toInt(),
            'WoLgID' => $term->languageId()->toInt(),
            'WoText' => $term->text(),
            'WoTextLC' => $term->textLowercase(),
            'WoLemma' => $term->lemma(),
            'WoLemmaLC' => $term->lemmaLc(),
            'WoStatus' => $term->status()->toInt(),
            'WoTranslation' => $term->translation(),
            'WoSentence' => $term->sentence(),
            'WoNotes' => $term->notes(),
            'WoRomanization' => $term->romanization(),
            'WoWordCount' => $term->wordCount(),
            'WoCreated' => $term->createdAt()->format('Y-m-d H:i:s'),
            'WoStatusChanged' => $term->statusChangedAt()->format('Y-m-d H:i:s'),
            'WoTodayScore' => $term->todayScore(),
            'WoTomorrowScore' => $term->tomorrowScore(),
            'WoRandom' => $term->random(),
        ];
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
     * {@inheritdoc}
     */
    public function find(int $id): ?Term
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
    public function findAll(): array
    {
        $rows = $this->query()->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save(Term $term): int
    {
        if ($term->id()->isNew()) {
            // Insert new term
            $data = $this->mapToRow($term);
            unset($data['WoID']); // Remove ID for insert

            $id = (int) $this->query()->insertPrepared($data);
            $term->setId(TermId::fromInt($id));
            return $id;
        } else {
            // Update existing term
            $data = $this->mapToRow($term);
            unset($data['WoID']);

            $this->query()
                ->where('WoID', '=', $term->id()->toInt())
                ->updatePrepared($data);
            return $term->id()->toInt();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $id)
            ->deletePrepared();

        return $affected > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(int $id): bool
    {
        return $this->query()
            ->where('WoID', '=', $id)
            ->existsPrepared();
    }

    /**
     * Count terms matching criteria.
     *
     * @param array<string, mixed> $criteria Field => value pairs
     *
     * @return int The count
     */
    public function count(array $criteria = []): int
    {
        $query = $this->query();

        /** @var mixed $value */
        foreach ($criteria as $field => $value) {
            $column = $this->columnMap[$field] ?? $field;
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } elseif ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, '=', $value);
            }
        }

        return $query->countPrepared();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function countByLanguage(int $languageId): int
    {
        return $this->query()
            ->where('WoLgID', '=', $languageId)
            ->countPrepared();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function findIgnored(?int $languageId = null): array
    {
        return $this->findByStatus(TermStatus::IGNORED, $languageId);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function updateTranslation(int $termId, string $translation): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared(['WoTranslation' => $translation]);

        return $affected > 0;
    }

    /**
     * {@inheritdoc}
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
     * Update the notes of a term.
     *
     * @param int    $termId Term ID
     * @param string $notes  New notes
     *
     * @return bool True if updated
     */
    public function updateNotes(int $termId, string $notes): bool
    {
        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared(['WoNotes' => $notes]);

        return $affected > 0;
    }

    /**
     * Update the lemma (base form) of a term.
     *
     * @param int         $termId Term ID
     * @param string|null $lemma  New lemma (null to clear)
     *
     * @return bool True if updated
     */
    public function updateLemma(int $termId, ?string $lemma): bool
    {
        $lemmaLc = $lemma !== null && $lemma !== '' ? mb_strtolower($lemma, 'UTF-8') : null;

        $affected = $this->query()
            ->where('WoID', '=', $termId)
            ->updatePrepared([
                'WoLemma' => $lemma,
                'WoLemmaLC' => $lemmaLc,
            ]);

        return $affected > 0;
    }

    /**
     * Find all terms sharing a lemma in a language (word family).
     *
     * @param int    $languageId Language ID
     * @param string $lemmaLc    Lowercase lemma
     *
     * @return Term[]
     */
    public function findByLemma(int $languageId, string $lemmaLc): array
    {
        $rows = $this->query()
            ->where('WoLgID', '=', $languageId)
            ->where('WoLemmaLC', '=', $lemmaLc)
            ->orderBy('WoWordCount')
            ->orderBy('WoText')
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
