<?php declare(strict_types=1);
/**
 * Text Service - Business logic for text management
 *
 * Handles both active texts (texts table) and archived texts (archivedtexts table).
 *
 * PHP version 8.1
 *
 * @category   Lwt
 * @package    Lwt\Services
 * @author     HugoFara <hugo.farajallah@protonmail.com>
 * @license    Unlicense <http://unlicense.org/>
 * @link       https://hugofara.github.io/lwt/docs/php/
 * @since      3.0.0
 * @deprecated 3.0.0 Use TextFacade directly for new code.
 */

namespace Lwt\Services;

use Lwt\Core\Http\UrlUtilities;
use Lwt\Core\Repository\TextRepository;
use Lwt\Core\StringUtils;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Maintenance;
use Lwt\Database\Settings;
use Lwt\Database\TextParsing;
use Lwt\Database\UserScopedQuery;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Services\TagService;
use Lwt\Services\ExportService;
use Lwt\Services\SentenceService;

require_once __DIR__ . '/../Core/Repository/RepositoryInterface.php';
require_once __DIR__ . '/../Core/Repository/AbstractRepository.php';
require_once __DIR__ . '/../Core/Repository/TextRepository.php';

/**
 * Service class for managing texts (active and archived).
 *
 * This class extends TextFacade and provides backward compatibility
 * with existing code. For new code, prefer using TextFacade directly.
 *
 * @category   Lwt
 * @package    Lwt\Services
 * @author     HugoFara <hugo.farajallah@protonmail.com>
 * @license    Unlicense <http://unlicense.org/>
 * @link       https://hugofara.github.io/lwt/docs/php/
 * @since      3.0.0
 * @deprecated 3.0.0 Use TextFacade directly for new code.
 */
class TextService extends TextFacade
{
    private SentenceService $sentenceService;

    /**
     * Constructor - initialize dependencies.
     *
     * @param SentenceService|null $sentenceService Sentence service (optional)
     * @param TextRepository|null  $repository      Text repository (optional)
     */
    public function __construct(
        ?SentenceService $sentenceService = null,
        ?TextRepository $repository = null
    ) {
        $this->sentenceService = $sentenceService ?? new SentenceService();

        // Initialize parent TextFacade
        parent::__construct($repository);
    }

    // ===========================
    // METHODS WITH DIFFERENT SIGNATURES OR IMPLEMENTATIONS
    // ===========================

    /**
     * Get paginated texts for a specific language (with sort).
     */
    public function getTextsForLanguage(
        int $langId,
        int $page = 1,
        int $perPage = 20,
        int $sort = 1
    ): array {
        $sorts = ['TxTitle', 'TxID DESC', 'TxID ASC'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;

        $bindings1 = [$langId];
        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM texts WHERE TxLgID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1,
            'cnt'
        );
        $totalPages = (int) ceil($total / $perPage);

        $bindings2 = [$langId, $offset, $perPage];
        $records = Connection::preparedFetchAll(
            "SELECT TxID, TxTitle, TxAudioURI, TxSourceURI,
            LENGTH(TxAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (texts LEFT JOIN texttags ON TxID = TtTxID)
                LEFT JOIN tags2 ON T2ID = TtT2ID
            )
            WHERE TxLgID = ?
            GROUP BY TxID
            ORDER BY {$sortColumn}
            LIMIT ?, ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings2)
            . UserScopedQuery::forTablePrepared('tags2', $bindings2),
            $bindings2
        );

        $texts = [];
        foreach ($records as $record) {
            $texts[] = [
                'id' => (int) $record['TxID'],
                'title' => (string) $record['TxTitle'],
                'has_audio' => !empty($record['TxAudioURI']),
                'source_uri' => (string) ($record['TxSourceURI'] ?? ''),
                'has_source' => !empty($record['TxSourceURI']) && substr($record['TxSourceURI'], 0, 1) !== '#',
                'annotated' => !empty($record['annotlen']),
                'taglist' => (string) $record['taglist']
            ];
        }

