<?php

/**
 * Admin API Handler
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Admin\Http;

use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Admin\Application\AdminFacade;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;
use Lwt\Modules\Admin\Application\Services\MediaService;

/**
 * API handler for admin-related operations.
 *
 * Merges functionality from SettingsHandler and StatisticsHandler.
 *
 * @since 3.0.0
 */
class AdminApiHandler
{
    /**
     * Constructor.
     *
     * @param AdminFacade $adminFacade Admin facade
     */
    public function __construct(
        private AdminFacade $adminFacade
    ) {
    }

    // =========================================================================
    // Settings Operations
    // =========================================================================

    /**
     * Save a setting to the database.
     *
     * @param string $key   Setting name
     * @param string $value Setting value
     *
     * @return array{error?: string, message?: string, last_text?: array<array-key, mixed>|null}
     */
    public function saveSetting(string $key, string $value): array
    {
        // Clear session settings when changing language
        if ($key === 'currentlanguage') {
            $this->clearSessionSettings();
        }

        $status = Settings::save($key, $value);
        if (str_starts_with($status, "OK: ")) {
            $result = ["message" => substr($status, 4)];

            // For language changes, include the last text info for that language
            if ($key === 'currentlanguage' && $value !== '') {
                $result['last_text'] = $this->getLastTextForLanguage((int)$value);
            }

            return $result;
        }
        return ["error" => $status];
    }

    /**
     * Get the last text information for a specific language.
     *
     * @param int $languageId Language ID
     *
     * @return array<string, mixed>|null Last text data or null if none exists
     */
    private function getLastTextForLanguage(int $languageId): ?array
    {
        // Get the current text ID
        $currentTextId = Settings::get('currenttext');

        if ($currentTextId === '') {
            return null;
        }

        $textId = (int)$currentTextId;

        // Check if the current text belongs to this language
        $textData = QueryBuilder::table('texts')
            ->selectRaw('TxID, TxTitle, TxLgID, LENGTH(TxAnnotatedText) > 0 AS annotated')
            ->where('TxID', '=', $textId)
            ->where('TxLgID', '=', $languageId)
            ->firstPrepared();

        if ($textData === null) {
            // Current text doesn't belong to this language, find the most recent one
            $textData = QueryBuilder::table('texts')
                ->selectRaw('TxID, TxTitle, TxLgID, LENGTH(TxAnnotatedText) > 0 AS annotated')
                ->where('TxLgID', '=', $languageId)
                ->orderBy('TxID', 'DESC')
                ->limit(1)
                ->firstPrepared();
        }

        if ($textData === null) {
            return null;
        }

        // Get language name
        /** @var string|null $languageName */
        $languageName = QueryBuilder::table('languages')
            ->where('LgID', '=', $languageId)
            ->valuePrepared('LgName');

        $textId = (int)$textData['TxID'];

        // Get text statistics
        $textStatsService = new TextStatisticsService();
        $textStats = $textStatsService->getTextWordCount((string)$textId);
        $todoCount = $textStatsService->getTodoWordsCount($textId);

        $stats = [
            'unknown' => $todoCount,
            's1' => $textStats['statu'][$textId][1] ?? 0,
            's2' => $textStats['statu'][$textId][2] ?? 0,
            's3' => $textStats['statu'][$textId][3] ?? 0,
            's4' => $textStats['statu'][$textId][4] ?? 0,
            's5' => $textStats['statu'][$textId][5] ?? 0,
            's98' => $textStats['statu'][$textId][98] ?? 0,
            's99' => $textStats['statu'][$textId][99] ?? 0,
        ];
        $stats['total'] = $stats['unknown'] + $stats['s1'] + $stats['s2'] + $stats['s3']
            + $stats['s4'] + $stats['s5'] + $stats['s98'] + $stats['s99'];

        return [
            'id' => $textId,
            'title' => $textData['TxTitle'],
            'language_id' => (int)$textData['TxLgID'],
            'language_name' => (string)$languageName,
            'annotated' => (bool)$textData['annotated'],
            'stats' => $stats
        ];
    }

    /**
     * Clear session settings when changing language.
     *
     * Note: Pagination/filter state is now stored in URL parameters,
     * so session clearing is no longer needed. This method is kept
     * for backwards compatibility but is now a no-op.
     *
     * @return void
     */
    private function clearSessionSettings(): void
    {
        // No-op: pagination state is now in URL parameters, not session
    }

