<?php declare(strict_types=1);
/**
 * Vocabulary Controller
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
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for vocabulary/term management operations.
 *
 * @since 3.0.0
 */
class VocabularyController
{
    /**
     * View base path.
     */
    private string $viewPath;

    /**
     * Vocabulary facade.
     */
    private VocabularyFacade $facade;

    /**
     * Use cases.
     */
    private CreateTermFromHover $createTermFromHover;
    private FindSimilarTerms $findSimilarTerms;

    /**
     * Adapters.
     */
    private DictionaryAdapter $dictionaryAdapter;

    /**
     * Constructor.
     *
     * @param VocabularyFacade|null     $facade              Vocabulary facade
     * @param CreateTermFromHover|null  $createTermFromHover Create term from hover use case
     * @param FindSimilarTerms|null     $findSimilarTerms    Find similar terms use case
     * @param DictionaryAdapter|null    $dictionaryAdapter   Dictionary adapter
     */
    public function __construct(
        ?VocabularyFacade $facade = null,
        ?CreateTermFromHover $createTermFromHover = null,
        ?FindSimilarTerms $findSimilarTerms = null,
        ?DictionaryAdapter $dictionaryAdapter = null
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->facade = $facade ?? new VocabularyFacade();
        $this->createTermFromHover = $createTermFromHover ?? new CreateTermFromHover();
        $this->findSimilarTerms = $findSimilarTerms ?? new FindSimilarTerms();
        $this->dictionaryAdapter = $dictionaryAdapter ?? new DictionaryAdapter();
    }

    /**
     * Set custom view path.
     *
     * @param string $path View path
     *
     * @return void
     */
    public function setViewPath(string $path): void
    {
        $this->viewPath = rtrim($path, '/') . '/';
    }

    // =========================================================================
    // Hover Actions (from text reading view)
    // =========================================================================

    /**
     * Create a term from hover action in reading view.
     *
     * This is called when user clicks a status from the hover menu
     * while reading a text.
     *
     * @param int    $textId     Text ID
     * @param string $wordText   Word text
     * @param int    $status     Word status (1-5)
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     *
     * @return array Term creation result
     */
    public function createFromHover(
        int $textId,
        string $wordText,
        int $status,
        string $sourceLang = '',
        string $targetLang = ''
    ): array {
        // Set no-cache headers for new words
        if ($this->createTermFromHover->shouldSetNoCacheHeaders($status)) {
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        return $this->createTermFromHover->execute(
            $textId,
            $wordText,
            $status,
            $sourceLang,
            $targetLang
        );
    }

    /**
     * Handle the hover create action from reading view.
     *
     * This is the route handler that parses request params and
     * renders the result view.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function hoverCreate(array $params): void
    {
        $text = InputValidator::getString('text');
        $textId = InputValidator::getInt('tid', 0);
        $status = InputValidator::getInt('status', 1);
        $targetLang = InputValidator::getString('tl');
        $sourceLang = InputValidator::getString('sl');

        // Create the term
        $result = $this->createFromHover(
            $textId,
            $text,
            $status,
            $sourceLang,
            $targetLang
        );

        // Render page
        PageLayoutHelper::renderPageStart("New Term: " . $result['word'], false);

        // Prepare view variables
        $word = $result['word'];
        $wordRaw = $result['wordRaw'];
        $wid = $result['wid'];
        $hex = $result['hex'];
        $translation = $result['translation'];

        $this->render('hover_save_result', [
            'word' => $word,
            'wordRaw' => $wordRaw,
            'wid' => $wid,
            'hex' => $hex,
            'translation' => $translation,
            'textId' => $textId,
            'status' => $status,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    // =========================================================================
    // Similar Terms
    // =========================================================================

    /**
     * Get similar terms for a given term.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function similarTerms(array $params): void
    {
        $langId = InputValidator::getInt('lgid', 0);
        $term = InputValidator::getString('term');

        header('Content-Type: text/html; charset=utf-8');
        echo $this->findSimilarTerms->getFormattedTerms($langId, $term);
    }

    // =========================================================================
    // Dictionary Links
    // =========================================================================

    /**
     * Get dictionary links for editing.
     *
     * @param int    $langId    Language ID
     * @param string $word      Word to look up
     * @param string $sentctlid Sentence control ID
     * @param bool   $openFirst Open first dictionary
     *
     * @return string HTML dictionary links
     */
    public function getDictionaryLinks(
        int $langId,
        string $word,
        string $sentctlid,
        bool $openFirst = false
    ): string {
        return $this->dictionaryAdapter->createDictLinksInEditWin(
            $langId,
            $word,
            $sentctlid,
            $openFirst
        );
    }

    // =========================================================================
    // CRUD Operations (delegating to facade)
    // =========================================================================

    /**
     * Show term edit form.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0);

        if ($termId === 0) {
            http_response_code(400);
            echo 'Term ID required';
            return;
        }

        $term = $this->facade->getTerm($termId);

        if ($term === null) {
            http_response_code(404);
            echo 'Term not found';
            return;
        }

        PageLayoutHelper::renderPageStart("Edit Term: " . $term->text(), false);

        $this->render('form_edit', [
            'term' => $term,
            'dictionaryLinks' => $this->getDictionaryLinks(
                $term->languageId()->toInt(),
                $term->text(),
                'sentence_textarea',
                true
            ),
            'similarTermsHtml' => $this->findSimilarTerms->getFormattedTerms(
                $term->languageId()->toInt(),
                $term->textLowercase()
            ),
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Show term details.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function show(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0);

        if ($termId === 0) {
            http_response_code(400);
            echo 'Term ID required';
            return;
        }

        $term = $this->facade->getTerm($termId);

        if ($term === null) {
            http_response_code(404);
            echo 'Term not found';
            return;
        }

        PageLayoutHelper::renderPageStart("Term: " . $term->text(), false);

        $this->render('show', [
            'term' => $term,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Update term status.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function updateStatus(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0);
        $status = InputValidator::getInt('status', 0);

        if ($termId === 0 || $status === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID and status required']);
            return;
        }

        $result = $this->facade->updateStatus($termId, $status);

        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
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
        $termId = InputValidator::getInt('wid', 0);

        if ($termId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID required']);
            return;
        }

        $result = $this->facade->deleteTerm($termId);

        header('Content-Type: application/json');
        echo json_encode(['deleted' => $result]);
    }

    // =========================================================================
    // View Rendering
    // =========================================================================

    /**
     * Render a view.
     *
     * @param string $view View name (without .php)
     * @param array  $data View data
     *
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract($data);
        require $viewFile;
    }

    // =========================================================================
    // JSON API Methods (for AJAX calls)
    // =========================================================================

    /**
     * Get term data as JSON.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function getTermJson(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0);

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
        $langId = InputValidator::getInt('lgid', 0);
        $text = InputValidator::getString('text');
        $status = InputValidator::getInt('status', 1);
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
        $termId = InputValidator::getInt('wid', 0);
        $translation = InputValidator::getString('translation');
        $romanization = InputValidator::getString('romanization');
        $sentence = InputValidator::getString('sentence');
        $status = InputValidator::getInt('status', 0);

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
}
