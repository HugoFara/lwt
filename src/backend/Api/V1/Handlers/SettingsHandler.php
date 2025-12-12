<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Database\Settings;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Core\Globals;
use Lwt\Services\TextStatisticsService;

/**
 * Handler for settings-related API operations.
 *
 * Extracted from api_v1.php.
 */
class SettingsHandler
{
    /**
     * Save a setting to the database.
     *
     * @param string $key   Setting name
     * @param string $value Setting value
     *
     * @return array{error?: string, message?: string, last_text?: array}
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
     * @return array|null Last text data or null if none exists
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
            'title' => $textData['TxTitle'],
            'language_id' => (int)$textData['TxLgID'],
            'language_name' => (string)$languageName,
            'annotated' => (bool)$textData['annotated'],
            'stats' => $stats
        ];
    }

    /**
     * Clear all session settings when changing language.
     *
     * @return void
     */
    private function clearSessionSettings(): void
    {
        // Text filters
        unset($_SESSION['currenttextpage']);
        unset($_SESSION['currenttextquery']);
        unset($_SESSION['currenttextquerymode']);
        unset($_SESSION['currenttexttag1']);
        unset($_SESSION['currenttexttag2']);
        unset($_SESSION['currenttexttag12']);

        // Word filters
        unset($_SESSION['currentwordpage']);
        unset($_SESSION['currentwordquery']);
        unset($_SESSION['currentwordquerymode']);
        unset($_SESSION['currentwordstatus']);
        unset($_SESSION['currentwordtext']);
        unset($_SESSION['currentwordtag1']);
        unset($_SESSION['currentwordtag2']);
        unset($_SESSION['currentwordtag12']);
        unset($_SESSION['currentwordtextmode']);
        unset($_SESSION['currentwordtexttag']);

        // Archive filters
        unset($_SESSION['currentarchivepage']);
        unset($_SESSION['currentarchivequery']);
        unset($_SESSION['currentarchivequerymode']);
        unset($_SESSION['currentarchivetexttag1']);
        unset($_SESSION['currentarchivetexttag2']);
        unset($_SESSION['currentarchivetexttag12']);
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
        return ["theme_path" => \Lwt\Core\Utils\getFilePath($path)];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for saving a setting.
     *
     * @param string $key   Setting key
     * @param string $value Setting value
     *
     * @return array{error?: string, message?: string, last_text?: array|null}
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
}
