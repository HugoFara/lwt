<?php declare(strict_types=1);
/**
 * Get Text Statistics Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Home\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Home\Application\UseCases;

use Lwt\Modules\Text\Application\Services\TextStatisticsService;

/**
 * Use case for retrieving text statistics for home page display.
 *
 * @since 3.0.0
 */
class GetTextStatistics
{
    private TextStatisticsService $statsService;

    /**
     * Constructor.
     *
     * @param TextStatisticsService|null $statsService Optional stats service
     */
    public function __construct(?TextStatisticsService $statsService = null)
    {
        $this->statsService = $statsService ?? new TextStatisticsService();
    }

    /**
     * Execute the use case.
     *
     * @param int   $textId   Text ID
     * @param array $textInfo Text info from GetDashboardData
     *
     * @return array|null Text info with statistics, or null if no text
     */
    public function execute(int $textId, array $textInfo): ?array
    {
        $textStats = $this->statsService->getTextWordCount((string)$textId);
        $todoCount = $this->statsService->getTodoWordsCount($textId);

        // Build statistics array with status counts
        $stats = [
            'unknown' => $todoCount,
            's1' => (int)($textStats['statu'][$textId][1] ?? 0),
            's2' => (int)($textStats['statu'][$textId][2] ?? 0),
            's3' => (int)($textStats['statu'][$textId][3] ?? 0),
            's4' => (int)($textStats['statu'][$textId][4] ?? 0),
            's5' => (int)($textStats['statu'][$textId][5] ?? 0),
            's98' => (int)($textStats['statu'][$textId][98] ?? 0),
            's99' => (int)($textStats['statu'][$textId][99] ?? 0),
        ];
        $stats['total'] = $stats['unknown'] + $stats['s1'] + $stats['s2'] + $stats['s3']
            + $stats['s4'] + $stats['s5'] + $stats['s98'] + $stats['s99'];

        return [
            'id' => $textId,
            'title' => $textInfo['title'],
            'language_id' => $textInfo['language_id'],
            'language_name' => $textInfo['language_name'],
            'annotated' => $textInfo['annotated'],
            'stats' => $stats,
        ];
    }
}
