<?php declare(strict_types=1);
/**
 * Term API Controller
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

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;

/**
 * Controller for JSON REST API endpoints.
 *
 * Handles:
 * - GET /vocabulary/term - Get term as JSON
 * - POST /vocabulary/term - Create term via JSON
 * - PUT /vocabulary/term - Update term via JSON
 * - DELETE /vocabulary/term/{wid} - Delete term
 *
 * @since 3.0.0
 */
class TermApiController
{
    /**
     * Vocabulary facade.
     */
    private VocabularyFacade $facade;

    /**
     * Constructor.
     *
     * @param VocabularyFacade|null $facade Vocabulary facade
     */
    public function __construct(?VocabularyFacade $facade = null)
    {
        $this->facade = $facade ?? new VocabularyFacade();
    }

    /**
     * Get term data as JSON.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function getTermJson(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID required']);
            return;
        }

        $term = $this->facade->getTerm($termId);

        if ($term === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Term not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'id' => $term->id()->toInt(),
            'text' => $term->text(),
            'textLc' => $term->textLowercase(),
            'translation' => $term->translation(),
            'romanization' => $term->romanization(),
            'sentence' => $term->sentence(),
            'status' => $term->status()->toInt(),
            'langId' => $term->languageId(),
        ]);
    }

    /**
     * Create term via AJAX.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function createJson(array $params): void
    {
        $langId = InputValidator::getInt('lgid', 0) ?? 0;
        $text = InputValidator::getString('text');
        $status = InputValidator::getInt('status', 1) ?? 1;
        $translation = InputValidator::getString('translation');
        $romanization = InputValidator::getString('romanization');
        $sentence = InputValidator::getString('sentence');

        if ($langId === 0 || $text === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Language ID and text required']);
            return;
        }

        try {
            $term = $this->facade->createTerm(
                $langId,
                $text,
                $status,
                $translation ?: '*',
                $romanization,
                $sentence
            );

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $term->id()->toInt(),
                'text' => $term->text(),
                'textLc' => $term->textLowercase(),
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update term via AJAX.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function updateJson(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;
        $translation = InputValidator::getString('translation');
        $romanization = InputValidator::getString('romanization');
        $sentence = InputValidator::getString('sentence');
        $status = InputValidator::getInt('status', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID required']);
            return;
        }

        try {
            $statusVal = $status !== 0 ? $status : null;

            $term = $this->facade->updateTerm(
                $termId,
                $statusVal,
                $translation !== '' ? $translation : null,
                $sentence !== '' ? $sentence : null,
                null, // notes
                $romanization !== '' ? $romanization : null
            );

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $term->id()->toInt(),
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete term.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function delete(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID required']);
            return;
        }

        $result = $this->facade->deleteTerm($termId);

        header('Content-Type: application/json');
        echo json_encode(['deleted' => $result]);
    }
}
