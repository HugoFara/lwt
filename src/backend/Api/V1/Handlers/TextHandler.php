<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Services\WordService;
use Lwt\Services\ExportService;
use Lwt\Services\TagService;
use Lwt\Services\DictionaryService;
use Lwt\Services\TextService;
use Lwt\Services\TextPrintService;

require_once __DIR__ . '/../../../Services/WordService.php';
require_once __DIR__ . '/../../../Services/ExportService.php';
require_once __DIR__ . '/../../../Services/TagService.php';
require_once __DIR__ . '/../../../Services/DictionaryService.php';
require_once __DIR__ . '/../../../Services/TextService.php';
require_once __DIR__ . '/../../../Services/TextPrintService.php';

/**
 * Handler for text-related API operations.
 *
 * Extracted from api_v1.php lines 262-373.
 */
class TextHandler
{
    private WordService $wordService;
    private TextService $textService;

    public function __construct()
    {
        $this->wordService = new WordService();
        $this->textService = new TextService();
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
        $newAnnotation = $data->{$elem};
        $line = (int)substr($elem, 2);
        if (str_starts_with($elem, "rg") && $newAnnotation == "") {
            $newAnnotation = $data->{'tx' . $line};
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
     * @return array{save_impr_text?: string, error?: string}
     */
    public function formatSetAnnotation(int $textId, string $elem, string $data): array
    {
        $result = $this->saveImprText($textId, $elem, json_decode($data));
        if (array_key_exists("error", $result)) {
            return ["error" => $result["error"]];
        }
        return ["save_impr_text" => $result["success"]];
    }

    // =========================================================================
    // New Phase 2 Methods
    // =========================================================================

    /**
     * Set display mode settings for a text.
     *
     * @param int        $textId      Text ID
     * @param int|null   $annotations Annotation mode (0=none, 1=translations, 2=romanization, 3=both)
     * @param bool|null  $romanization Whether to show romanization
     * @param bool|null  $translation  Whether to show translation
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
        list($count, $wordsData) = $this->wordService->markAllWordsWithStatus($textId, 99);
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
        list($count, $wordsData) = $this->wordService->markAllWordsWithStatus($textId, 98);
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

        if (!$textInfo) {
            return ['error' => 'Text not found'];
        }

        $langId = (int)$textInfo['TxLgID'];

        // Get language info including dictionary URLs
        $langInfo = QueryBuilder::table('languages')
            ->select(['LgID', 'LgName', 'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
                    'LgTextSize', 'LgRightToLeft', 'LgRegexpWordCharacters', 'LgRemoveSpaces'])
            ->where('LgID', '=', $langId)
            ->firstPrepared();

        if (!$langInfo) {
            return ['error' => 'Language not found'];
        }

        // Get all text items with word info
        $records = QueryBuilder::table('textitems2')
            ->select([
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
                'WoRomanization'
            ])
            ->leftJoin('words', 'textitems2.Ti2WoID', '=', 'words.WoID')
            ->where('textitems2.Ti2TxID', '=', $textId)
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
                        'remaining' => $code
                    ];
                }
            }

            // Determine if hidden (for multiword display logic)
            $hidden = $order <= $lastOrder;

            // Calculate hex for TERM class
            $hex = StringUtils::toClassName($record['TiTextLC'] ?? '');

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
                    $wordData['translation'] = ExportService::replaceTabNewline($record['WoTranslation'] ?? '');
                    $wordData['romanization'] = $record['WoRomanization'] ?? '';

                    // Get tags
                    $tags = TagService::getWordTagList((int)$record['WoID'], false);
                    if ($tags) {
                        $wordData['tags'] = $tags;
                    }
                } else {
                    // Unknown word (status 0)
                    $wordData['wordId'] = null;
                    $wordData['status'] = 0;
                    $wordData['translation'] = '';
                    $wordData['romanization'] = '';
                }

                // Add multiword references
                foreach ($exprs as $expr) {
                    $wordData['mw' . $expr['code']] = $expr['text'];
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
}
