<?php declare(strict_types=1);
/**
 * Build Text Filters Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Text\Application\UseCases;

use Lwt\Database\Connection;

/**
 * Use case for building text query filters.
 *
 * Constructs WHERE and HAVING clauses for text list queries
 * based on search criteria and tag filters.
 *
 * @since 3.0.0
 */
class BuildTextFilters
{
    /**
     * Build WHERE clause for text query filtering.
     *
     * @param string $query     Search query string
     * @param string $queryMode Query mode ('title,text', 'title', 'text')
     * @param string $regexMode Regex mode ('' for LIKE, 'r' for RLIKE)
     * @param string $tablePrefix Column prefix ('Tx' for texts, 'At' for archived)
     *
     * @return array{clause: string, params: array} SQL WHERE clause and parameters
     */
    public function buildQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode,
        string $tablePrefix = 'Tx'
    ): array {
        if ($query === '') {
            return ['clause' => '', 'params' => []];
        }

        $titleCol = $tablePrefix . 'Title';
        $textCol = $tablePrefix . 'Text';

        $searchValue = $regexMode === ''
            ? str_replace("*", "%", mb_strtolower($query, 'UTF-8'))
            : $query;
        $operator = $regexMode . 'LIKE';

        switch ($queryMode) {
            case 'title,text':
                return [
                    'clause' => " AND ({$titleCol} {$operator} ? OR {$textCol} {$operator} ?)",
                    'params' => [$searchValue, $searchValue]
                ];
            case 'title':
                return [
                    'clause' => " AND ({$titleCol} {$operator} ?)",
                    'params' => [$searchValue]
                ];
            case 'text':
                return [
                    'clause' => " AND ({$textCol} {$operator} ?)",
                    'params' => [$searchValue]
                ];
            default:
                return [
                    'clause' => " AND ({$titleCol} {$operator} ? OR {$textCol} {$operator} ?)",
                    'params' => [$searchValue, $searchValue]
                ];
        }
    }

    /**
     * Build WHERE clause for archived text query filtering.
     *
     * @param string $query     Search query string
     * @param string $queryMode Query mode
     * @param string $regexMode Regex mode
     *
     * @return array{clause: string, params: array}
     */
    public function buildArchivedQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode
    ): array {
        return $this->buildQueryWhereClause($query, $queryMode, $regexMode, 'At');
    }

    /**
     * Build HAVING clause for tag filtering.
     *
     * @param string|int $tag1       First tag filter
     * @param string|int $tag2       Second tag filter
     * @param string     $tag12      AND/OR operator
     * @param string     $tagIdCol   Tag ID column (AgT2ID for archived, TtT2ID for active)
     *
     * @return string SQL HAVING clause
     */
    public function buildTagHavingClause(
        $tag1,
        $tag2,
        string $tag12,
        string $tagIdCol = 'TtT2ID'
    ): string {
        if ($tag1 === '' && $tag2 === '') {
            return '';
        }

        $whTag1 = null;
        $whTag2 = null;

        if ($tag1 !== '') {
            if ($tag1 == -1) {
                $whTag1 = "GROUP_CONCAT({$tagIdCol}) IS NULL";
            } else {
                $whTag1 = "CONCAT('/', GROUP_CONCAT({$tagIdCol} SEPARATOR '/'), '/') LIKE '%/{$tag1}/%'";
            }
        }

        if ($tag2 !== '') {
            if ($tag2 == -1) {
                $whTag2 = "GROUP_CONCAT({$tagIdCol}) IS NULL";
            } else {
                $whTag2 = "CONCAT('/', GROUP_CONCAT({$tagIdCol} SEPARATOR '/'), '/') LIKE '%/{$tag2}/%'";
            }
        }

        if ($tag1 !== '' && $tag2 === '') {
            return " HAVING ({$whTag1})";
        }
        if ($tag2 !== '' && $tag1 === '') {
            return " HAVING ({$whTag2})";
        }

        $operator = $tag12 ? 'AND' : 'OR';
        return " HAVING (({$whTag1}) {$operator} ({$whTag2}))";
    }

    /**
     * Build HAVING clause for archived text tag filtering.
     *
     * @param string|int $tag1  First tag filter
     * @param string|int $tag2  Second tag filter
     * @param string     $tag12 AND/OR operator
     *
     * @return string SQL HAVING clause
     */
    public function buildArchivedTagHavingClause($tag1, $tag2, string $tag12): string
    {
        return $this->buildTagHavingClause($tag1, $tag2, $tag12, 'AgT2ID');
    }

    /**
     * Build HAVING clause for active text tag filtering.
     *
     * @param string|int $tag1  First tag filter
     * @param string|int $tag2  Second tag filter
     * @param string     $tag12 AND/OR operator
     *
     * @return string SQL HAVING clause
     */
    public function buildTextTagHavingClause($tag1, $tag2, string $tag12): string
    {
        return $this->buildTagHavingClause($tag1, $tag2, $tag12, 'TtT2ID');
    }

    /**
     * Validate regex query (test if regex is valid).
     *
     * @param string $query     Query string
     * @param string $regexMode Regex mode
     *
     * @return bool True if valid, false if invalid
     */
    public function validateRegexQuery(string $query, string $regexMode): bool
    {
        if ($query === '' || $regexMode === '') {
            return true;
        }

        try {
            $stmt = Connection::prepare('SELECT "test" RLIKE ?');
            $stmt->bind('s', $query)->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
