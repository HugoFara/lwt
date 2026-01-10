<?php declare(strict_types=1);
/**
 * Multi-Word Controller
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
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for multi-word expression management.
 *
 * Handles:
 * - /word/edit-multi - Create/edit multi-word expressions
 * - /word/delete-multi - Delete multi-word expressions
 *
 * @since 3.0.0
 */
class MultiWordController extends VocabularyBaseController
{
    /**
     * Vocabulary facade.
     */
    private VocabularyFacade $facade;

    /**
     * Adapters.
     */
    private DictionaryAdapter $dictionaryAdapter;

    /**
     * Services.
     */
    private LanguageFacade $languageFacade;

    /**
     * Constructor.
     *
     * @param VocabularyFacade|null  $facade            Vocabulary facade
     * @param DictionaryAdapter|null $dictionaryAdapter Dictionary adapter
     * @param LanguageFacade|null    $languageFacade    Language facade
     */
    public function __construct(
        ?VocabularyFacade $facade = null,
        ?DictionaryAdapter $dictionaryAdapter = null,
        ?LanguageFacade $languageFacade = null
    ) {
        parent::__construct();
        $this->facade = $facade ?? new VocabularyFacade();
        $this->dictionaryAdapter = $dictionaryAdapter ?? new DictionaryAdapter();
        $this->languageFacade = $languageFacade ?? new LanguageFacade();
    }

    /**
     * Edit multi-word expression.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function editMulti(array $params): void
    {
        $op = InputValidator::getString('op');
        if ($op !== '') {
            // Handle save/update operation
            $this->handleMultiWordOperation();
        } else {
            // Display form
            $this->displayMultiWordForm();
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle multi-word save/update operation.
     *
     * @return void
     */
    private function handleMultiWordOperation(): void
    {
        $textlc = trim(InputValidator::getString('WoTextLC'));
        $text = trim(InputValidator::getString('WoText'));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                'Error: Term in lowercase must be exactly = "' . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8') .
                '", please go back and correct this!</div>';
            return;
        }

        $translationRaw = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
        $translation = ($translationRaw == '') ? '*' : $translationRaw;

        $woText = InputValidator::getString('WoText');
        $woRomanization = InputValidator::getString('WoRomanization');
        $woSentence = InputValidator::getString('WoSentence');
        $woStatus = InputValidator::getInt('WoStatus', 0) ?? 0;
        $data = [
            'text' => Escaping::prepareTextdata($woText),
            'textlc' => Escaping::prepareTextdata($textlc),
            'translation' => $translation,
            'roman' => $woRomanization,
            'sentence' => $woSentence,
        ];

        $op = InputValidator::getString('op');
        $multiWordService = $this->getMultiWordService();
        $contextService = $this->getContextService();

