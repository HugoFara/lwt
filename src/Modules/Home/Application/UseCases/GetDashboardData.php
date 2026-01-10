<?php declare(strict_types=1);
/**
 * Get Dashboard Data Use Case
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

use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;

/**
 * Use case for retrieving dashboard data.
 *
 * @since 3.0.0
 */
class GetDashboardData
{
    /**
     * Execute the use case.
     *
     * @return array{
     *   language_count: int,
     *   current_language_id: int|null,
     *   current_text_id: int|null,
     *   current_text_info: array|null,
     *   is_wordpress: bool,
     *   is_multi_user: bool
     * }
     */
    public function execute(): array
    {
        $currentTextId = $this->getCurrentTextId();

        return [
            'language_count' => $this->getLanguageCount(),
            'current_language_id' => $this->getCurrentLanguageId(),
            'current_text_id' => $currentTextId,
            'current_text_info' => $currentTextId !== null
                ? $this->getCurrentTextInfo($currentTextId)
                : null,
            'is_wordpress' => $this->isWordPressSession(),
            'is_multi_user' => Globals::isMultiUserEnabled()
        ];
    }

    /**
     * Get current text information for the dashboard.
     *
     * @param int $textId Text ID to retrieve information for
     *
     * @return array{exists: bool, title?: string, language_id?: int, language_name?: string, annotated?: bool}|null
     */
    private function getCurrentTextInfo(int $textId): ?array
    {
        /** @var mixed $title */
        $title = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->valuePrepared('TxTitle');

        if ($title === null) {
            return null;
        }

        $languageId = (int)QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->valuePrepared('TxLgID');

        $languageName = $this->getLanguageName($languageId);

        $row = QueryBuilder::table('texts')
            ->select(['LENGTH(TxAnnotatedText) AS annotated_length'])
            ->where('TxID', '=', $textId)
            ->firstPrepared();
        $annotated = isset($row['annotated_length']) && (int)$row['annotated_length'] > 0;

        return [
            'exists' => true,
            'title' => (string)$title,
            'language_id' => $languageId,
            'language_name' => $languageName,
            'annotated' => $annotated
        ];
    }

    /**
     * Get language name by ID.
     *
     * @param int $languageId Language ID
     *
     * @return string Language name or empty string if not found
     */
    private function getLanguageName(int $languageId): string
    {
        /** @var mixed $result */
        $result = QueryBuilder::table('languages')
            ->where('LgID', '=', $languageId)
            ->valuePrepared('LgName');

        return $result !== null ? (string)$result : '';
    }

    /**
     * Get the count of languages in the database.
     *
     * @return int Number of languages
     */
    private function getLanguageCount(): int
    {
        return QueryBuilder::table('languages')->count();
    }

    /**
     * Get the current language ID from settings.
     *
     * @return int|null Current language ID or null if not set
     */
    private function getCurrentLanguageId(): ?int
    {
        $currentLang = Settings::get('currentlanguage');
        if (is_numeric($currentLang)) {
            return (int)$currentLang;
        }
        return null;
    }

    /**
     * Get the current text ID from settings.
     *
     * @return int|null Current text ID or null if not set
     */
    private function getCurrentTextId(): ?int
    {
        $currentText = Settings::get('currenttext');
        if (is_numeric($currentText)) {
            return (int)$currentText;
        }
        return null;
    }

    /**
     * Check if user is on WordPress server with active session.
     *
     * @return bool True if WordPress session is active
     */
    private function isWordPressSession(): bool
    {
        return isset($_SESSION['LWT-WP-User']);
    }
}
