<?php declare(strict_types=1);
/**
 * MySQL Statistics Repository
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Infrastructure;

use Lwt\Database\QueryBuilder;

/**
 * MySQL repository for statistics queries.
 *
 * Provides database access for learning statistics.
 *
 * @since 3.0.0
 */
class MySqlStatisticsRepository
{
    /**
     * Get term counts grouped by language and status.
     *
     * @return array<string, array<int, int>> Term counts indexed by language ID and status
     */
    public function getTermCountsByLanguageAndStatus(): array
    {
        $results = QueryBuilder::table('words')
            ->selectRaw('WoLgID, WoStatus, COUNT(*) AS term_count')
            ->groupBy(['WoLgID', 'WoStatus'])
            ->getPrepared();

        $termStat = [];
        foreach ($results as $record) {
            $lgId = (string) $record['WoLgID'];
            $status = (int) $record['WoStatus'];
            $termStat[$lgId][$status] = (int) $record['term_count'];
        }

        return $termStat;
    }

    /**
     * Get list of languages with IDs and names.
     *
     * @return array<int, array{LgID: int, LgName: string}> Language records
     */
    public function getLanguageList(): array
    {
        return QueryBuilder::table('languages')
            ->select(['LgID', 'LgName'])
            ->where('LgName', '<>', '')
            ->orderBy('LgName')
            ->getPrepared();
    }

    /**
     * Get terms created grouped by language and days ago.
     *
     * @return array<int, array<int, int>> Terms by language ID and days since creation
     */
    public function getTermsCreatedByDay(): array
    {
        $results = QueryBuilder::table('words')
            ->select([
                'WoLgID',
                'TO_DAYS(curdate()) - TO_DAYS(cast(WoCreated as date)) AS Created',
                'count(WoID) as value'
            ])
            ->whereIn('WoStatus', [1, 2, 3, 4, 5, 99])
            ->groupBy(['WoLgID', 'Created'])
            ->getPrepared();

        $termCreated = [];
        foreach ($results as $record) {
            $termCreated[$record['WoLgID']][$record['Created']] = $record['value'];
        }

        return $termCreated;
    }

    /**
     * Get term activity grouped by language and days ago.
     *
     * @return array{active: array<int, array<int, int>>, known: array<int, array<int, int>>}
     */
    public function getTermActivityByDay(): array
    {
        $results = QueryBuilder::table('words')
            ->select([
                'WoLgID',
                'WoStatus',
                'TO_DAYS(curdate()) - TO_DAYS(cast(WoStatusChanged as date)) AS Changed',
                'count(WoID) as value'
            ])
            ->groupBy(['WoLgID', 'WoStatus', 'WoStatusChanged'])
            ->getPrepared();

        $termActive = [];
        $termKnown = [];

        foreach ($results as $record) {
            if (!empty($record['WoStatus'])) {
                $lgId = $record['WoLgID'];
                $changed = $record['Changed'];
                $value = (int) $record['value'];

                if ($record['WoStatus'] == 5 || $record['WoStatus'] == 99) {
                    if (!isset($termKnown[$lgId][$changed])) {
                        $termKnown[$lgId][$changed] = 0;
                    }
                    $termKnown[$lgId][$changed] += $value;

                    if (!isset($termActive[$lgId][$changed])) {
                        $termActive[$lgId][$changed] = 0;
                    }
                    $termActive[$lgId][$changed] += $value;
                } elseif ($record['WoStatus'] > 0 && $record['WoStatus'] < 5) {
                    if (!isset($termActive[$lgId][$changed])) {
                        $termActive[$lgId][$changed] = 0;
                    }
                    $termActive[$lgId][$changed] += $value;
                }
            }
        }

        return [
            'active' => $termActive,
            'known' => $termKnown
        ];
    }

    /**
     * Get language count.
     *
     * @return int Number of languages
     */
    public function getLanguageCount(): int
    {
        return QueryBuilder::table('languages')->count();
    }
}
