<?php

/**
 * Text Print Controller
 *
 * HTTP controller for text printing functionality.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Text\Http;

use Lwt\Controllers\BaseController;
use Lwt\Modules\Text\Application\Services\TextPrintService;
use Lwt\Modules\Text\Application\Services\AnnotationService;
use Lwt\Modules\Text\Http\TextApiHandler;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * Controller for text printing functionality.
 *
 * Handles:
 * - Plain text printing with annotations
 * - Improved/annotated text printing
 * - Annotation editing
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextPrintController extends BaseController
{
    /**
     * Base path for legacy includes.
     */
    private const BACKEND_PATH = __DIR__ . '/../../../backend';

    /**
     * Module views path.
     */
    private const MODULE_VIEWS = __DIR__ . '/../Views';
    /**
     * Text print service for business logic.
     *
     * @var TextPrintService
     */
    private TextPrintService $printService;

    /**
     * Create a new TextPrintController.
     *
     * @param TextPrintService|null $printService Print service for text printing operations
     */
    public function __construct(?TextPrintService $printService = null)
    {
        parent::__construct();
        $this->printService = $printService ?? new TextPrintService();
    }

    /**
     * Get the print service instance (for testing).
     *
     * @return TextPrintService
     */
    public function getPrintService(): TextPrintService
    {
        return $this->printService;
    }

    /**
     * Print plain text with annotations (replaces text_print_plain.php).
     *
     * Route: /text/print-plain?text=[textid]&ann=[annotationcode]&status=[statuscode]
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function printPlain(array $params): void
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        include_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        include_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        include_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        include_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';

        $textId = (int) $this->param('text', '0');

        if ($textId === 0) {
            $this->redirect('/text/edit');
        }

        // Get print settings from request or saved settings
        $savedAnn = $this->printService->getAnnotationSetting($this->param('ann'));
        $savedStatus = $this->printService->getStatusRangeSetting($this->param('status'));
        $savedPlacement = $this->printService->getAnnotationPlacementSetting($this->param('annplcmnt'));

        // Prepare view data
        $viewData = $this->printService->preparePlainPrintData($textId);
        if ($viewData === null) {
            $this->redirect('/text/edit');
        }

        // Save settings
        $this->printService->savePrintSettings($textId, $savedAnn, $savedStatus, $savedPlacement);

        // Set mode for Alpine view
        $mode = 'plain';

        // Render the view
        PageLayoutHelper::renderPageStartNobody('Print');

        include self::MODULE_VIEWS . '/print_alpine.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Print improved annotated text (replaces text_print.php).
     *
     * Route: /text/{text}/print
     *
     * @param array $params Route parameters (expects 'text' key with text ID)
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function printAnnotated(array $params): void
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        include_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        include_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        include_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        include_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Infrastructure/DictionaryAdapter.php';

        $textId = (int) ($params['text'] ?? 0);

        if ($textId === 0) {
            $this->redirect('/text/edit');
        }

        // Handle annotation recreation
        $ann = $this->printService->getAnnotatedText($textId);
        $annExists = $ann !== null;
        if ($annExists) {
            $annotationService = new AnnotationService();
            $ann = $annotationService->recreateSaveAnnotation($textId, $ann);
            $annExists = strlen($ann) > 0;
        }

        // Prepare view data
        $viewData = $this->printService->prepareAnnotatedPrintData($textId);
        if ($viewData === null) {
            $this->redirect('/text/edit');
        }

        // Save current text setting
        $this->printService->setCurrentText($textId);

        // Display mode (not edit)
        $mode = 'annotated';
        $editFormHtml = null;
        $savedAnn = 0;
        $savedStatus = 0;
        $savedPlacement = 0;

        // Render the view
        PageLayoutHelper::renderPageStartNobody('Annotated Text');

        include self::MODULE_VIEWS . '/print_alpine.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Edit annotation for a text.
     *
     * Route: /text/{text}/print/edit
     *
     * @param array $params Route parameters (expects 'text' key with text ID)
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function editAnnotation(array $params): void
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        include_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        include_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        include_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        include_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Infrastructure/DictionaryAdapter.php';

        $textId = (int) ($params['text'] ?? 0);

        if ($textId === 0) {
            $this->redirect('/text/edit');
        }

        // Handle annotation recreation or creation
        $ann = $this->printService->getAnnotatedText($textId);
        $annExists = $ann !== null;
        if ($annExists) {
            $annotationService = new AnnotationService();
            $ann = $annotationService->recreateSaveAnnotation($textId, $ann);
            $annExists = strlen($ann) > 0;
        }

        // Create annotation if it doesn't exist
        if (!$annExists) {
            $annotationService = new AnnotationService();
            $ann = $annotationService->createSaveAnnotation($textId);
            $annExists = strlen($ann) > 0;
        }

        // Prepare view data
        $viewData = $this->printService->prepareAnnotatedPrintData($textId);
        if ($viewData === null) {
            $this->redirect('/text/edit');
        }

        // Save current text setting
        $this->printService->setCurrentText($textId);

        // Edit mode
        $mode = 'edit';
        $editFormHtml = null;
        $savedAnn = 0;
        $savedStatus = 0;
        $savedPlacement = 0;

        if ($annExists) {
            $handler = new TextApiHandler();
            $editFormHtml = $handler->editTermForm($textId);
        }

        // Render the view
        PageLayoutHelper::renderPageStartNobody('Annotated Text');

        include self::MODULE_VIEWS . '/print_alpine.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Delete annotation for a text.
     *
     * Route: DELETE /text/{text}/annotation
     *
     * @param array $params Route parameters (expects 'text' key with text ID)
     *
     * @return void
     */
    public function deleteAnnotation(array $params): void
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';

        $textId = (int) ($params['text'] ?? 0);

        if ($textId === 0) {
            $this->redirect('/text/edit');
        }

        $deleted = $this->printService->deleteAnnotation($textId);
        if ($deleted) {
            $this->redirect('/text/print-plain?text=' . $textId);
        }

        // If deletion failed, redirect back to print view
        $this->redirect('/text/' . $textId . '/print');
    }
}
