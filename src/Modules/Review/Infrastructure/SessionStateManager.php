<?php declare(strict_types=1);
/**
 * Session State Manager
 *
 * Infrastructure adapter for PHP session state management.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Review\Infrastructure;

use Lwt\Modules\Review\Domain\ReviewSession;

/**
 * Adapter for PHP session state management.
 *
 * Abstracts $_SESSION access for the Review module,
 * enabling testability and future session backend changes.
 *
 * @since 3.0.0
 */
class SessionStateManager
{
    /**
     * Session keys used for review state.
     */
    private const KEY_START = 'reviewstart';
    private const KEY_TOTAL = 'reviewtotal';
    private const KEY_CORRECT = 'reviewcorrect';
    private const KEY_WRONG = 'reviewwrong';

    /**
     * Get the current review session from PHP session.
     *
     * @return ReviewSession|null Session or null if not initialized
     */
    public function getSession(): ?ReviewSession
    {
        if (!isset($_SESSION[self::KEY_TOTAL])) {
            return null;
        }

        return new ReviewSession(
            (int) ($_SESSION[self::KEY_START] ?? 0),
            (int) $_SESSION[self::KEY_TOTAL],
            (int) ($_SESSION[self::KEY_CORRECT] ?? 0),
            (int) ($_SESSION[self::KEY_WRONG] ?? 0)
        );
    }

    /**
     * Save the review session to PHP session.
     *
     * @param ReviewSession $session Session to save
     *
     * @return void
     */
    public function saveSession(ReviewSession $session): void
    {
        $_SESSION[self::KEY_START] = $session->getStartTime();
        $_SESSION[self::KEY_TOTAL] = $session->getTotal();
        $_SESSION[self::KEY_CORRECT] = $session->getCorrect();
        $_SESSION[self::KEY_WRONG] = $session->getWrong();
    }

    /**
     * Clear the review session from PHP session.
     *
     * @return void
     */
    public function clearSession(): void
    {
        unset(
            $_SESSION[self::KEY_START],
            $_SESSION[self::KEY_TOTAL],
            $_SESSION[self::KEY_CORRECT],
            $_SESSION[self::KEY_WRONG]
        );
    }

    /**
     * Check if a session exists.
     *
     * @return bool
     */
    public function hasSession(): bool
    {
        return isset($_SESSION[self::KEY_TOTAL]);
    }

    /**
     * Get raw session data (for backward compatibility).
     *
     * @return array{start: int, total: int, correct: int, wrong: int}
     */
    public function getRawSessionData(): array
    {
        return [
            'start' => (int) ($_SESSION[self::KEY_START] ?? 0),
            'total' => (int) ($_SESSION[self::KEY_TOTAL] ?? 0),
            'correct' => (int) ($_SESSION[self::KEY_CORRECT] ?? 0),
            'wrong' => (int) ($_SESSION[self::KEY_WRONG] ?? 0)
        ];
    }
}
