<?php

/**
 * Term Edit Controller
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

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * Controller for creating and editing single-word terms.
 *
 * Handles:
 * - /word/edit - Edit word form
 * - /word/new - Create new word
 * - /word/inline-edit - Inline edit translation/romanization
 * - /word/edit-term - Edit term during review
 * - /word/delete-term - Delete term (iframe view)
 *
 * @since 3.0.0
 */
class TermEditController extends VocabularyBaseController
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
     * Edit word by ID.
     *
     * Route: GET/POST /words/{id}/edit
     *
     * @param int $id Word ID from route parameter
     *
     * @return void
     */
    public function editWordById(int $id): void
    {
        $op = InputValidator::getString('op');

        if ($op !== '') {
            if ($this->handleEditWordOperation()) {
                return; // Error was rendered with full page
            }
        } else {
            $this->displayEditWordForm($id, 0, 0, '');
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Edit word form.
     *
     * Handles:
     * - Display edit form: ?wid=[wordid] or ?tid=[textid]&ord=[ord]
     * - Save/Update: ?op=Save or ?op=Change
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function editWord(array $params): void
    {
        $wid = InputValidator::getString('wid');
        $tid = InputValidator::getString('tid');
        $ord = InputValidator::getString('ord');
        $op = InputValidator::getString('op');

        // Check for valid entry point
        if ($wid === '' && $tid . $ord === '' && $op === '') {
            return;
        }

        $fromAnn = InputValidator::getString('fromAnn');

        if ($op !== '') {
            if ($this->handleEditWordOperation()) {
                return; // Error was rendered with full page
            }
        } else {
            $widInt = ($wid !== '' && is_numeric($wid)) ? (int) $wid : -1;
            $textId = InputValidator::getInt('tid', 0) ?? 0;
            $ordInt = InputValidator::getInt('ord', 0) ?? 0;
            $this->displayEditWordForm($widInt, $textId, $ordInt, $fromAnn);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle save/update operation for word edit.
     *
     * @return bool True if error response was rendered, false otherwise
     */
    private function handleEditWordOperation(): bool
    {
        $textlc = trim(Escaping::prepareTextdata(InputValidator::getString('WoTextLC')));
        $text = trim(Escaping::prepareTextdata(InputValidator::getString('WoText')));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                'Error: Term in lowercase must be exactly = "' . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8') .
                '", please go back and correct this!</div>';
            PageLayoutHelper::renderPageEnd();
            return true;
        }

        $translation = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
        if ($translation == '') {
            $translation = '*';
        }

        $op = InputValidator::getString('op');
        $requestData = $this->getWordFormData();

        if ($op == 'Save') {
            // Insert new term
            $result = $this->getCrudService()->create($requestData);
            $hex = $this->getContextService()->textToClassName(InputValidator::getString('WoTextLC'));
            $oldStatus = 0;
            $titletext = "New Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
        } else {
            // Update existing term
            $result = $this->getCrudService()->update(InputValidator::getInt('WoID', 0) ?? 0, $requestData);
            $hex = null;
            $oldStatus = InputValidator::getString('WoOldStatus');
            $titletext = "Edit Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
        }

        PageLayoutHelper::renderPageStartNobody($titletext);
        echo '<h1>' . $titletext . '</h1>';

        $wid = $result['id'];
        $message = $result['message'];

        TagsFacade::saveWordTagsFromForm($wid);

        // Prepare view variables
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $status = InputValidator::getString('WoStatus');
        $romanization = InputValidator::getString('WoRomanization');

        $this->render('edit_result', [
            'wid' => $wid,
            'message' => $message,
            'textId' => $textId,
            'status' => $status,
            'romanization' => $romanization,
            'translation' => $translation,
            'hex' => $hex,
            'oldStatus' => $oldStatus,
            'isNew' => ($op == 'Save'),
        ]);

        return false;
    }

    /**
     * Display the word edit form (new or existing).
     *
     * @param int    $wid     Word ID (-1 for new)
     * @param int    $textId  Text ID
     * @param int    $ord     Word order position
     * @param string $fromAnn From annotation flag
     *
     * @return void
     */
    private function displayEditWordForm(int $wid, int $textId, int $ord, string $fromAnn): void
    {
        $crudService = $this->getCrudService();
        $contextService = $this->getContextService();
        $linkingService = $this->getLinkingService();

        if ($wid == -1) {
            // Get the term from text items
            $termData = $linkingService->getTermFromTextItem($textId, $ord);
            if ($termData === null) {
                throw new \RuntimeException("Cannot access term and language: term not found in text");
            }
            $term = (string) $termData['Ti2Text'];
            $lang = (int) $termData['Ti2LgID'];
            $termlc = mb_strtolower($term, 'UTF-8');

            // Check if word already exists
            $existingId = $crudService->findByText($termlc, $lang);
            if ($existingId !== null) {
                $new = false;
                $wid = $existingId;
            } else {
                $new = true;
            }
        } else {
            // Get existing word data
            $wordData = $crudService->findById($wid);
            if ($wordData === null) {
                throw new \RuntimeException("Cannot access term and language: word ID not found");
            }
            $term = (string) $wordData['WoText'];
            $lang = (int) $wordData['WoLgID'];
            $termlc = mb_strtolower($term, 'UTF-8');
            $new = false;
        }

        $titletext = ($new ? "New Term" : "Edit Term") . ": " . htmlspecialchars($term, ENT_QUOTES, 'UTF-8');
        PageLayoutHelper::renderPageStartNobody($titletext);

        $scrdir = $this->languageFacade->getScriptDirectionTag($lang);
        $langData = $contextService->getLanguageData($lang);
        $showRoman = $langData['showRoman'];

        if ($new) {
            // New word form
            $sentence = $contextService->getSentenceForTerm($textId, $ord, $termlc);
            $transUri = $langData['translateUri'];
            $lgname = $langData['name'];
            $langShort = array_key_exists($lgname, LanguagePresets::getAll()) ?
                LanguagePresets::getAll()[$lgname][1] : '';

            $this->render('form_edit_new', [
                'term' => $term,
                'termlc' => $termlc,
                'lang' => $lang,
                'sentence' => $sentence,
                'transUri' => $transUri,
                'lgname' => $lgname,
                'langShort' => $langShort,
                'scrdir' => $scrdir,
                'showRoman' => $showRoman,
                'textId' => $textId,
                'ord' => $ord,
                'dictionaryAdapter' => $this->dictionaryAdapter,
            ]);
        } else {
            // Edit existing word form
            $wordData = $crudService->findById($wid);
            if ($wordData === null) {
                throw new \RuntimeException("Cannot access word data: word ID not found");
            }

            $status = (int)$wordData['WoStatus'];
            if ($fromAnn == '' && $status >= 98) {
                $status = 1;
            }

            $sentence = ExportService::replaceTabNewline((string)$wordData['WoSentence']);
            if ($sentence == '' && $textId !== 0 && $ord !== 0) {
                $sentence = $contextService->getSentenceForTerm($textId, $ord, $termlc);
            }

            $transl = ExportService::replaceTabNewline((string)$wordData['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }

            // Get showRoman from language joined with text
            $showRoman = (bool) QueryBuilder::table('languages')
                ->join('texts', 'TxLgID', '=', 'LgID')
                ->where('TxID', '=', $textId)
                ->valuePrepared('LgShowRomanization');

            $this->render('form_edit_existing', [
                'wid' => $wid,
                'term' => $term,
                'termlc' => $termlc,
                'lang' => $lang,
                'status' => $status,
                'sentence' => $sentence,
                'transl' => $transl,
                'wordData' => $wordData,
                'scrdir' => $scrdir,
                'showRoman' => $showRoman,
                'textId' => $textId,
                'ord' => $ord,
                'dictionaryAdapter' => $this->dictionaryAdapter,
            ]);
        }
    }

    /**
     * Edit term while testing.
     *
     * Call: ?wid=[wordid] - display edit form
     *       ?op=Change - update the term
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function editTerm(array $params): void
    {
        $translation_raw = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
        $translation = ($translation_raw == '') ? '*' : $translation_raw;

        $op = InputValidator::getString('op');
        if ($op !== '') {
            if ($this->handleEditTermOperation($translation)) {
                return; // Error was rendered with full page
            }
        } else {
            $this->displayEditTermForm();
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle update operation for edit term.
     *
     * @param string $translation Translation value
     *
     * @return bool True if error response was rendered, false otherwise
     */
    private function handleEditTermOperation(string $translation): bool
    {
        $woTextLC = InputValidator::getString('WoTextLC');
        $woText = InputValidator::getString('WoText');
        $textlc = trim(Escaping::prepareTextdata($woTextLC));
        $text = trim(Escaping::prepareTextdata($woText));

        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $escapedText = htmlspecialchars(Escaping::prepareTextdata($woTextLC), ENT_QUOTES, 'UTF-8');
            $titletext = "New/Edit Term: " . $escapedText;
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                'Error: Term in lowercase must be exactly = "' . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8') .
                '", please go back and correct this!</div>';
            PageLayoutHelper::renderPageEnd();
            return true;
        }

        $op = InputValidator::getString('op');
        if ($op == 'Change') {
            $titletext = "Edit Term: " . htmlspecialchars(Escaping::prepareTextdata($woTextLC), ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $oldstatus = InputValidator::getString('WoOldStatus');
            $newstatus = InputValidator::getString('WoStatus');
            $woId = InputValidator::getInt('WoID', 0) ?? 0;
            $woSentence = InputValidator::getString('WoSentence');
            $woRomanization = InputValidator::getString('WoRomanization');

            $scoreRandomUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');
            $sentenceEscaped = ExportService::replaceTabNewline($woSentence);

            if ($oldstatus != $newstatus) {
                // Status changed - update with status change timestamp
                $bindings = [
                    $woText, $translation, $sentenceEscaped, $woRomanization,
                    $newstatus, $woId
                ];
                $sql = "UPDATE words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?,
                    WoStatus = ?, WoStatusChanged = NOW(), {$scoreRandomUpdate}
                    WHERE WoID = ?"
                    . \Lwt\Shared\Infrastructure\Database\UserScopedQuery::forTablePrepared('words', $bindings);
                Connection::preparedExecute($sql, $bindings);
            } else {
                // Status unchanged
                $bindings = [
                    $woText, $translation, $sentenceEscaped, $woRomanization,
                    $woId
                ];
                $sql = "UPDATE words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?,
                    {$scoreRandomUpdate}
                    WHERE WoID = ?"
                    . \Lwt\Shared\Infrastructure\Database\UserScopedQuery::forTablePrepared('words', $bindings);
                Connection::preparedExecute($sql, $bindings);
            }
            $wid = $woId;
            TagsFacade::saveWordTagsFromForm($wid);

            $message = 'Updated';

            /** @var int|null $lang */
            $lang = QueryBuilder::table('words')
                ->where('WoID', '=', $wid)
                ->valuePrepared('WoLgID');
            if (!isset($lang)) {
                throw new \RuntimeException('Cannot retrieve language: word not found');
            }
            /** @var string|null $regexword */
            $regexword = QueryBuilder::table('languages')
                ->where('LgID', '=', $lang)
                ->valuePrepared('LgRegexpWordCharacters');
            if (!isset($regexword)) {
                throw new \RuntimeException('Cannot retrieve language data: language not found');
            }
            $sent = htmlspecialchars(ExportService::replaceTabNewline($woSentence), ENT_QUOTES, 'UTF-8');
            $sent1 = str_replace(
                "{",
                ' <b>[',
                str_replace(
                    "}",
                    ']</b> ',
                    ExportService::maskTermInSentence($sent, $regexword)
                )
            );

            $status = $newstatus;
            $romanization = $woRomanization;
            $text = $woText;

            $this->render('edit_term_result', [
                'wid' => $wid,
                'message' => $message,
                'status' => $status,
                'romanization' => $romanization,
                'translation' => $translation,
                'text' => $text,
                'sent1' => $sent1,
            ]);
        }

        return false;
    }

    /**
     * Display the edit term form.
     *
     * @return void
     */
    private function displayEditTermForm(): void
    {
        $widParam = InputValidator::getString('wid');

        if ($widParam == '') {
            throw new \RuntimeException("Term ID missing: required parameter not provided");
        }
        $wid = (int) $widParam;

        $record = QueryBuilder::table('words')
            ->select(['WoText', 'WoLgID', 'WoTranslation', 'WoSentence', 'WoNotes', 'WoRomanization', 'WoStatus'])
            ->where('WoID', '=', $wid)
            ->firstPrepared();
        if ($record !== null) {
            $term = (string) $record['WoText'];
            $lang = (int) $record['WoLgID'];
            $transl = ExportService::replaceTabNewline((string)$record['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }
            $sentence = ExportService::replaceTabNewline((string)$record['WoSentence']);
            $notes = ExportService::replaceTabNewline((string)($record['WoNotes'] ?? ''));
            $rom = (string)$record['WoRomanization'];
            $status = (int)$record['WoStatus'];
            $showRoman = (bool) QueryBuilder::table('languages')
                ->where('LgID', '=', $lang)
                ->valuePrepared('LgShowRomanization');
        } else {
            throw new \RuntimeException("Term data not found: invalid term ID");
        }

        $termlc = mb_strtolower($term, 'UTF-8');
        $titletext = "Edit Term: " . htmlspecialchars($term, ENT_QUOTES, 'UTF-8');
        PageLayoutHelper::renderPageStartNobody($titletext);
        $scrdir = $this->languageFacade->getScriptDirectionTag($lang);

        $this->render('form_edit_term', [
            'wid' => $wid,
            'term' => $term,
            'termlc' => $termlc,
            'lang' => $lang,
            'transl' => $transl,
            'sentence' => $sentence,
            'notes' => $notes,
            'rom' => $rom,
            'status' => $status,
            'showRoman' => $showRoman,
            'scrdir' => $scrdir,
            'dictionaryAdapter' => $this->dictionaryAdapter,
        ]);
    }

    /**
     * Inline edit word.
     *
     * Handles AJAX inline editing of translation or romanization fields.
     * POST parameters:
     * - id: string - Field identifier (e.g., "trans123" or "roman123" where 123 is word ID)
     * - value: string - New value for the field
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function inlineEdit(array $params): void
    {
        $value = InputValidator::getStringFromPost('value');
        $id = InputValidator::getStringFromPost('id');

        if (substr($id, 0, 5) === 'trans') {
            $wordId = (int) substr($id, 5);
            $term = $this->facade->getTerm($wordId);
            if ($term === null) {
                echo 'ERROR - term not found!';
                return;
            }
            $this->facade->updateTerm($wordId, null, $value ?: '*', null, null, null);
            $displayValue = $value ?: '*';
            echo htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8');
            return;
        }

        if (substr($id, 0, 5) === 'roman') {
            $wordId = (int) substr($id, 5);
            $term = $this->facade->getTerm($wordId);
            if ($term === null) {
                echo 'ERROR - term not found!';
                return;
            }
            $this->facade->updateTerm($wordId, null, null, null, null, $value);
            echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            return;
        }

        echo 'ERROR - please refresh page!';
    }

    /**
     * Create new word form.
     *
     * Handles:
     * - Display form: ?lang=[langid]&text=[textid]
     * - Save: ?op=Save
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function createWord(array $params): void
    {
        $op = InputValidator::getString('op');
        $crudService = $this->getCrudService();
        $contextService = $this->getContextService();

        // Handle save operation
        if ($op === 'Save') {
            $requestData = $this->getWordFormData();
            $result = $crudService->create($requestData);

            $titletext = "New Term: " . htmlspecialchars($result['textlc'] ?? '', ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            if (!$result['success']) {
                // Handle duplicate entry error
                if (strpos($result['message'], 'Duplicate entry') !== false) {
                    $message = 'Error: <b>Duplicate entry for <i>' . $result['textlc'] .
                        '</i></b><br /><br /><input type="button" value="&lt;&lt; Back" data-action="back" />';
                } else {
                    $message = $result['message'];
                }
                echo '<p>' . $message . '</p>';
            } else {
                $wid = $result['id'];
                TagsFacade::saveWordTagsFromForm($wid);
                \Lwt\Shared\Infrastructure\Database\Maintenance::initWordCount();

                echo '<p>' . $result['message'] . '</p>';

                $woLgId = InputValidator::getInt('WoLgID', 0) ?? 0;
                $len = $crudService->getWordCount($wid);
                if ($len > 1) {
                    $this->getExpressionService()->insertExpressions($result['textlc'], $woLgId, $wid, $len, 0);
                } elseif ($len == 1) {
                    $this->getLinkingService()->linkToTextItems($wid, $woLgId, $result['textlc']);

                    // Prepare view variables
                    $hex = $contextService->textToClassName($result['textlc']);
                    $translation = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
                    if ($translation === '') {
                        $translation = '*';
                    }
                    $status = InputValidator::getString('WoStatus');
                    $romanization = InputValidator::getString('WoRomanization');
                    $text = $result['text'];
                    $textId = InputValidator::getInt('tid', 0) ?? 0;
                    $success = true;
                    $message = $result['message'];

                    $this->render('save_result', [
                        'wid' => $wid,
                        'hex' => $hex,
                        'translation' => $translation,
                        'status' => $status,
                        'romanization' => $romanization,
                        'text' => $text,
                        'textId' => $textId,
                        'success' => $success,
                        'message' => $message,
                    ]);
                }
            }
        } else {
            // Display the new word form
            $lang = InputValidator::getInt('lang', 0) ?? 0;
            $textId = InputValidator::getInt('text', 0) ?? 0;
            $scrdir = $this->languageFacade->getScriptDirectionTag($lang);

            $langData = $contextService->getLanguageData($lang);
            $showRoman = $langData['showRoman'];
            $dictService = $this->dictionaryAdapter;

            PageLayoutHelper::renderPageStartNobody('');

            $this->render('form_new', [
                'lang' => $lang,
                'textId' => $textId,
                'scrdir' => $scrdir,
                'showRoman' => $showRoman,
                'dictService' => $dictService,
                'langData' => $langData,
            ]);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Get form data for word create/update operations.
     *
     * @return array<string, mixed> Form data array
     */
    private function getWordFormData(): array
    {
        return [
            'WoID' => InputValidator::getInt('WoID'),
            'WoLgID' => InputValidator::getInt('WoLgID', 0) ?? 0,
            'WoText' => InputValidator::getString('WoText'),
            'WoTextLC' => InputValidator::getString('WoTextLC'),
            'WoStatus' => InputValidator::getString('WoStatus'),
            'WoOldStatus' => InputValidator::getString('WoOldStatus'),
            'WoTranslation' => InputValidator::getString('WoTranslation'),
            'WoRomanization' => InputValidator::getString('WoRomanization'),
            'WoSentence' => InputValidator::getString('WoSentence'),
            'tid' => InputValidator::getInt('tid'),
            'ord' => InputValidator::getInt('ord'),
            'len' => InputValidator::getInt('len'),
        ];
    }

    /**
     * Delete word (iframe view).
     *
     * Replaces delete_word.php - deletes a word and renders confirmation.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function deleteWordView(array $params): void
    {
        $wid = InputValidator::getInt('wid', 0) ?? 0;
        $textId = InputValidator::getInt('tid', 0) ?? 0;

        if ($wid === 0) {
            PageLayoutHelper::renderPageStartNobody('Error');
            echo '<p>Invalid word ID</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        // Get term info before deletion for display
        $term = $this->facade->getTerm($wid);
        $termText = $term !== null ? $term->text() : '';
        $termTextLc = $term !== null ? $term->textLowercase() : '';

        // Delete the term
        $result = $this->facade->deleteTerm($wid);

        PageLayoutHelper::renderPageStartNobody('Term Deleted');

        $this->render('delete_result', [
            'wid' => $wid,
            'textId' => $textId,
            'deleted' => $result,
            'term' => $termText,
            'termLc' => $termTextLc,
        ]);

        PageLayoutHelper::renderPageEnd();
    }
}
