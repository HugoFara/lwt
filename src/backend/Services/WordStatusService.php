<?php declare(strict_types=1);
/**
 * Word Status Service - Word status definitions and scoring
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Application\Services\TermStatusService instead
 */

namespace Lwt\Services;

use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;

/**
 * Service class for word status definitions and scoring.
 *
 * Contains status definitions and SQL formulas for word scoring.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Application\Services\TermStatusService instead
 */
class WordStatusService
{
    /**
     * SQL formula for computing today's score.
     *
     * @deprecated Use TermStatusService::SCORE_FORMULA_TODAY instead
     */
    public const SCORE_FORMULA_TODAY = TermStatusService::SCORE_FORMULA_TODAY;

    /**
     * SQL formula for computing tomorrow's score.
     *
     * @deprecated Use TermStatusService::SCORE_FORMULA_TOMORROW instead
     */
    public const SCORE_FORMULA_TOMORROW = TermStatusService::SCORE_FORMULA_TOMORROW;

    /**
     * Return an associative array of all possible statuses.
     *
     * @return array<int, array{abbr: string, name: string}>
     *
     * @deprecated Use TermStatusService::getStatuses() instead
     */
    public static function getStatuses(): array
    {
        return TermStatusService::getStatuses();
    }

    /**
     * Make a random score for a new word.
     *
     * @param 'iv'|'id'|'u'|string $type Type of insertion
     *
     * @return string SQL code to use
     *
     * @deprecated Use TermStatusService::makeScoreRandomInsertUpdate() instead
     */
    public static function makeScoreRandomInsertUpdate(string $type): string
    {
        return TermStatusService::makeScoreRandomInsertUpdate($type);
    }

    /**
     * Check if a status is valid.
     *
     * @param int $status Status to check
     *
     * @return bool True if valid status
     *
     * @deprecated Use TermStatusService::isValidStatus() instead
     */
    public static function isValidStatus(int $status): bool
    {
        return TermStatusService::isValidStatus($status);
    }

    /**
     * Get status name.
     *
     * @param int $status Status value
     *
     * @return string Status name or empty if not found
     *
     * @deprecated Use TermStatusService::getStatusName() instead
     */
    public static function getStatusName(int $status): string
    {
        return TermStatusService::getStatusName($status);
    }

    /**
     * Get status abbreviation.
     *
     * @param int $status Status value
     *
     * @return string Status abbreviation or empty if not found
     *
     * @deprecated Use TermStatusService::getStatusAbbr() instead
     */
    public static function getStatusAbbr(int $status): string
    {
        return TermStatusService::getStatusAbbr($status);
    }
}
