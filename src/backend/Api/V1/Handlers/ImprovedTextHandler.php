<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Services\AnnotationService;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * Handler for improved/annotated text API operations.
 *
 * Extracted from api_v1.php lines 468-823 (namespace Lwt\Ajax\Improved_Text).
 */
class ImprovedTextHandler
{
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
                    $i . '" value="' . htmlspecialchars($tt ?? '', ENT_QUOTES, 'UTF-8') . '" />
                    &nbsp;' . htmlspecialchars($tt ?? '', ENT_QUOTES, 'UTF-8') . '
                </span>
                <br />';
            }
        }
        $r .= '<span class="nowrap">
        <input class="impr-ann-radio" type="radio" name="rg' . $i . '" ' .
        ($set ? 'checked="checked" ' : '') . 'value="" />
        &nbsp;
        <input class="impr-ann-text" type="text" name="tx' . $i .
        '" id="tx' . $i . '" value="' . ($set ? htmlspecialchars($trans ?? '', ENT_QUOTES, 'UTF-8') : '') .
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
     * @return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int, term_ord?: int, translations?: string[], lang_id?: int, error?: string}
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
        $annData["lang_id"] = $langid;
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
        $rtlScript = $langRecord !== null ? $langRecord['LgRightToLeft'] : false;

        $r =
        '<form action="" method="post">
            <table class="tab2" cellspacing="0" cellpadding="5">
                <tr>
                    <th class="th1 center">Text</th>
                    <th class="th1 center">Dict.</th>
                    <th class="th1 center">Edit<br />Term</th>
                    <th class="th1 center">
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
                        <td class="td1 center" style="font-size:' . $textsize . '%;">' .
                            $nontermbuffer .
                        '</td>
                        <td class="td1 right" colspan="3">
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
                    <td class="td1 center" style="font-size:' . $textsize . '%;"' .
                    ($rtlScript ? ' dir="rtl"' : '') . '>
                        <span id="term' . $i . '">' . htmlspecialchars($termText, ENT_QUOTES, 'UTF-8') .
                        '</span>
                    </td>
                    <td class="td1 center" nowrap="nowrap">' .
                        (new DictionaryAdapter())->makeDictLinks($langid, $termText) .
                    '</td>
                    <td class="td1 center">
                        <span id="editlink' . $i . '">' . $wordLink . '</span>
                    </td>
                    <td class="td1" style="font-size:90%;">
                        <span id="transsel' . $i . '">' .
                            $this->makeTrans($i, $wid, $trans, $termText, $langid) . '
                        </span>
                    </td>
                </tr>';
            } else {
                $nontermbuffer .= str_replace(
                    "¶",
                    '' . IconHelper::render('wrap-text', ['title' => 'New Line', 'alt' => 'New Line']) . '',
                    htmlspecialchars(trim($vals[1] ?? '') ?? '', ENT_QUOTES, 'UTF-8')
                );
            }
        }
        if ($nontermbuffer != '') {
            $r .= '<tr>
                <td class="td1 center" style="font-size:' . $textsize . '%;">' .
                $nontermbuffer .
                '</td>
                <td class="td1 right" colspan="3">
                    ' . IconHelper::render('check', ['title' => "Back to 'Display/Print Mode'", 'alt' => "Back to 'Display/Print Mode'", 'class' => 'click', 'data-action' => 'back-to-print-mode', 'data-textid' => (string)$textid]) . '
                </td>
            </tr>';
        }
        $r .= '
                    <th class="th1 center">Text</th>
                    <th class="th1 center">Dict.</th>
                    <th class="th1 center">Edit<br />Term</th>
                    <th class="th1 center">
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

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for getting term translations.
     *
     * @param string $termLc Term in lowercase
     * @param int    $textId Text ID
     *
     * @return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int, term_ord?: int, translations?: string[], lang_id?: int, error?: string}
     */
    public function formatTermTranslations(string $termLc, int $textId): array
    {
        return $this->getTermTranslations($termLc, $textId);
    }
}
