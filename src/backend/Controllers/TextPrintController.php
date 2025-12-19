<?php declare(strict_types=1);
/**
 * \file
 * \brief Text Print Controller - Text printing functionality
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-textprintcontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Services\TextPrintService;
use Lwt\Services\AnnotationService;
use Lwt\Api\V1\Handlers\ImprovedTextHandler;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../Services/TextPrintService.php';
require_once __DIR__ . '/../Services/TextNavigationService.php';
require_once __DIR__ . '/../Services/AnnotationService.php';
require_once __DIR__ . '/../Api/V1/Handlers/ImprovedTextHandler.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/SelectOptionsBuilder.php';
require_once __DIR__ . '/../View/Helper/FormHelper.php';

/**
 * Controller for text printing functionality.
 *
 * Handles:
 * - Plain text printing with annotations
 * - Improved/annotated text printing
 * - Annotation editing
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextPrintController extends BaseController
{
    /**
     * Text print service for business logic.
     *
     * @var TextPrintService
     */
    private TextPrintService $printService;

    /**
     * Create a new TextPrintController.
     *
     * @param TextPrintService $printService Print service for text printing operations
     */
    public function __construct(TextPrintService $printService)
    {
        parent::__construct();
        $this->printService = $printService;
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
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Services/TextStatisticsService.php';
        require_once __DIR__ . '/../Services/SentenceService.php';
        require_once __DIR__ . '/../Services/AnnotationService.php';
        require_once __DIR__ . '/../Services/SimilarTermsService.php';
        require_once __DIR__ . '/../Services/TextNavigationService.php';
        require_once __DIR__ . '/../Services/TextParsingService.php';
        require_once __DIR__ . '/../Services/ExpressionService.php';
        require_once __DIR__ . '/../Core/Database/Restore.php';
        require_once __DIR__ . '/../Services/WordStatusService.php';

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

        include __DIR__ . '/../Views/TextPrint/print_alpine.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Print/edit improved annotated text (replaces text_print.php).
     *
     * Route: /text/print?text=[textid]&edit=[0|1]&del=[0|1]
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function printAnnotated(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Services/TextStatisticsService.php';
        require_once __DIR__ . '/../Services/SentenceService.php';
        require_once __DIR__ . '/../Services/AnnotationService.php';
        require_once __DIR__ . '/../Services/SimilarTermsService.php';
        require_once __DIR__ . '/../Services/TextNavigationService.php';
        require_once __DIR__ . '/../Services/TextParsingService.php';
        require_once __DIR__ . '/../Services/ExpressionService.php';
        require_once __DIR__ . '/../Core/Database/Restore.php';
        require_once __DIR__ . '/../Services/DictionaryService.php';
        require_once __DIR__ . '/../Services/WordStatusService.php';

        $textId = (int) $this->param('text', '0');
        $editMode = (int) $this->param('edit', '0');
        $deleteMode = (int) $this->param('del', '0');

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

        // Handle delete mode
        if ($deleteMode && $annExists) {
            $deleted = $this->printService->deleteAnnotation($textId);
            if ($deleted) {
                $this->redirect('/text/print-plain?text=' . $textId);
            }
        }

        // Prepare view data
        $viewData = $this->printService->prepareAnnotatedPrintData($textId);
        if ($viewData === null) {
            $this->redirect('/text/edit');
        }

        // Save current text setting
        $this->printService->setCurrentText($textId);

        // Set mode and prepare edit form if needed
        $mode = $editMode ? 'edit' : 'annotated';
        $editFormHtml = null;
        $savedAnn = 0;
        $savedStatus = 0;
        $savedPlacement = 0;

        if ($editMode) {
            // For edit mode, create annotation if needed and render edit form
            if (!$annExists) {
                $annotationService = new AnnotationService();
                $ann = $annotationService->createSaveAnnotation($textId);
                $annExists = strlen($ann) > 0;
            }
            if ($annExists) {
                $handler = new ImprovedTextHandler();
                $editFormHtml = $handler->editTermForm($textId);
            }
        }

        // Render the view
        PageLayoutHelper::renderPageStartNobody('Annotated Text');

        include __DIR__ . '/../Views/TextPrint/print_alpine.php';

        PageLayoutHelper::renderPageEnd();
    }

}
