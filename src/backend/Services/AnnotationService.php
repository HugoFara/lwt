<?php declare(strict_types=1);
/**
 * Annotation Service - Annotation management functions.
 *
 * This service contains functions for creating, saving, and managing
 * text annotations for the print/improved view.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/annotation_management.php
 */

namespace Lwt\Services {

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Core\Utils\ErrorHandler;
use Lwt\Database\Connection;
use Lwt\View\Helper\IconHelper;

/**
 * Service class for annotation management.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class AnnotationService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Uses provided annotations, and annotations from database to update annotations.
     *
     * @param int    $textId Id of the text on which to update annotations
     * @param string $oldAnn Old annotations
     *
     * @return string Updated annotations for this text.
     */
    public function recreateSaveAnnotation(int $textId, string $oldAnn): string
    {
        // Get the translations from $oldAnn:
        $oldtrans = array();
        $olditems = preg_split('/[\n]/u', $oldAnn);
        foreach ($olditems as $olditem) {
            $oldvals = preg_split('/[\t]/u', $olditem);
            if (count($oldvals) >= 2 && (int)$oldvals[0] > -1) {
                $trans = '';
                if (count($oldvals) > 3) {
                    $trans = $oldvals[3];
                }
                $oldtrans[$oldvals[0] . "\t" . $oldvals[1]] = $trans;
            }
        }

        // Reset the translations from $oldAnn in $newann and rebuild in $ann:
        $newann = $this->createAnnotation($textId);
        $newitems = preg_split('/[\n]/u', $newann);
        $ann = '';
        foreach ($newitems as $newitem) {
            $newvals = preg_split('/[\t]/u', $newitem);
            if ((int)$newvals[0] > -1) {
                $key = $newvals[0] . "\t";
                if (isset($newvals[1])) {
                    $key .= $newvals[1];
                }
                if (isset($oldtrans[$key])) {
                    $newvals[3] = $oldtrans[$key];
                }
                $item = implode("\t", $newvals);
            } else {
                $item = $newitem;
            }
            $ann .= $item . "\n";
        }

        Connection::preparedExecute(
            "UPDATE {$this->tbpref}texts
            SET TxAnnotatedText = ?
            WHERE TxID = ?",
            [$ann, $textId]
        );

        return (string)Connection::preparedFetchValue(
            "SELECT TxAnnotatedText AS value
            FROM {$this->tbpref}texts
            WHERE TxID = ?",
            [$textId]
        );
    }

    /**
     * Create new annotations for a text.
     *
     * @param int $textId Id of the text to create annotations for
     *
     * @return string Annotations for the text
     *
     * @since 2.9.0 Annotations "position" change, they are now equal to Ti2Order
     *              it was shifted by one index before.
     */
    public function createAnnotation(int $textId): string
    {
        $ann = '';
        $sql = "SELECT
            CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END AS Code,
            CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END AS TiText,
            Ti2Order,
            CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END AS TiIsNotWord,
            WoID, WoTranslation
            FROM (
                {$this->tbpref}textitems2
                LEFT JOIN {$this->tbpref}words
                ON Ti2WoID = WoID AND Ti2LgID = WoLgID
            )
            WHERE Ti2TxID = ?
            ORDER BY Ti2Order ASC, Ti2WordCount DESC";

        $until = 0;
        $results = Connection::preparedFetchAll($sql, [$textId]);
        // For each term (includes blanks)
        foreach ($results as $record) {
            $actcode = (int)$record['Code'];
            $order = (int)$record['Ti2Order'];
            if ($order <= $until) {
                continue;
            }
            $savenonterm = '';
            $saveterm = '';
            $savetrans = '';
            $savewordid = '';
            $until = $order;
            if ($record['TiIsNotWord'] != 0) {
                $savenonterm = $record['TiText'];
            } else {
                $until = $order + 2 * ($actcode - 1);
                $saveterm = $record['TiText'];
                if (isset($record['WoID'])) {
                    $savetrans = $record['WoTranslation'];
                    $savewordid = $record['WoID'];
                }
            }
            // Append the annotation
            $ann .= $this->processTerm(
                $savenonterm,
                $saveterm,
                $savetrans,
                $savewordid,
                $order
            );
        }
        return $ann;
    }

    /**
     * Create and save annotations for a text.
     *
     * @param int $textId Text ID
     *
     * @return string Annotations for the text
     */
    public function createSaveAnnotation(int $textId): string
    {
        $ann = $this->createAnnotation($textId);
        Connection::preparedExecute(
            "UPDATE {$this->tbpref}texts
            SET TxAnnotatedText = ?
            WHERE TxID = ?",
            [$ann, $textId]
        );
        return (string)Connection::preparedFetchValue(
            "SELECT TxAnnotatedText AS value
            FROM {$this->tbpref}texts
            WHERE TxID = ?",
            [$textId]
        );
    }

    /**
     * Process a term for annotation output.
     *
     * @param string $nonterm Non-term text (punctuation, spaces)
     * @param string $term    Term text
     * @param string $trans   Translation
     * @param string $wordid  Word ID
     * @param int    $line    Line/order number
     *
     * @return string Formatted annotation line
     */
    public function processTerm(string $nonterm, string $term, string $trans, string $wordid, int $line): string
    {
        $r = '';
        if ($nonterm != '') {
            $r = "-1\t$nonterm\n";
        }
        if ($term != '') {
            $r .= "$line\t$term\t" . trim($wordid) . "\t" .
            $this->getFirstTranslation($trans) . "\n";
        }
        return $r;
    }

