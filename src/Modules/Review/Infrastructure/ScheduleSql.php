<?php

declare(strict_types=1);

namespace Lwt\Modules\Review\Infrastructure;

use Lwt\Modules\Review\Domain\Scheduling\LegacyStatusSeed;

/**
 * SQL for reading a term's due date from its FSRS schedule.
 *
 * `term_schedule` rows are seeded lazily, on a term's first graded review, so
 * on the day phase 2b ships almost every term still has no row. Joining the
 * table plainly would empty the review queue. Instead a missing row falls back
 * to the due date {@see LegacyStatusSeed} would have seeded it with — the
 * status interval counted from `WoStatusChanged` — so an ungraded term keeps
 * the schedule it already had, and moves onto real FSRS the first time it is
 * graded.
 *
 * That fallback reproduces the retired `WoTodayScore < 0` test to within a day.
 * The legacy formula rounded before comparing, which pushed status 2 to day 3
 * (interval 2) and status 5 to day 72 (interval 71); statuses 1, 3 and 4 agree
 * exactly. Those two terms therefore come up one day earlier than they used to,
 * once. Matching the old rounding here instead would only move the discrepancy
 * to the term's first graded review, when the real seed is written — better to
 * agree with the seed table than with a formula being removed.
 *
 * A correlated subquery rather than a join: three of the five review
 * projections in {@see \Lwt\Modules\Review\Domain\ReviewConfiguration} build a
 * comma join or arbitrary caller SQL, and a LEFT JOIN cannot see `words` from
 * inside one of those without reordering the FROM clause. The subquery is a
 * primary-key lookup and reads the same in every projection.
 */
final class ScheduleSql
{
    /**
     * When a term next falls due, whether or not it has been graded.
     *
     * Yields NULL for statuses that are never scheduled (98 ignored, 99 well
     * known), which drops them from any comparison — the review queries filter
     * to statuses 1-5 anyway.
     *
     * @return string SQL expression usable wherever `words` is in scope
     */
    public static function effectiveDue(): string
    {
        $cases = '';
        foreach (LegacyStatusSeed::stabilityByStatus() as $status => $stability) {
            $cases .= ' WHEN ' . $status . ' THEN ' . (int) round($stability);
        }

        return '(SELECT COALESCE('
            . '(SELECT ts.TsDue FROM term_schedule ts WHERE ts.TsWoID = WoID),'
            . ' DATE_ADD(WoStatusChanged, INTERVAL CASE WoStatus' . $cases . ' END DAY)'
            . '))';
    }

    /**
     * Whether a term is due now.
     *
     * @return string SQL predicate
     */
    public static function isDue(): string
    {
        return self::effectiveDue() . ' <= NOW()';
    }

    /**
     * Whether a term is due by the end of tomorrow.
     *
     * Replaces the `WoTomorrowScore < 0` test, which asked the same question of
     * the legacy formula one day ahead.
     *
     * @return string SQL predicate
     */
    public static function isDueTomorrow(): string
    {
        return self::effectiveDue() . ' <= DATE_ADD(NOW(), INTERVAL 1 DAY)';
    }
}
