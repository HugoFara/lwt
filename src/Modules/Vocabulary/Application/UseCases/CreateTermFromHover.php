<?php declare(strict_types=1);
/**
 * Create Term From Hover Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Application\UseCases;

use Lwt\Core\Entity\GoogleTranslate;
use Lwt\Services\WordService;

/**
 * Use case for creating a term from the text reading hover action.
 *
 * When a user clicks on a word status in the reading view hover menu,
 * this use case creates the term with the specified status.
 *
 * @since 3.0.0
 */
class CreateTermFromHover
{
    private WordService $wordService;

    /**
     * Constructor.
     *
     * @param WordService|null $wordService Word service
     */
    public function __construct(?WordService $wordService = null)
    {
        $this->wordService = $wordService ?? new WordService();
    }

    /**
     * Execute the use case.
     *
     * @param int    $textId     Text ID
     * @param string $wordText   Word text to create
     * @param int    $status     Word status (1-5)
     * @param string $sourceLang Source language code (for translation)
     * @param string $targetLang Target language code (for translation)
     *
     * @return array{
     *     wid: int,
     *     word: string,
     *     wordRaw: string,
     *     translation: string,
     *     status: int,
     *     hex: string
     * }
     */
    public function execute(
        int $textId,
        string $wordText,
        int $status,
        string $sourceLang = '',
        string $targetLang = ''
    ): array {
        // Get translation if status is 1 (new word) and translation params provided
        $translation = '*';
        if ($status === 1 && $sourceLang !== '' && $targetLang !== '') {
            $translationResult = GoogleTranslate::staticTranslate(
                $wordText,
                $sourceLang,
                $targetLang
            );
            if ($translationResult) {
                $translation = $translationResult[0];
            }
            // Don't use word as its own translation
            if ($translation === $wordText) {
                $translation = '*';
            }
        }

        // Create the word
        return $this->wordService->createOnHover($textId, $wordText, $status, $translation);
    }

    /**
     * Check if this is a new word (status 1) that should set no-cache headers.
     *
     * @param int $status Word status
     *
     * @return bool True if no-cache headers should be set
     */
    public function shouldSetNoCacheHeaders(int $status): bool
    {
        return $status === 1;
    }
}
