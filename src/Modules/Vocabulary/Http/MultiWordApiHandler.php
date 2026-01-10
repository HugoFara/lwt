<?php declare(strict_types=1);
/**
 * Multi-word Expression API Handler
 *
 * Handles API operations for multi-word expressions.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Modules\Vocabulary\Application\Services\WordService;

/**
 * Handler for multi-word expression API operations.
 *
 * Provides endpoints for:
 * - Getting multi-word expression data for editing
 * - Creating new multi-word expressions
 * - Updating existing multi-word expressions
 *
 * @since 3.0.0
 */
class MultiWordApiHandler
{
    private WordService $wordService;

    /**
     * Constructor.
     *
     * @param WordService|null $wordService Word service instance
     */
    public function __construct(?WordService $wordService = null)
    {
        $this->wordService = $wordService ?? new WordService();
    }

    /**
     * Get multi-word expression data for editing.
     *
     * @param int         $textId   Text ID
     * @param int         $position Position in text
     * @param string|null $text     Multi-word text (for new expressions)
     * @param int|null    $wordId   Word ID (for existing expressions)
     *
     * @return array Multi-word data or error
     */
    public function getMultiWordForEdit(int $textId, int $position, ?string $text = null, ?int $wordId = null): array
    {
        // Get language ID from text
        $lgid = $this->wordService->getLanguageIdFromText($textId);
        if ($lgid === null) {
            return ['error' => 'Text not found'];
        }

        // If word ID provided, get existing multi-word
        if ($wordId !== null && $wordId > 0) {
            $data = $this->wordService->getMultiWordData($wordId);
            if ($data === null) {
                return ['error' => 'Multi-word expression not found'];
            }

            // Get word count
            $wordCount = (int) QueryBuilder::table('words')
                ->select(['WoWordCount'])
                ->where('WoID', '=', $wordId)
                ->valuePrepared('WoWordCount');

            $text = (string) $data['text'];
            return [
                'id' => $wordId,
                'text' => $text,
                'textLc' => mb_strtolower($text, 'UTF-8'),
                'translation' => (string) $data['translation'],
                'romanization' => (string) $data['romanization'],
                'sentence' => (string) $data['sentence'],
                'notes' => (string) ($data['notes'] ?? ''),
                'status' => (int) $data['status'],
                'langId' => (int) $data['lgid'],
                'wordCount' => $wordCount,
                'isNew' => false
            ];
        }

        // Check if text is provided
        if ($text === null || $text === '') {
            return ['error' => 'Multi-word text is required for new expressions'];
        }

        // Try to find existing term by text (case-insensitive)
        $textLc = mb_strtolower($text, 'UTF-8');
        $existingWord = QueryBuilder::table('words')
            ->select(['WoID', 'WoText', 'WoTranslation', 'WoRomanization', 'WoSentence', 'WoStatus', 'WoWordCount'])
            ->where('WoTextLC', '=', $textLc)
            ->where('WoLgID', '=', $lgid)
            ->where('WoWordCount', '>', 1)
            ->firstPrepared();

        if ($existingWord !== null) {
            // Found existing multi-word term
            return [
                'id' => (int) $existingWord['WoID'],
                'text' => (string) $existingWord['WoText'],
                'textLc' => $textLc,
                'translation' => (string) ($existingWord['WoTranslation'] ?? ''),
                'romanization' => (string) ($existingWord['WoRomanization'] ?? ''),
                'sentence' => (string) ($existingWord['WoSentence'] ?? ''),
                'notes' => '',
                'status' => (int) $existingWord['WoStatus'],
                'langId' => $lgid,
                'wordCount' => (int) $existingWord['WoWordCount'],
                'isNew' => false
            ];
        }

        // New multi-word expression
        // Get sentence at position
        $sentence = $this->wordService->getSentenceTextAtPosition($textId, $position);

        // Count words in the text
        $wordCount = count(preg_split('/\s+/', trim($text)) ?: []);

        return [
            'id' => null,
            'text' => $text,
            'textLc' => $textLc,
            'translation' => '',
            'romanization' => '',
            'sentence' => $sentence ?? '',
            'notes' => '',
            'status' => 1,
            'langId' => $lgid,
            'wordCount' => $wordCount,
            'isNew' => true
        ];
    }

    /**
     * Create a new multi-word expression.
     *
     * @param array $data Multi-word data:
     *                    - textId: Text ID
     *                    - position: Position in text
     *                    - text: Multi-word text
     *                    - wordCount: Number of words
     *                    - translation: Translation
     *                    - romanization: Romanization
     *                    - sentence: Example sentence
     *                    - notes: Notes (optional)
     *                    - status: Status (1-5)
     *
     * @return array{term_id?: int, term_lc?: string, hex?: string, error?: string}
     */
    public function createMultiWordTerm(array $data): array
    {
        $textId = (int) ($data['textId'] ?? 0);
        $text = trim((string) ($data['text'] ?? ''));

        if ($textId === 0 || $text === '') {
            return ['error' => 'Text ID and multi-word text are required'];
        }

        $lgid = $this->wordService->getLanguageIdFromText($textId);
        if ($lgid === null) {
            return ['error' => 'Text not found'];
        }

        $textLc = mb_strtolower($text, 'UTF-8');
        $splitWords = preg_split('/\s+/', $text);
        $wordCount = (int) ($data['wordCount'] ?? count($splitWords ?: []));

        try {
            $result = $this->wordService->createMultiWord([
                'lgid' => $lgid,
                'text' => $text,
                'textlc' => $textLc,
                'status' => (int) ($data['status'] ?? 1),
                'translation' => (string) ($data['translation'] ?? ''),
                'sentence' => (string) ($data['sentence'] ?? ''),
                'notes' => (string) ($data['notes'] ?? ''),
                'roman' => (string) ($data['romanization'] ?? ''),
                'wordcount' => $wordCount
            ]);

            return [
                'term_id' => $result['id'],
                'term_lc' => $textLc,
                'hex' => StringUtils::toClassName($textLc)
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update an existing multi-word expression.
     *
     * @param int   $termId Term ID
     * @param array $data   Multi-word data (translation, romanization, sentence, notes, status)
     *
     * @return array{success?: bool, status?: int, error?: string}
     */
    public function updateMultiWordTerm(int $termId, array $data): array
    {
        $existing = $this->wordService->getMultiWordData($termId);
        if ($existing === null) {
            return ['error' => 'Multi-word expression not found'];
        }

        $oldStatus = (int) $existing['status'];
        $newStatus = (int) ($data['status'] ?? $oldStatus);

        try {
            $this->wordService->updateMultiWord($termId, [
                'text' => (string) $existing['text'], // Don't change text
                'translation' => (string) ($data['translation'] ?? $existing['translation'] ?? ''),
                'sentence' => (string) ($data['sentence'] ?? $existing['sentence'] ?? ''),
                'notes' => (string) ($data['notes'] ?? $existing['notes'] ?? ''),
                'roman' => (string) ($data['romanization'] ?? $existing['romanization'] ?? '')
            ], $oldStatus, $newStatus);

            return [
                'success' => true,
                'status' => $newStatus
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