    /**
     * Get the first translation from a translation string.
     *
     * @param string $trans Full translation string (may contain separators)
     *
     * @return string First translation only
     */
    public function getFirstTranslation(string $trans): string
    {
        $arr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $trans);
        if (count($arr) < 1) {
            return '';
        }
        $r = trim($arr[0]);
        if ($r == '*') {
            $r = "";
        }
        return $r;
    }

    /**
     * Get a link to the annotated text if it exists.
     *
     * @param int $textId Text ID
     *
     * @return string HTML link or empty string
     */
    public function getAnnotationLink(int $textId): string
    {
        if (Connection::preparedFetchValue(
            "SELECT LENGTH(TxAnnotatedText) AS value
            FROM {$this->tbpref}texts
            WHERE TxID = ?",
            [$textId]
        ) > 0) {
            return ' &nbsp;<a href="print_impr_text.php?text=' . $textId .
            '" target="_top">' . IconHelper::render('check', ['title' => 'Annotated Text', 'alt' => 'Annotated Text']) . '</a>';
        } else {
            return '';
        }
    }

    /**
     * Convert annotations in a JSON format.
     *
     * @param string $ann Annotations.
     *
     * @return string|false A JSON-encoded version of the annotations
     */
    public function annotationToJson(string $ann): string|false
    {
        if ($ann == '') {
            return "{}";
        }
        $arr = array();
        $items = preg_split('/[\n]/u', $ann);
        foreach ($items as $item) {
            $vals = preg_split('/[\t]/u', $item);
            if (count($vals) > 3 && $vals[0] >= 0 && $vals[2] > 0) {
                $arr[intval($vals[0]) - 1] = array($vals[1], $vals[2], $vals[3]);
            }
        }
        $json_data = json_encode($arr);
        if ($json_data === false) {
            ErrorHandler::die("Unable to format to JSON");
        }
        return $json_data;
    }
}

} // End namespace Lwt\Services

namespace {

// =============================================================================
// GLOBAL FUNCTION WRAPPERS (for backward compatibility)
// =============================================================================

use Lwt\Services\AnnotationService;

/**
 * Uses provided annotations, and annotations from database to update annotations.
 *
 * @param int    $textid Id of the text on which to update annotations
 * @param string $oldann Old annotations
 *
 * @return string Updated annotations for this text.
 *
 * @see AnnotationService::recreateSaveAnnotation()
 */
function recreate_save_ann(int $textid, string $oldann): string
{
    $service = new AnnotationService();
    return $service->recreateSaveAnnotation($textid, $oldann);
}

/**
 * Create new annotations for a text.
 *
 * @param int $textid Id of the text to create annotations for
 *
 * @return string Annotations for the text
 *
 * @see AnnotationService::createAnnotation()
 */
function create_ann(int $textid): string
{
    $service = new AnnotationService();
    return $service->createAnnotation($textid);
}

/**
 * Create and save annotations for a text.
 *
 * @param int $textid Text ID
 *
 * @return string Annotations for the text
 *
 * @see AnnotationService::createSaveAnnotation()
 */
function create_save_ann(int $textid): string
{
    $service = new AnnotationService();
    return $service->createSaveAnnotation($textid);
}

/**
 * Process a term for annotation output.
 *
 * @param string $nonterm Non-term text (punctuation, spaces)
 * @param string $term    Term text
 * @param string $trans   Translation
 * @param string $wordid  Word ID
 * @param int    $line    Line/order number
 *
 * @return string Formatted annotation line
 *
 * @see AnnotationService::processTerm()
 */
function process_term(string $nonterm, string $term, string $trans, string $wordid, int $line): string
{
    $service = new AnnotationService();
    return $service->processTerm($nonterm, $term, $trans, $wordid, $line);
}

/**
 * Get the first translation from a translation string.
 *
 * @param string $trans Full translation string (may contain separators)
 *
 * @return string First translation only
 *
 * @see AnnotationService::getFirstTranslation()
 */
function get_first_translation(string $trans): string
{
    $service = new AnnotationService();
    return $service->getFirstTranslation($trans);
}

/**
 * Get a link to the annotated text if it exists.
 *
 * @param int $textid Text ID
 *
 * @return string HTML link or empty string
 *
 * @see AnnotationService::getAnnotationLink()
 */
function get_annotation_link(int $textid): string
{
    $service = new AnnotationService();
    return $service->getAnnotationLink($textid);
}

/**
 * Like trim, but in place (modify variable)
 *
 * @param string $value Value to be trimmed
 */
function trim_value(&$value): void
{
    $value = trim($value);
}

/**
 * Convert annotations in a JSON format.
 *
 * @param string $ann Annotations.
 *
 * @return string|false A JSON-encoded version of the annotations
 *
 * @see AnnotationService::annotationToJson()
 */
function annotation_to_json(string $ann): string|false
{
    $service = new AnnotationService();
    return $service->annotationToJson($ann);
}

} // End global namespace