        if ($op == 'Save') {
            // Insert new multi-word
            $data['status'] = $woStatus;
            $data['lgid'] = InputValidator::getInt('WoLgID', 0) ?? 0;
            $data['wordcount'] = InputValidator::getInt('len', 0) ?? 0;

            $titletext = "New Term: " . htmlspecialchars($data['textlc'], ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $multiWordService->createMultiWord($data);
            $wid = $result['id'];
        } else {
            // Update existing multi-word
            $wid = InputValidator::getInt('WoID', 0) ?? 0;
            $oldStatus = InputValidator::getInt('WoOldStatus', 0) ?? 0;
            $newStatus = $woStatus;

            $titletext = "Edit Term: " . htmlspecialchars($data['textlc'], ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $multiWordService->updateMultiWord($wid, $data, $oldStatus, $newStatus);

            // Prepare data for view
            $tagList = TagsFacade::getWordTagList($wid, false);
            $formattedTags = $tagList !== '' ? ' [' . $tagList . ']' : '';
            $termJson = $contextService->exportTermAsJson(
                $wid,
                $data['text'],
                $data['roman'],
                $translation . $formattedTags,
                $newStatus
            );
            $oldStatusValue = $oldStatus;

            $this->render('edit_multi_update_result', [
                'wid' => $wid,
                'result' => $result,
                'termJson' => $termJson,
                'oldStatusValue' => $oldStatusValue,
            ]);
        }
    }

    /**
     * Display multi-word edit form (new or existing).
     *
     * @return void
     */
    private function displayMultiWordForm(): void
    {
        $tid = InputValidator::getInt('tid', 0) ?? 0;
        $ord = InputValidator::getInt('ord', 0) ?? 0;
        $strWid = InputValidator::getString('wid');
        $contextService = $this->getContextService();
        $multiWordService = $this->getMultiWordService();

        // Determine if we're editing an existing word or creating new
        if ($strWid == "" || !is_numeric($strWid)) {
            // No ID provided: check if text exists in database
            $lgid = $contextService->getLanguageIdFromText($tid);
            $txtParam = InputValidator::getString('txt');
            $textlc = mb_strtolower(
                Escaping::prepareTextdata($txtParam),
                'UTF-8'
            );

            $strWid = $multiWordService->findMultiWordByText($textlc, (int) $lgid);
        }

        if ($strWid === null) {
            // New multi-word
            $txtParam = InputValidator::getString('txt');
            $len = InputValidator::getInt('len', 0) ?? 0;
            PageLayoutHelper::renderPageStartNobody("New Term: " . $txtParam);
            $this->displayNewMultiWordForm($txtParam, $tid, $ord, $len);
        } else {
            // Edit existing multi-word
            $wid = (int) $strWid;
            $wordData = $multiWordService->getMultiWordData($wid);
            if ($wordData === null) {
                throw new \RuntimeException("Cannot access term and language: multi-word not found");
            }
            PageLayoutHelper::renderPageStartNobody("Edit Term: " . $wordData['text']);
            $this->displayEditMultiWordForm($wid, $wordData, $tid, $ord);
        }
    }

    /**
     * Display form for new multi-word.
     *
     * @param string $text Original text
     * @param int    $tid  Text ID
     * @param int    $ord  Text order
     * @param int    $len  Number of words
     *
     * @return void
     */
    private function displayNewMultiWordForm(string $text, int $tid, int $ord, int $len): void
    {
        $contextService = $this->getContextService();
        $multiWordService = $this->getMultiWordService();
        $lgid = $contextService->getLanguageIdFromText($tid);
        $termText = Escaping::prepareTextdata($text);
        $textlc = mb_strtolower($termText, 'UTF-8');

        // Check if word already exists
        $existingWid = $multiWordService->findMultiWordByText($textlc, (int) $lgid);
        if ($existingWid !== null) {
            // Get text from existing word
            $wordData = $multiWordService->getMultiWordData($existingWid);
            if ($wordData !== null) {
                /** @var string $termText */
                $termText = $wordData['text'];
            }
        }

        $scrdir = $this->languageFacade->getScriptDirectionTag((int) $lgid);
        $seid = $contextService->getSentenceIdAtPosition($tid, $ord) ?? 0;
        $sent = $this->getSentenceService()->formatSentence(
            $seid,
            $textlc,
            (int) Settings::getWithDefault('set-term-sentence-count')
        );
        $showRoman = $contextService->shouldShowRomanization($tid);

        // Variables for view
        $term = (object) [
            'lgid' => $lgid,
            'text' => $termText,
            'textlc' => $textlc,
            'id' => $existingWid
        ];
        $sentence = ExportService::replaceTabNewline($sent[1] ?? '');

        $this->render('form_edit_multi_new', [
            'term' => $term,
            'sentence' => $sentence,
            'scrdir' => $scrdir,
            'showRoman' => $showRoman,
            'tid' => $tid,
            'ord' => $ord,
            'len' => $len,
            'dictionaryAdapter' => $this->dictionaryAdapter,
        ]);
    }

    /**
     * Display form for editing existing multi-word.
     *
     * @param int                        $wid      Word ID
     * @param array{text: string, lgid: int, translation: string, sentence: string, notes: string, romanization: string, status: int} $wordData Word data from service
     * @param int                        $tid      Text ID
     * @param int                        $ord      Text order
     *
     * @return void
     */
    private function displayEditMultiWordForm(int $wid, array $wordData, int $tid, int $ord): void
    {
        $lgid = $wordData['lgid'];
        $termText = $wordData['text'];
        $textlc = mb_strtolower($termText, 'UTF-8');

        $scrdir = $this->languageFacade->getScriptDirectionTag($lgid);
        $showRoman = $this->getContextService()->shouldShowRomanization($tid);

        $this->render('form_edit_multi_existing', [
            'wid' => $wid,
            'wordData' => $wordData,
            'termText' => $termText,
            'textlc' => $textlc,
            'lgid' => $lgid,
            'scrdir' => $scrdir,
            'showRoman' => $showRoman,
            'tid' => $tid,
            'ord' => $ord,
            'dictionaryAdapter' => $this->dictionaryAdapter,
        ]);
    }

    /**
     * Delete multi-word expression (iframe view).
     *
     * Replaces delete_mword.php - deletes a multi-word and renders confirmation.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function deleteMultiWordView(array $params): void
    {
        $wid = InputValidator::getInt('wid', 0) ?? 0;
        $textId = InputValidator::getInt('tid', 0) ?? 0;

        if ($wid === 0) {
            PageLayoutHelper::renderPageStartNobody('Error');
            echo '<p>Invalid word ID</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        // Get term info before deletion
        $term = $this->facade->getTerm($wid);
        $showAll = (bool) Settings::getWithDefault('set-show-all-words');

        // Delete the multi-word
        $result = $this->getMultiWordService()->deleteMultiWord($wid);

        PageLayoutHelper::renderPageStartNobody('Term Deleted');

        $this->render('delete_multi_result', [
            'wid' => $wid,
            'textId' => $textId,
            'deleted' => $result > 0,
            'showAll' => $showAll,
        ]);

        PageLayoutHelper::renderPageEnd();
    }
}