        return [
            'texts' => $texts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Get paginated archived texts for a specific language.
     */
    public function getArchivedTextsForLanguage(
        int $langId,
        int $page,
        int $perPage,
        int $sort
    ): array {
        $sorts = ['AtTitle', 'AtID DESC', 'AtID ASC'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;

        $bindings1 = [$langId];
        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM archivedtexts WHERE AtLgID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
            $bindings1,
            'cnt'
        );
        $totalPages = (int) ceil($total / $perPage);

        $bindings2 = [$langId, $offset, $perPage];
        $records = Connection::preparedFetchAll(
            "SELECT AtID, AtTitle, AtAudioURI, AtSourceURI,
            LENGTH(AtAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (archivedtexts LEFT JOIN archtexttags ON AtID = AgAtID)
                LEFT JOIN tags2 ON T2ID = AgT2ID
            )
            WHERE AtLgID = ?
            GROUP BY AtID
            ORDER BY {$sortColumn}
            LIMIT ?, ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings2)
            . UserScopedQuery::forTablePrepared('tags2', $bindings2),
            $bindings2
        );

        $texts = [];
        foreach ($records as $record) {
            $texts[] = [
                'id' => (int) $record['AtID'],
                'title' => (string) $record['AtTitle'],
                'has_audio' => !empty($record['AtAudioURI']),
                'source_uri' => (string) ($record['AtSourceURI'] ?? ''),
                'has_source' => !empty($record['AtSourceURI']),
                'annotated' => !empty($record['annotlen']),
                'taglist' => (string) $record['taglist']
            ];
        }

        return [
            'texts' => $texts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Check text for parsing without saving (outputs HTML).
     */
    public function checkText(string $text, int $lgId): void
    {
        if (strlen(Escaping::prepareTextdata($text)) > 65000) {
            echo "<p>Error: Text too long, must be below 65000 Bytes.</p>";
        } else {
            TextParsing::parseAndDisplayPreview($text, $lgId);
        }
    }

    /**
     * Prepare text data for long text import.
     */
    public function prepareLongTextData(
        array $files,
        string $uploadText,
        int $paragraphHandling
    ): string {
        if (
            isset($files["thefile"])
            && $files["thefile"]["tmp_name"] != ""
            && $files["thefile"]["error"] == 0
        ) {
            $data = file_get_contents($files["thefile"]["tmp_name"]);
            $data = str_replace("\r\n", "\n", $data);
        } else {
            $data = Escaping::prepareTextdata($uploadText);
        }
        $data = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $data);
        $data = trim($data);

        if ($paragraphHandling == 2) {
            $data = preg_replace('/\n\s*?\n/u', '¶', $data);
            $data = str_replace("\n", ' ', $data);
        } else {
            $data = str_replace("\n", '¶', $data);
        }
        $data = preg_replace('/\s{2,}/u', ' ', $data);
        $data = str_replace('¶ ', '¶', $data);
        $data = str_replace('¶', "\n", $data);

        return $data;
    }

    /**
     * Split long text into smaller texts.
     */
    public function splitLongText(string $data, int $langId, int $maxSent): array
    {
        $sentArray = TextParsing::splitIntoSentences($data, $langId);
        $texts = [];
        $textIndex = 0;
        $texts[$textIndex] = [];
        $cnt = 0;
        $bytes = 0;

        foreach ($sentArray as $item) {
            $itemLen = strlen($item) + 1;
            if ($item != '¶') {
                $cnt++;
            }
            if ($cnt <= $maxSent && $bytes + $itemLen < 65000) {
                $texts[$textIndex][] = $item;
                $bytes += $itemLen;
            } else {
                $textIndex++;
                $texts[$textIndex] = [$item];
                $cnt = 1;
                $bytes = $itemLen;
            }
        }

        return $texts;
    }

    /**
     * Save long text import (multiple texts).
     */
    public function saveLongTextImport(
        int $langId,
        string $title,
        string $sourceUri,
        array $texts,
        int $textCount,
        ?array $textTags = null
    ): array {
        if (count($texts) != $textCount) {
            return [
                'success' => false,
                'message' => "Error: Number of texts wrong: " . count($texts) . " != " . $textCount,
                'imported' => 0
            ];
        }

        $imported = 0;
        for ($i = 0; $i < $textCount; $i++) {
            $texts[$i] = str_replace("\xC2\xAD", "", $texts[$i]);
            $counter = StringUtils::makeCounterWithTotal($textCount, $i + 1);
            $thisTitle = $title . ($counter == '' ? '' : (' (' . $counter . ')'));

            $bindings = [$langId, $thisTitle, $texts[$i], $sourceUri];
            $affected = Connection::preparedExecute(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertColumn('texts')
                . ") VALUES (?, ?, ?, '', '', ?"
                    . UserScopedQuery::insertValuePrepared('texts', $bindings)
                . ")",
                $bindings
            );
            $imported += $affected;
            $id = Connection::lastInsertId();
            TagService::saveTextTags($id, $textTags);
            TextParsing::parseAndSave($texts[$i], $langId, $id);
        }

        return [
            'success' => true,
            'message' => $imported . " Text(s) imported!",
            'imported' => $imported
        ];
    }

    /**
     * Get text data for text content display.
     */
    public function getTextDataForContent(int $textId): ?array
    {
        $bindings = [$textId];
        return Connection::preparedFetchOne(
            "SELECT TxLgID, TxTitle, TxAnnotatedText, TxPosition
                FROM texts
                WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );
    }

    /**
     * Set term sentences from texts (with SentenceService).
     */
    public function setTermSentences(array $textIds, bool $activeOnly = false): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $ids = array_map('intval', $textIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $count = 0;

        $statusFilter = $activeOnly
            ? " AND WoStatus != 98 AND WoStatus != 99"
            : "";

        $sql = "SELECT WoID, WoTextLC, MIN(Ti2SeID) AS SeID
            FROM words, textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ({$placeholders})
            {$statusFilter}
            AND IFNULL(WoSentence,'') NOT LIKE CONCAT('%{',WoText,'}%')
            GROUP BY WoID
            ORDER BY WoID, MIN(Ti2SeID)"
            . UserScopedQuery::forTablePrepared('words', $ids)
            . UserScopedQuery::forTablePrepared('textitems2', $ids, '', 'texts');

        $records = Connection::preparedFetchAll($sql, $ids);
        $sentenceCount = (int) Settings::getWithDefault('set-term-sentence-count');

        foreach ($records as $record) {
            $sent = $this->sentenceService->formatSentence(
                $record['SeID'],
                $record['WoTextLC'],
                $sentenceCount
            );
            $bindings = [ExportService::replaceTabNewline($sent[1]), $record['WoID']];
            $count += Connection::preparedExecute(
                "UPDATE words SET WoSentence = ? WHERE WoID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings),
                $bindings
            );
        }

        return "Term Sentences set from Text(s): {$count}";
    }

    /**
     * Get language translation URIs for form language selection.
     */
    public function getLanguageDataForForm(): array
    {
        $sql = "SELECT LgID, LgGoogleTranslateURI FROM languages
                WHERE LgGoogleTranslateURI <> ''"
                . UserScopedQuery::forTable('languages');
        $res = Connection::query($sql);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $result[(int) $record['LgID']] = UrlUtilities::langFromDict((string) $record['LgGoogleTranslateURI']);
        }
        mysqli_free_result($res);
        return $result;
    }

