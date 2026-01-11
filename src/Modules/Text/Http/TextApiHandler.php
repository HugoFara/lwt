<?php

/**
 * Text API Handler
 *
 * Handler for text-related API operations in the Text module.
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

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Vocabulary\Application\Services\WordDiscoveryService;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Text\Application\Services\AnnotationService;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Text\Application\Services\TextPrintService;
use Lwt\Modules\Text\Application\Services\TextScoringService;
use Lwt\Shared\UI\Helpers\IconHelper;

require_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/WordDiscoveryService.php';
require_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExportService.php';
require_once dirname(__DIR__, 2) . '/Vocabulary/Infrastructure/DictionaryAdapter.php';
require_once dirname(__DIR__) . '/Application/TextFacade.php';
require_once dirname(__DIR__) . '/Application/Services/TextPrintService.php';

/**
 * Handler for text-related API operations.
 *
 * Extracted from api_v1.php lines 262-373.
 */
class TextApiHandler
{
    private WordDiscoveryService $discoveryService;
    private TextFacade $textService;

    public function __construct(?WordDiscoveryService $discoveryService = null)
    {
        $this->discoveryService = $discoveryService ?? new WordDiscoveryService();
        $this->textService = new TextFacade();
    }
    /**
     * Save the reading position of the text.
     *
     * @param int $textid   Text ID
     * @param int $position Position in text to save
     *
     * @return void
     */
    public function saveTextPosition(int $textid, int $position): void
    {
        QueryBuilder::table('texts')
            ->where('TxID', '=', $textid)
            ->updatePrepared(['TxPosition' => $position]);
    }

    /**
     * Save the audio position in the text.
     *
     * @param int $textid        Text ID
     * @param int $audioposition Audio position
     *
     * @return void
     */
    public function saveAudioPosition(int $textid, int $audioposition): void
    {
        QueryBuilder::table('texts')
            ->where('TxID', '=', $textid)
            ->updatePrepared(['TxAudioPosition' => $audioposition]);
    }

    /**
     * Save data from printed text.
     *
     * @param int    $textid Text ID
     * @param int    $line   Line number to save
     * @param string $val    Proposed new annotation for the term
     *
     * @return string Error message, or "OK" if success.
     */
    public function saveImprTextData(int $textid, int $line, string $val): string
    {
        $ann = (string) QueryBuilder::table('texts')
            ->where('TxID', '=', $textid)
            ->valuePrepared('TxAnnotatedText');

        $items = preg_split('/[\n]/u', $ann);
        if (count($items) <= $line) {
            return "Unreachable translation: line request is $line, but only " .
            count($items) . " translations were found";
        }

        $vals = preg_split('/[\t]/u', $items[$line]);
        if ((int)$vals[0] <= -1) {
            return "Term is punctation! Term position is {$vals[0]}";
        }
        if (count($vals) < 4) {
            return "Not enough columns: " . count($vals);
        }

        $items[$line] = implode("\t", array($vals[0], $vals[1], $vals[2], $val));

        QueryBuilder::table('texts')
            ->where('TxID', '=', $textid)
            ->updatePrepared(['TxAnnotatedText' => implode("\n", $items)]);

        return "OK";
    }

