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
use Lwt\Api\V1\Handlers\ImprovedTextHandler;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../Services/TextPrintService.php';
require_once __DIR__ . '/../Api/V1/Handlers/ImprovedTextHandler.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';

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
     * Constructor - initialize services.
     */
    public function __construct()
    {
        parent::__construct();
        $this->printService = new TextPrintService();
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
        require_once __DIR__ . '/../Core/Http/param_helpers.php';
        require_once __DIR__ . '/../Services/WordStatusService.php';

        $textId = (int) $this->param('text', 0);

        if ($textId === 0) {
            $this->redirect('/text/edit');
        }

        // Get print settings from request or saved settings
        $ann = $this->printService->getAnnotationSetting($this->param('ann'));
        $statusRange = $this->printService->getStatusRangeSetting($this->param('status'));
        $annPlacement = $this->printService->getAnnotationPlacementSetting($this->param('annplcmnt'));

        // Parse annotation flags
        $showRom = $ann & TextPrintService::ANN_SHOW_ROM;
        $showTrans = $ann & TextPrintService::ANN_SHOW_TRANS;
        $showTags = $ann & TextPrintService::ANN_SHOW_TAGS;

        // Prepare view data
        $viewData = $this->printService->preparePlainPrintData($textId);
        if ($viewData === null) {
            $this->redirect('/text/edit');
        }

        // Save settings
        $this->printService->savePrintSettings($textId, $ann, $statusRange, $annPlacement);

        // Get text items
        $textItems = $this->printService->getTextItems($textId);

        // Render the view
        PageLayoutHelper::renderPageStartNobody('Print');

        include __DIR__ . '/../Views/TextPrint/plain_print.php';

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
        require_once __DIR__ . '/../Core/Http/param_helpers.php';
        require_once __DIR__ . '/../Services/DictionaryService.php';
        require_once __DIR__ . '/../Services/WordStatusService.php';

        $textId = (int) $this->param('text', 0);
        $editMode = (int) $this->param('edit', 0);
        $deleteMode = (int) $this->param('del', 0);

        if ($textId === 0) {
            $this->redirect('/text/edit');
        }

        // Handle annotation recreation
        $ann = $this->printService->getAnnotatedText($textId);
        $annExists = $ann !== null;
        if ($annExists) {
            $ann = \recreate_save_ann($textId, $ann);
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

        // Render the view
        PageLayoutHelper::renderPageStartNobody('Annotated Text');

        if ($editMode) {
            $this->renderEditMode($textId, $annExists, $viewData);
        } else {
            $this->renderDisplayMode($textId, $viewData, $ann ?? '');
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Render the header section for print pages.
     *
     * @param int    $textId     Text ID
     * @param array  $viewData   View data array
     * @param string $printUrl   URL for print navigation
     * @param bool   $showImprov Show improved annotation link
     *
     * @return void
     *
     * @psalm-suppress UnusedParam Parameters are used in included view files
     */
    private function renderHeader(
        int $textId,
        array $viewData,
        string $printUrl,
        bool $showImprov = true
    ): void {
        include __DIR__ . '/../Views/TextPrint/header.php';
    }

    /**
     * Render edit mode for annotated text.
     *
     * @param int   $textId    Text ID
     * @param bool  $annExists Whether annotations exist
     * @param array $viewData  View data array
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function renderEditMode(int $textId, bool $annExists, array $viewData): void
    {
        $this->renderHeader($textId, $viewData, '/text/print?text=', false);

        if (!$annExists) {
            // No annotations, try create them
            $ann = \create_save_ann($textId);
            $annExists = strlen($ann) > 0;
        }

        include __DIR__ . '/../Views/TextPrint/annotated_edit.php';
    }

    /**
     * Render display/print mode for annotated text.
     *
     * @param int    $textId   Text ID
     * @param array  $viewData View data array
     * @param string $ann      Annotation string
     *
     * @return void
     *
     * @psalm-suppress UnusedParam Parameters are used in included view files
     */
    private function renderDisplayMode(int $textId, array $viewData, string $ann): void
    {
        $this->renderHeader($textId, $viewData, '/text/print?text=', true);

        include __DIR__ . '/../Views/TextPrint/annotated_display.php';
    }

    /**
     * Output text with annotations (used by plain print).
     *
     * @param string $term       Term text
     * @param string $rom        Romanization
     * @param string $trans      Translation
     * @param string $tags       Tags
     * @param bool   $showRom    Show romanization
     * @param bool   $showTrans  Show translation
     * @param bool   $showTags   Show tags
     * @param int    $placement  Annotation placement
     *
     * @return string HTML output
     */
    public function formatTermOutput(
        string $term,
        string $rom,
        string $trans,
        string $tags,
        bool $showRom,
        bool $showTrans,
        bool $showTags,
        int $placement
    ): string {
        if ($showTags) {
            if ($trans === '' && $tags !== '') {
                $trans = '* ' . $tags;
            } else {
                $trans = trim($trans . ' ' . $tags);
            }
        }
        if ($showRom && $rom === '') {
            $showRom = false;
        }
        if ($showTrans && $trans === '') {
            $showTrans = false;
        }

        switch ($placement) {
            case TextPrintService::ANN_PLACEMENT_INFRONT:
                return $this->formatTermInFront($term, $rom, $trans, $showRom, $showTrans);

            case TextPrintService::ANN_PLACEMENT_RUBY:
                return $this->formatTermRuby($term, $rom, $trans, $showRom, $showTrans);

            default:
                return $this->formatTermBehind($term, $rom, $trans, $showRom, $showTrans);
        }
    }

    /**
     * Format term with annotation in front.
     *
     * @param string $term      Term text
     * @param string $rom       Romanization
     * @param string $trans     Translation
     * @param bool   $showRom   Show romanization
     * @param bool   $showTrans Show translation
     *
     * @return string HTML output
     */
    private function formatTermInFront(
        string $term,
        string $rom,
        string $trans,
        bool $showRom,
        bool $showTrans
    ): string {
        $output = '';
        if ($showRom || $showTrans) {
            $output .= ' ';
            if ($showTrans) {
                $output .= '<span class="anntrans">' . \tohtml($trans) . '</span> ';
            }
            if ($showRom && !$showTrans) {
                $output .= '<span class="annrom">' . \tohtml($rom) . '</span> ';
            }
            if ($showRom && $showTrans) {
                $output .= '<span class="annrom" dir="ltr">[' . \tohtml($rom) . ']</span> ';
            }
            $output .= ' <span class="annterm">';
        }
        $output .= \tohtml($term);
        if ($showRom || $showTrans) {
            $output .= '</span> ';
        }
        return $output;
    }

    /**
     * Format term with annotation above (ruby).
     *
     * @param string $term      Term text
     * @param string $rom       Romanization
     * @param string $trans     Translation
     * @param bool   $showRom   Show romanization
     * @param bool   $showTrans Show translation
     *
     * @return string HTML output
     */
    private function formatTermRuby(
        string $term,
        string $rom,
        string $trans,
        bool $showRom,
        bool $showTrans
    ): string {
        if ($showRom || $showTrans) {
            $output = ' <ruby><rb><span class="anntermruby">' . \tohtml($term) . '</span></rb><rt> ';
            if ($showTrans) {
                $output .= '<span class="anntransruby">' . \tohtml($trans) . '</span> ';
            }
            if ($showRom && !$showTrans) {
                $output .= '<span class="annromrubysolo">' . \tohtml($rom) . '</span> ';
            }
            if ($showRom && $showTrans) {
                $output .= '<span class="annromruby" dir="ltr">[' . \tohtml($rom) . ']</span> ';
            }
            $output .= '</rt></ruby> ';
            return $output;
        }
        return \tohtml($term);
    }

    /**
     * Format term with annotation behind.
     *
     * @param string $term      Term text
     * @param string $rom       Romanization
     * @param string $trans     Translation
     * @param bool   $showRom   Show romanization
     * @param bool   $showTrans Show translation
     *
     * @return string HTML output
     */
    private function formatTermBehind(
        string $term,
        string $rom,
        string $trans,
        bool $showRom,
        bool $showTrans
    ): string {
        $output = '';
        if ($showRom || $showTrans) {
            $output .= ' <span class="annterm">';
        }
        $output .= \tohtml($term);
        if ($showRom || $showTrans) {
            $output .= '</span> ';
            if ($showRom && !$showTrans) {
                $output .= '<span class="annrom">' . \tohtml($rom) . '</span>';
            }
            if ($showRom && $showTrans) {
                $output .= '<span class="annrom" dir="ltr">[' . \tohtml($rom) . ']</span> ';
            }
            if ($showTrans) {
                $output .= '<span class="anntrans">' . \tohtml($trans) . '</span>';
            }
            $output .= ' ';
        }
        return $output;
    }
}