    /**
     * Get the file path using the current theme.
     *
     * @param string $path Relative filepath using theme
     *
     * @return array{theme_path: string}
     */
    public function getThemePath(string $path): array
    {
        return ["theme_path" => StringUtils::getFilePath($path)];
    }

    /**
     * Format response for saving a setting.
     *
     * @param string $key   Setting key
     * @param string $value Setting value
     *
     * @return array{error?: string, message?: string, last_text?: array<array-key, mixed>|null}
     */
    public function formatSaveSetting(string $key, string $value): array
    {
        return $this->saveSetting($key, $value);
    }

    /**
     * Format response for getting theme path.
     *
     * @param string $path Relative path
     *
     * @return array{theme_path: string}
     */
    public function formatThemePath(string $path): array
    {
        return $this->getThemePath($path);
    }

    // =========================================================================
    // Statistics Operations
    // =========================================================================

    /**
     * Return statistics about a group of texts.
     *
     * @param string $textsId Comma-separated text IDs
     *
     * @return array Text word count statistics
     */
    public function getTextsStatistics(string $textsId): array
    {
        $service = new TextStatisticsService();
        return $service->getTextWordCount($textsId);
    }

    /**
     * Format response for texts statistics.
     *
     * Transforms the raw statistics data into a format expected by the frontend:
     * - total: unique word count
     * - saved: count of words with any status (1-5, 98, 99)
     * - unknown: count of words without a saved status
     * - unknownPercent: percentage of unknown words
     * - statusCounts: word counts by status
     *
     * @param string $textsId Comma-separated text IDs
     *
     * @return array<string, array{total: int, saved: int, unknown: int, unknownPercent: int, statusCounts: array<string, int>}>
     */
    public function formatTextsStatistics(string $textsId): array
    {
        $raw = $this->getTextsStatistics($textsId);
        $result = [];

        // Get all text IDs from the request
        $textIds = array_map('intval', explode(',', $textsId));

        foreach ($textIds as $textId) {
            $textIdStr = (string) $textId;

            // Get unique word count (totalu)
            $total = isset($raw['totalu'][$textIdStr])
                ? (int) $raw['totalu'][$textIdStr]
                : 0;

            // Sum saved words from status counts (statu)
            $saved = 0;
            /** @var array<string, int> $statusCounts */
            $statusCounts = [];
            if (isset($raw['statu'][$textIdStr]) && is_array($raw['statu'][$textIdStr])) {
                /**
                 * @var int|string $status
                 * @var int|string $count
                 */
                foreach ($raw['statu'][$textIdStr] as $status => $count) {
                    $countInt = is_int($count) ? $count : (int) $count;
                    $saved += $countInt;
                    $statusCounts[(string) $status] = $countInt;
                }
            }

            // Unknown = total unique - saved unique
            $unknown = $total - $saved;

            // Calculate unknown percentage
            $unknownPercent = $total > 0
                ? (int) round(($unknown / $total) * 100)
                : 0;

            $result[$textIdStr] = [
                'total' => $total,
                'saved' => $saved,
                'unknown' => $unknown,
                'unknownPercent' => $unknownPercent,
                'statusCounts' => $statusCounts
            ];
        }

        return $result;
    }

    // =========================================================================
    // Module Statistics (from AdminFacade)
    // =========================================================================

    /**
     * Get intensity statistics.
     *
     * @return array{languages: array, totals: array}
     */
    public function getIntensityStatistics(): array
    {
        return $this->adminFacade->getIntensityStatistics();
    }

    /**
     * Get frequency statistics.
     *
     * @return array{languages: array, totals: array}
     */
    public function getFrequencyStatistics(): array
    {
        return $this->adminFacade->getFrequencyStatistics();
    }

    // =========================================================================
    // Server Data
    // =========================================================================

    /**
     * Get server data.
     *
     * @return array Server information
     */
    public function getServerData(): array
    {
        return $this->adminFacade->getServerData();
    }

    // =========================================================================
    // Media Operations
    // =========================================================================

    /**
     * List the audio and video files in the media folder.
     *
     * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
     */
    public function getMediaFiles(): array
    {
        $mediaService = new MediaService();
        return $mediaService->getMediaPaths();
    }

    /**
     * Format response for media files list.
     *
     * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
     */
    public function formatMediaFiles(): array
    {
        return $this->getMediaFiles();
    }
}