    /**
     * Save text and reparse it (with additional return data).
     */
    public function saveTextAndReparse(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): array {
        $cleanText = str_replace("\xC2\xAD", "", $text);
        $audioValue = $audioUri === '' ? null : $audioUri;

        if ($textId === 0) {
            $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri];
            $textId = (int) Connection::preparedInsert(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertColumn('texts')
                . ") VALUES (?, ?, ?, '', ?, ?"
                    . UserScopedQuery::insertValuePrepared('texts', $bindings1)
                . ")",
                $bindings1
            );
        } else {
            $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri, $textId];
            Connection::preparedExecute(
                "UPDATE texts SET
                    TxLgID = ?, TxTitle = ?, TxText = ?, TxAudioURI = ?, TxSourceURI = ?
                 WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings1),
                $bindings1
            );
        }

        TagService::saveTextTags($textId);

        $sentencesDeleted = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();
        $textitemsDeleted = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        $bindings2 = [$textId];
        TextParsing::parseAndSave(
            Connection::preparedFetchValue(
                "SELECT TxText FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings2),
                $bindings2,
                'TxText'
            ),
            $lgId,
            $textId
        );

        $bindings3 = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings3, '', 'texts'),
            $bindings3,
            'cnt'
        );
        $bindings4 = [$textId];
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings4, '', 'texts'),
            $bindings4,
            'cnt'
        );

        $message = "Sentences deleted: {$sentencesDeleted} / Textitems deleted: {$textitemsDeleted} / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

        return [
            'message' => $message,
            'textId' => $textId,
            'redirect' => false
        ];
    }
}