    /**
     * Save a text with improved annotations.
     *
     * @param int    $textid Text ID
     * @param string $elem   Element to select
     * @param object $data   Data element
     *
     * @return array{error?: string, success?: string}
     */
    public function saveImprText(int $textid, string $elem, object $data): array
    {
        $newAnnotation = (string)($data->{$elem} ?? '');
        $line = (int)substr($elem, 2);
        if (str_starts_with($elem, "rg") && $newAnnotation == "") {
            $newAnnotation = (string)($data->{'tx' . $line} ?? '');
        }
        $status = $this->saveImprTextData($textid, $line, $newAnnotation);
        if ($status != "OK") {
            return ["error" => $status];
        }
        return ["success" => $status];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for setting text position.
     *
     * @param int $textId   Text ID
     * @param int $position Position
     *
     * @return array{text: string}
     */
    public function formatSetTextPosition(int $textId, int $position): array
    {
        $this->saveTextPosition($textId, $position);
        return ["text" => "Reading position set"];
    }

    /**
     * Format response for setting audio position.
     *
     * @param int $textId   Text ID
     * @param int $position Audio position
     *
     * @return array{audio: string}
     */
    public function formatSetAudioPosition(int $textId, int $position): array
    {
        $this->saveAudioPosition($textId, $position);
        return ["audio" => "Audio position set"];
    }

    /**
     * Format response for setting annotation.
     *
     * @param int    $textId Text ID
     * @param string $elem   Element selector
     * @param string $data   JSON-encoded data
     *
     * @return array{save_impr_text?: string|null, error?: string}
     */
    public function formatSetAnnotation(int $textId, string $elem, string $data): array
    {
        $decoded = json_decode($data);
        if (!is_object($decoded)) {
            return ["error" => "Invalid JSON data"];
        }
        $result = $this->saveImprText($textId, $elem, $decoded);
        if (array_key_exists("error", $result)) {
            return ["error" => $result["error"]];
        }
        return ["save_impr_text" => $result["success"] ?? null];
    }

    // =========================================================================
    // New Phase 2 Methods
    // =========================================================================

    /**
     * Set display mode settings for a text.
     *
     * @param int       $textId       Text ID
     * @param int|null  $annotations  Annotation mode (0=none, 1=translations, 2=romanization, 3=both)
     * @param bool|null $romanization Whether to show romanization
     * @param bool|null $translation  Whether to show translation
     *
     * @return array{updated: bool, error?: string}
     */
    public function setDisplayMode(int $textId, ?int $annotations, ?bool $romanization, ?bool $translation): array
    {
        // Validate text exists
        $exists = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->existsPrepared();

        if (!$exists) {
            return ['updated' => false, 'error' => 'Text not found'];
        }

        // Save settings
        if ($annotations !== null) {
            Settings::save('set-text-h-annotations', (string)$annotations);
        }

        if ($romanization !== null) {
            Settings::save('set-display-romanization', $romanization ? '1' : '0');
        }

        if ($translation !== null) {
            Settings::save('set-display-translation', $translation ? '1' : '0');
        }

        return ['updated' => true];
    }

    /**
     * Mark all unknown words in a text as well-known.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function markAllWellKnown(int $textId): array
    {
        list($count, $wordsData) = $this->discoveryService->markAllWordsWithStatus($textId, 99);
        return [
            'count' => $count,
            'words' => $wordsData
        ];
    }

    /**
     * Mark all unknown words in a text as ignored.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function markAllIgnored(int $textId): array
    {
        list($count, $wordsData) = $this->discoveryService->markAllWordsWithStatus($textId, 98);
        return [
            'count' => $count,
            'words' => $wordsData
        ];
    }

    // =========================================================================
    // New API Response Formatters
    // =========================================================================

    /**
     * Format response for setting display mode.
     *
     * @param int   $textId Text ID
     * @param array $params Display mode parameters
     *
     * @return array{updated: bool, error?: string}
     */
    public function formatSetDisplayMode(int $textId, array $params): array
    {
        $annotations = isset($params['annotations']) ? (int)$params['annotations'] : null;
        $romanization = isset($params['romanization']) ? filter_var($params['romanization'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        $translation = isset($params['translation']) ? filter_var($params['translation'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        return $this->setDisplayMode($textId, $annotations, $romanization, $translation);
    }

    /**
     * Format response for marking all words as well-known.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function formatMarkAllWellKnown(int $textId): array
    {
        return $this->markAllWellKnown($textId);
    }

    /**
     * Format response for marking all words as ignored.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function formatMarkAllIgnored(int $textId): array
    {
        return $this->markAllIgnored($textId);
    }

    // =========================================================================
    // Text Words API (for client-side rendering)
    // =========================================================================

    /**
     * Get all words for a text for client-side rendering.
     *
     * Returns word tokens with position, status, translation, etc.
     *
     * @param int $textId Text ID
     *
     * @return array{words: array, config: array}|array{error: string}
     */
    public function getWords(int $textId): array
    {
        // Get text info and language settings
        $textInfo = QueryBuilder::table('texts')
            ->select(['TxID', 'TxLgID', 'TxTitle', 'TxAudioURI', 'TxSourceURI', 'TxAudioPosition'])
            ->where('TxID', '=', $textId)
            ->firstPrepared();

        if ($textInfo === null) {
            return ['error' => 'Text not found'];
        }

        $langId = (int)$textInfo['TxLgID'];

        // Get language info including dictionary URLs
        $langInfo = QueryBuilder::table('languages')
            ->select(
                ['LgID', 'LgName', 'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
                'LgTextSize', 'LgRightToLeft', 'LgRegexpWordCharacters', 'LgRemoveSpaces']
            )
            ->where('LgID', '=', $langId)
            ->firstPrepared();

        if ($langInfo === null) {
            return ['error' => 'Language not found'];
        }

        // Get all text items with word info
        $records = QueryBuilder::table('word_occurrences')
            ->select(
                [
                'CASE WHEN `Ti2WordCount`>0 THEN Ti2WordCount ELSE 1 END AS Code',
                'CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE `WoText` END AS TiText',
                'CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN LOWER(Ti2Text) ELSE `WoTextLC` END AS TiTextLC',
                'Ti2Order',
                'Ti2SeID',
                'CASE WHEN `Ti2WordCount`>0 THEN 0 ELSE 1 END AS TiIsNotWord',
                'CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN CHAR_LENGTH(Ti2Text) ELSE CHAR_LENGTH(`WoTextLC`) END AS TiTextLength',
                'WoID',
                'WoText',
                'WoStatus',
                'WoTranslation',
                'WoRomanization',
                'WoNotes'
                ]
            )
            ->leftJoin('words', 'word_occurrences.Ti2WoID', '=', 'words.WoID')
            ->where('word_occurrences.Ti2TxID', '=', $textId)
            ->orderBy('Ti2Order', 'ASC')
            ->orderBy('Ti2WordCount', 'DESC')
            ->getPrepared();

        $words = [];
        $exprs = [];
        $lastOrder = -1;

        foreach ($records as $record) {
            $code = (int)$record['Code'];
            $order = (int)$record['Ti2Order'];
            $isNotWord = (int)$record['TiIsNotWord'];

            // Handle multiword expressions tracking
            if ($code > 1) {
                if (empty($exprs) || $exprs[count($exprs) - 1]['text'] !== $record['TiText']) {
                    $exprs[] = [
                        'code' => $code,
                        'text' => $record['TiText'],
                        'remaining' => $code,
                        'startPos' => $order,
                        'wordId' => isset($record['WoID']) ? (int)$record['WoID'] : null,
                        'status' => (int)($record['WoStatus'] ?? 0),
                        'translation' => ExportService::replaceTabNewline((string)($record['WoTranslation'] ?? '')),
                    ];
                }
            }

            // Determine if hidden (for multiword display logic)
            $hidden = $order <= $lastOrder;

            // Calculate hex for TERM class
            $hex = StringUtils::toClassName((string)($record['TiTextLC'] ?? ''));

            // Build word data
            $wordData = [
                'position' => $order,
                'sentenceId' => (int)$record['Ti2SeID'],
                'text' => $record['TiText'] ?? '',
                'textLc' => $record['TiTextLC'] ?? '',
                'hex' => $hex,
                'isNotWord' => $isNotWord === 1,
                'wordCount' => $code,
                'hidden' => $hidden,
            ];

            if ($isNotWord === 0) {
                // It's a word or multiword
                if (isset($record['WoID'])) {
                    // Known word
                    $wordData['wordId'] = (int)$record['WoID'];
                    $wordData['status'] = (int)$record['WoStatus'];
                    $wordData['translation'] = ExportService::replaceTabNewline((string)($record['WoTranslation'] ?? ''));
                    $wordData['romanization'] = (string)($record['WoRomanization'] ?? '');
                    $wordData['notes'] = (string)($record['WoNotes'] ?? '');

                    // Get tags
                    $tags = TagsFacade::getWordTagList((int)$record['WoID'], false);
                    if ($tags) {
                        $wordData['tags'] = $tags;
                    }
                } else {
                    // Unknown word (status 0)
                    $wordData['wordId'] = null;
                    $wordData['status'] = 0;
                    $wordData['translation'] = '';
                    $wordData['romanization'] = '';
                    $wordData['notes'] = '';
                }

                // Add multiword references with full details
                foreach ($exprs as $expr) {
                    $wordData['mw' . $expr['code']] = [
                        'text' => $expr['text'],
                        'translation' => $expr['translation'],
                        'status' => $expr['status'],
                        'wordId' => $expr['wordId'],
                        'startPos' => $expr['startPos'],
                        'endPos' => $expr['startPos'] + ($expr['code'] - 1) * 2,
                    ];
                }
            }

            $words[] = $wordData;

            // Update tracking for single words
            if ($code === 1) {
                // Update expression counters
                for ($i = count($exprs) - 1; $i >= 0; $i--) {
                    $exprs[$i]['remaining']--;
                    if ($exprs[$i]['remaining'] < 1) {
                        array_splice($exprs, $i, 1);
                    }
                }
            }

            // Track last order for hidden calculation
            $lastOrder = max($lastOrder, $order + ($code - 1) * 2);
        }

        // Build config
        $showLearning = Settings::getZeroOrOne('showlearningtranslations', 1);
        $displayStatTrans = (int)Settings::getWithDefault('set-display-text-frame-term-translation');
        $modeTrans = (int)Settings::getWithDefault('set-text-frame-annotation-position');
        $termDelimiter = Settings::getWithDefault('set-term-translation-delimiters');
        $textSize = (int)$langInfo['LgTextSize'];

        $config = [
            'textId' => $textId,
            'langId' => $langId,
            'title' => $textInfo['TxTitle'],
            'audioUri' => $textInfo['TxAudioURI'],
            'sourceUri' => $textInfo['TxSourceURI'],
            'audioPosition' => (int)$textInfo['TxAudioPosition'],
            'rightToLeft' => (int)$langInfo['LgRightToLeft'] === 1,
            'textSize' => $textSize,
            'removeSpaces' => (int)$langInfo['LgRemoveSpaces'] === 1,
            'dictLinks' => [
                'dict1' => $langInfo['LgDict1URI'] ?? '',
                'dict2' => $langInfo['LgDict2URI'] ?? '',
                'translator' => $langInfo['LgGoogleTranslateURI'] ?? '',
            ],
            // Annotation/display settings
            'showLearning' => $showLearning,
            'displayStatTrans' => $displayStatTrans,
            'modeTrans' => $modeTrans,
            'termDelimiter' => $termDelimiter,
            // Annotation text size percentages (based on main text size)
            'annTextSize' => match ($textSize) {
                100 => 50,
                150 => 50,
                200 => 40,
                250 => 25,
                default => 50,
            },
        ];

        return [
            'words' => $words,
            'config' => $config,
        ];
    }

    /**
     * Format response for getting text words.
     *
     * @param int $textId Text ID
     *
     * @return array{words: array, config: array}|array{error: string}
     */
    public function formatGetWords(int $textId): array
    {
        return $this->getWords($textId);
    }

    /**
     * Format response for getting texts by language.
     *
     * Returns paginated texts for a specific language (for grouped texts page).
     *
     * @param int   $langId Language ID
     * @param array $params Query parameters (page, per_page, sort)
     *
     * @return array{texts: array, pagination: array}
     */
    public function formatTextsByLanguage(int $langId, array $params): array
    {
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['per_page']) ? max(1, min(100, (int)$params['per_page'])) : 10;
        $sort = isset($params['sort']) ? (int)$params['sort'] : 1;

        return $this->textService->getTextsForLanguage($langId, $page, $perPage, $sort);
    }

    /**
     * Format response for getting archived texts by language.
     *
     * Returns paginated archived texts for a specific language (for grouped archived texts page).
     *
     * @param int   $langId Language ID
     * @param array $params Query parameters (page, per_page, sort)
     *
     * @return array{texts: array, pagination: array}
     */
    public function formatArchivedTextsByLanguage(int $langId, array $params): array
    {
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['per_page']) ? max(1, min(100, (int)$params['per_page'])) : 10;
        $sort = isset($params['sort']) ? (int)$params['sort'] : 1;

        return $this->textService->getArchivedTextsForLanguage($langId, $page, $perPage, $sort);
    }

    // =========================================================================
    // Print Items API (for client-side print rendering)
    // =========================================================================

    /**
     * Get print items and configuration for a text.
     *
     * Returns structured text items suitable for client-side rendering
     * of the print view with annotation options.
     *
     * @param int $textId Text ID
     *
     * @return array{items: array, config: array}|array{error: string}
     */
    public function getPrintItems(int $textId): array
    {
        $printService = new TextPrintService();

        $viewData = $printService->preparePlainPrintData($textId);
        if ($viewData === null) {
            return ['error' => 'Text not found'];
        }

        $items = $printService->getTextItemsForApi($textId);

        // Get saved print settings
        $savedAnn = $printService->getAnnotationSetting(null);
        $savedStatus = $printService->getStatusRangeSetting(null);
        $savedPlacement = $printService->getAnnotationPlacementSetting(null);

        return [
            'items' => $items,
            'config' => [
                'textId' => $textId,
                'title' => $viewData['title'],
                'sourceUri' => $viewData['sourceUri'],
                'audioUri' => $viewData['audioUri'],
                'langId' => $viewData['langId'],
                'textSize' => $viewData['textSize'],
                'rtlScript' => $viewData['rtlScript'],
                'hasAnnotation' => $viewData['hasAnnotation'],
                'savedAnn' => $savedAnn,
                'savedStatus' => $savedStatus,
                'savedPlacement' => $savedPlacement
            ]
        ];
    }

    /**
     * Format response for getting print items.
     *
     * @param int $textId Text ID
     *
     * @return array{items: array, config: array}|array{error: string}
     */
    public function formatGetPrintItems(int $textId): array
    {
        return $this->getPrintItems($textId);
    }

    /**
     * Get annotation items for improved/annotated text view.
     *
     * Returns parsed annotation data for client-side rendering.
     *
     * @param int $textId Text ID
     *
     * @return array{items: array|null, config: array}|array{error: string}
     */
    public function getAnnotation(int $textId): array
    {
        $printService = new TextPrintService();

        $viewData = $printService->prepareAnnotatedPrintData($textId);
        if ($viewData === null) {
            return ['error' => 'Text not found'];
        }

        $items = $printService->getAnnotationForApi($textId);

        return [
            'items' => $items,
            'config' => [
                'textId' => $textId,
                'title' => $viewData['title'],
                'sourceUri' => $viewData['sourceUri'],
                'audioUri' => $viewData['audioUri'],
                'langId' => $viewData['langId'],
                'textSize' => $viewData['textSize'],
                'rtlScript' => $viewData['rtlScript'],
                'hasAnnotation' => $viewData['hasAnnotation'],
                'ttsClass' => $viewData['ttsClass']
            ]
        ];
    }

    /**
     * Format response for getting annotation.
     *
     * @param int $textId Text ID
     *
     * @return array{items: array|null, config: array}|array{error: string}
     */
    public function formatGetAnnotation(int $textId): array
    {
        return $this->getAnnotation($textId);
    }

    // =========================================================================
    // Improved/Annotated Text Methods (migrated from ImprovedTextHandler)
    // =========================================================================

    /**
     * Make the translations choices for a term.
     *
     * @param int      $i     Word unique index in the form
     * @param int|null $wid   Word ID or null
     * @param string   $trans Current translation set for the term, may be empty
     * @param string   $word  Term text
     * @param int      $lang  Language ID
     *
     * @return string HTML-formatted string
     */
    public function makeTrans(int $i, ?int $wid, string $trans, string $word, int $lang): string
    {
        $trans = trim($trans);
        $widset = is_numeric($wid);
        $r = "";
        $set = false;
        if ($widset) {
            $alltrans = (string) QueryBuilder::table('words')
                ->where('WoID', '=', $wid)
                ->valuePrepared('WoTranslation');
            $transarr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $alltrans);
            $set = false;
            foreach ($transarr as $t) {
                $tt = trim($t);
                if ($tt == '*' || $tt == '') {
                    continue;
                }
                $set = $set || $tt == $trans;
                $r .= '<span class="nowrap">
                    <input class="impr-ann-radio" ' .
                    ($tt == $trans ? 'checked="checked" ' : '') . 'type="radio" name="rg' .
                    $i . '" value="' . htmlspecialchars($tt, ENT_QUOTES, 'UTF-8') . '" />
                    &nbsp;' . htmlspecialchars($tt, ENT_QUOTES, 'UTF-8') . '
                </span>
                <br />';
            }
        }
        $r .= '<span class="nowrap">
        <input class="impr-ann-radio" type="radio" name="rg' . $i . '" ' .
        ($set ? 'checked="checked" ' : '') . 'value="" />
        &nbsp;
        <input class="impr-ann-text" type="text" name="tx' . $i .
        '" id="tx' . $i . '" value="' . ($set ? htmlspecialchars($trans, ENT_QUOTES, 'UTF-8') : '') .
        '" maxlength="50" size="40" />
         &nbsp;
' . IconHelper::render('eraser', ['title' => 'Erase Text Field', 'alt' => 'Erase Text Field', 'class' => 'click', 'data-action' => 'erase-field', 'data-target' => '#tx' . $i]) . '
         &nbsp;
        ' . IconHelper::render('star', ['title' => '* (Set to Term)', 'alt' => '* (Set to Term)', 'class' => 'click', 'data-action' => 'set-star', 'data-target' => '#tx' . $i]) . '
        &nbsp;';
        if ($widset) {
            $r .=
            IconHelper::render('circle-plus', ['title' => 'Save another translation to existent term', 'alt' => 'Save another translation to existent term', 'class' => 'click', 'data-action' => 'update-term-translation', 'data-wid' => (string)$wid, 'data-target' => '#tx' . $i]);
        } else {
            $r .=
            IconHelper::render('circle-plus', ['title' => 'Save translation to new term', 'alt' => 'Save translation to new term', 'class' => 'click', 'data-action' => 'add-term-translation', 'data-target' => '#tx' . $i, 'data-word' => htmlspecialchars($word, ENT_QUOTES, 'UTF-8'), 'data-lang' => (string)$lang]);
        }
        $r .= '&nbsp;&nbsp;
        <span id="wait' . $i . '">
            ' . IconHelper::render('empty', []) . '
        </span>
        </span>';
        return $r;
    }

    /**
     * Find the possible translations for a term.
     *
     * @param int $wordId Term ID
     *
     * @return string[] Return the possible translations.
     */
    public function getTranslations(int $wordId): array
    {
        $translations = array();
        $alltrans = (string) QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoTranslation');
        $transarr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $alltrans);
        foreach ($transarr as $t) {
            $tt = trim($t);
            if ($tt == '*' || $tt == '') {
                continue;
            }
            $translations[] = $tt;
        }
        return $translations;
    }

    /**
     * Gather useful data to edit a term annotation on a specific text.
     *
     * @param string $wordlc Term in lower case
     * @param int    $textid Text ID
     *
     * @return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int, term_ord?: int, translations?: string[], language_id?: int, error?: string}
     */
    public function getTermTranslations(string $wordlc, int $textid): array
    {
        $record = QueryBuilder::table('texts')
            ->select(['TxLgID', 'TxAnnotatedText'])
            ->where('TxID', '=', $textid)
            ->firstPrepared();
        if ($record === null) {
            return ['error' => 'Text not found'];
        }
        $langid = (int)$record['TxLgID'];
        $ann = (string)$record['TxAnnotatedText'];
        if (strlen($ann) > 0) {
            $annotationService = new AnnotationService();
            $ann = $annotationService->recreateSaveAnnotation($textid, $ann);
        }

        $annotations = preg_split('/[\n]/u', $ann);
        $i = -1;
        foreach (array_values($annotations) as $index => $annotationLine) {
            $vals = preg_split('/[\t]/u', $annotationLine);
            if ($vals === false) {
                continue;
            }
            if ($vals[0] <= -1) {
                continue;
            }
            if (trim($wordlc) != mb_strtolower(trim($vals[1]), 'UTF-8')) {
                continue;
            }
            $i = $index;
            break;
        }

        $annData = array();
        if ($i == -1) {
            $annData["error"] = "Annotation not found";
            return $annData;
        }

        $annotationLine = $annotations[$i];
        $vals = preg_split('/[\t]/u', $annotationLine);
        if ($vals === false) {
            $annData["error"] = "Annotation line is ill-formatted";
            return $annData;
        }
        $annData["term_lc"] = trim($wordlc);
        $annData["wid"] = null;
        $annData["trans"] = '';
        $annData["ann_index"] = $i;
        $annData["term_ord"] = (int)$vals[0];

        $wid = null;
        if (count($vals) > 2 && ctype_digit($vals[2])) {
            $wid = (int)$vals[2];
            $tempWid = QueryBuilder::table('words')
                ->where('WoID', '=', $wid)
                ->countPrepared();
            if ($tempWid < 1) {
                $wid = null;
            }
        }
        if ($wid !== null) {
            $annData["wid"] = $wid;
            $annData["translations"] = $this->getTranslations($wid);
        }
        if (count($vals) > 3) {
            $annData["trans"] = $vals[3];
        }
        $annData["language_id"] = $langid;
        return $annData;
    }

    /**
     * Full form for terms edition in a given text.
     *
     * @param int $textid Text ID.
     *
     * @return string HTML table for all terms
     */
    public function editTermForm(int $textid): string
    {
        $record = QueryBuilder::table('texts')
            ->select(['TxLgID', 'TxAnnotatedText'])
            ->where('TxID', '=', $textid)
            ->firstPrepared();
        if ($record === null) {
            return '<p>Text not found</p>';
        }
        $langid = (int) $record['TxLgID'];
        $ann = (string) $record['TxAnnotatedText'];
        if (strlen($ann) > 0) {
            $annotationService = new AnnotationService();
            $ann = $annotationService->recreateSaveAnnotation($textid, $ann);
        }

        $langRecord = QueryBuilder::table('languages')
            ->select(['LgTextSize', 'LgRightToLeft'])
            ->where('LgID', '=', $langid)
            ->firstPrepared();
        $textsize = $langRecord !== null ? (int)$langRecord['LgTextSize'] : 100;
        if ($textsize > 100) {
            $textsize = intval($textsize * 0.8);
        }
        $rtlScript = $langRecord !== null && !empty($langRecord['LgRightToLeft']);

        $dictionaryAdapter = new DictionaryAdapter();

        $r =
        '<form action="" method="post">
            <table class="table is-bordered is-fullwidth">
                <tr>
                    <th class="has-text-centered">Text</th>
                    <th class="has-text-centered">Dict.</th>
                    <th class="has-text-centered">Edit<br />Term</th>
                    <th class="has-text-centered">
                        Term Translations (Delim.: ' .
                        htmlspecialchars(Settings::getWithDefault('set-term-translation-delimiters'), ENT_QUOTES, 'UTF-8') . ')
                        <br />
                        <input type="button" value="Reload" data-action="reload-impr-text" />
                    </th>
                </tr>';
        $items = preg_split('/[\n]/u', $ann);
        $nontermbuffer = '';
        foreach (array_values($items) as $i => $item) {
            $vals = preg_split('/[\t]/u', $item);
            if ((int)$vals[0] > -1) {
                if ($nontermbuffer != '') {
                    $r .= '<tr>
                        <td class="has-text-centered" style="font-size:' . $textsize . '%;">' .
                            $nontermbuffer .
                        '</td>
                        <td class="has-text-right" colspan="3">
                        ' . IconHelper::render('check', ['title' => "Back to 'Display/Print Mode'", 'alt' => "Back to 'Display/Print Mode'", 'class' => 'click', 'data-action' => 'back-to-print-mode', 'data-textid' => (string)$textid]) . '
                        </td>
                    </tr>';
                    $nontermbuffer = '';
                }
                $wid = null;
                $trans = '';
                if (count($vals) > 2) {
                    $strWid = $vals[2];
                    if (is_numeric($strWid)) {
                        $tempWid = QueryBuilder::table('words')
                            ->where('WoID', '=', $strWid)
                            ->countPrepared();
                        if ($tempWid < 1) {
                            $wid = null;
                        } else {
                            $wid = (int) $strWid;
                        }
                    } else {
                        $wid = null;
                    }
                }
                if (count($vals) > 3) {
                    $trans = $vals[3];
                }
                $wordLink = "&nbsp;";
                if ($wid !== null) {
                    $wordLink = '<a name="rec' . $i . '"></a>
                    <span class="click"
                    data-action="edit-term-popup" data-wid="' . $wid . '" data-textid="' . $textid . '" data-ord="' . (int)$vals[0] . '">
                        ' . IconHelper::render('file-pen-line', ['title' => 'Edit Term', 'alt' => 'Edit Term']) . '
                    </span>';
                }
                $termText = $vals[1] ?? '';
                $r .= '<tr>
                    <td class="has-text-centered" style="font-size:' . $textsize . '%;"' .
                    ($rtlScript ? ' dir="rtl"' : '') . '>
                        <span id="term' . $i . '">' . htmlspecialchars($termText, ENT_QUOTES, 'UTF-8') .
                        '</span>
                    </td>
                    <td class="has-text-centered" nowrap="nowrap">' .
                        $dictionaryAdapter->makeDictLinks($langid, $termText) .
                    '</td>
                    <td class="has-text-centered">
                        <span id="editlink' . $i . '">' . $wordLink . '</span>
                    </td>
                    <td class="" style="font-size:90%;">
                        <span id="transsel' . $i . '">' .
                            $this->makeTrans($i, $wid, $trans, $termText, $langid) . '
                        </span>
                    </td>
                </tr>';
            } else {
                $nontermbuffer .= str_replace(
                    "¶",
                    '' . IconHelper::render('wrap-text', ['title' => 'New Line', 'alt' => 'New Line']) . '',
                    htmlspecialchars(trim($vals[1] ?? ''), ENT_QUOTES, 'UTF-8')
                );
            }
        }
        if ($nontermbuffer != '') {
            $r .= '<tr>
                <td class="has-text-centered" style="font-size:' . $textsize . '%;">' .
                $nontermbuffer .
                '</td>
                <td class="has-text-right" colspan="3">
                    ' . IconHelper::render('check', ['title' => "Back to 'Display/Print Mode'", 'alt' => "Back to 'Display/Print Mode'", 'class' => 'click', 'data-action' => 'back-to-print-mode', 'data-textid' => (string)$textid]) . '
                </td>
            </tr>';
        }
        $r .= '
                    <th class="has-text-centered">Text</th>
                    <th class="has-text-centered">Dict.</th>
                    <th class="has-text-centered">Edit<br />Term</th>
                    <th class="has-text-centered">
                        Term Translations (Delim.: ' .
                        htmlspecialchars(Settings::getWithDefault('set-term-translation-delimiters'), ENT_QUOTES, 'UTF-8') . ')
                        <br />
                        <input type="button" value="Reload" data-action="reload-impr-text" />
                        <a name="bottom"></a>
                    </th>
                </tr>
            </table>
        </form>';
        return $r;
    }

    /**
     * Format response for getting term translations.
     *
     * @param string $termLc Term in lowercase
     * @param int    $textId Text ID
     *
     * @return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int, term_ord?: int, translations?: string[], language_id?: int, error?: string}
     */
    public function formatTermTranslations(string $termLc, int $textId): array
    {
        return $this->getTermTranslations($termLc, $textId);
    }

    /**
     * Format response for edit term form HTML.
     *
     * @param int $textId Text ID
     *
     * @return array{html: string}
     */
    public function formatEditTermForm(int $textId): array
    {
        return ['html' => $this->editTermForm($textId)];
    }

    // =========================================================================
    // Text Scoring API (comprehensibility/difficulty analysis)
    // =========================================================================

    /**
     * Get the difficulty score for a single text.
     *
     * @param int $textId Text ID to score
     *
     * @return array<string, mixed>
     */
    public function formatGetTextScore(int $textId): array
    {
        $scoringService = new TextScoringService();
        $score = $scoringService->scoreText($textId);
        return $score->toArray();
    }

    /**
     * Get scores for multiple texts.
     *
     * @param int[] $textIds Array of text IDs
     *
     * @return array{scores: array<int, array<string, mixed>>}
     */
    public function formatGetTextScores(array $textIds): array
    {
        $scoringService = new TextScoringService();
        $scores = $scoringService->scoreTexts($textIds);

        $result = [];
        foreach ($scores as $textId => $score) {
            $result[$textId] = $score->toArray();
        }

        return ['scores' => $result];
    }

    /**
     * Get recommended texts for a language based on comprehensibility.
     *
     * @param int   $languageId Language ID
     * @param array $params     Query parameters (target, limit)
     *
     * @return array{recommendations: array<array<string, mixed>>, target_comprehensibility: float}
     */
    public function formatGetRecommendedTexts(int $languageId, array $params): array
    {
        $target = isset($params['target']) ? (float)$params['target'] : 0.95;
        $limit = isset($params['limit']) ? min(50, max(1, (int)$params['limit'])) : 10;

        // Clamp target between 0.5 and 1.0
        $target = max(0.5, min(1.0, $target));

        $scoringService = new TextScoringService();
        $recommendations = $scoringService->getRecommendedTexts($languageId, $target, $limit);

        // Convert to arrays and add text titles
        $result = [];
        foreach ($recommendations as $score) {
            $scoreArray = $score->toArray();

            // Fetch text title
            $text = QueryBuilder::table('texts')
                ->select(['TxTitle'])
                ->where('TxID', '=', $score->textId)
                ->firstPrepared();

            if ($text !== null) {
                $scoreArray['title'] = (string)$text['TxTitle'];
            }

            $result[] = $scoreArray;
        }

        return [
            'recommendations' => $result,
            'target_comprehensibility' => $target
        ];
    }
}
