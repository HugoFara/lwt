<?php declare(strict_types=1);
/**
 * Archive Text Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Text\Application\UseCases;

use Lwt\Database\Connection;
use Lwt\Database\Maintenance;
use Lwt\Database\QueryBuilder;
use Lwt\Database\TextParsing;
use Lwt\Database\UserScopedQuery;

/**
 * Use case for archiving and unarchiving texts.
 *
 * Handles moving texts between active (texts table) and archived
 * (archivedtexts table) states, including tag migration and cleanup.
 *
 * @since 3.0.0
 */
class ArchiveText
{
    /**
     * Archive an active text.
     *
     * @param int $textId Text ID
     *
     * @return string Result message
     */
    public function execute(int $textId): string
    {
        // Delete parsed data
        $count3 = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        $count2 = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();

        // Copy to archived texts
        $bindings1 = [$textId];
        $inserted = Connection::preparedExecute(
            "INSERT INTO archivedtexts (
                AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI"
                . UserScopedQuery::insertColumn('archivedtexts')
            . ") SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                . UserScopedQuery::insertValue('archivedtexts')
            . " FROM texts
            WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1
        );
        $archivedId = Connection::lastInsertId();

        // Copy tags
        $bindings2 = [$archivedId, $textId];
        Connection::preparedExecute(
            "INSERT INTO archtexttags (AgAtID, AgT2ID)
            SELECT ?, TtT2ID
            FROM texttags
            WHERE TtTxID = ?"
            . UserScopedQuery::forTablePrepared('texttags', $bindings2, '', 'texts'),
            $bindings2
        );

        // Delete from texts
        $count1 = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->delete();

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "Texts deleted: $count1, Sentences deleted: $count2, Text items deleted: $count3, Archived: $inserted";
    }

    /**
     * Archive multiple texts.
     *
     * @param array $textIds Array of text IDs
     *
     * @return string Result message
     */
    public function archiveMultiple(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $count = 0;
        $ids = array_map('intval', $textIds);

        foreach ($ids as $textId) {
            // Delete parsed data
            QueryBuilder::table('textitems2')
                ->where('Ti2TxID', '=', $textId)
                ->delete();
            QueryBuilder::table('sentences')
                ->where('SeTxID', '=', $textId)
                ->delete();

            // Copy to archived
            $bindings1 = [$textId];
            $mess = Connection::preparedExecute(
                "INSERT INTO archivedtexts (
                    AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI"
                    . UserScopedQuery::insertColumn('archivedtexts')
                . ") SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertValue('archivedtexts')
                . " FROM texts
                WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings1),
                $bindings1
            );
            $count += $mess;

            $id = Connection::lastInsertId();

            // Copy tags
            $bindings2 = [$id, $textId];
            Connection::preparedExecute(
                "INSERT INTO archtexttags (AgAtID, AgT2ID)
                SELECT ?, TtT2ID
                FROM texttags
                WHERE TtTxID = ?"
                . UserScopedQuery::forTablePrepared('texttags', $bindings2, '', 'texts'),
                $bindings2
            );

            // Delete from texts
            QueryBuilder::table('texts')
                ->where('TxID', '=', $textId)
                ->delete();
        }

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "Archived Text(s): {$count}";
    }

    /**
     * Unarchive a text (move from archived to active).
     *
     * @param int $archivedId Archived text ID
     *
     * @return array{message: string, textId: int|null} Result with message and new text ID
     */
    public function unarchive(int $archivedId): array
    {
        // Get language ID first
        $bindings = [$archivedId];
        $lgId = Connection::preparedFetchValue(
            "SELECT AtLgID FROM archivedtexts
            WHERE AtID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings),
            $bindings,
            'AtLgID'
        );

        if ($lgId === null) {
            return ['message' => 'Archived text not found', 'textId' => null];
        }

        // Insert into active texts
        $bindings1 = [$archivedId];
        $inserted = Connection::preparedExecute(
            "INSERT INTO texts (
                TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                . UserScopedQuery::insertColumn('texts')
            . ") SELECT AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI"
                . UserScopedQuery::insertValue('texts')
            . " FROM archivedtexts
            WHERE AtID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
            $bindings1
        );
        $insertedMsg = "Texts added: $inserted";

        $textId = Connection::lastInsertId();

        // Copy tags
        $bindings2 = [$textId, $archivedId];
        Connection::preparedExecute(
            "INSERT INTO texttags (TtTxID, TtT2ID)
            SELECT ?, AgT2ID
            FROM archtexttags
            WHERE AgAtID = ?"
            . UserScopedQuery::forTablePrepared('archtexttags', $bindings2, '', 'archivedtexts'),
            $bindings2
        );

        // Parse the text
        $bindings3 = [$textId];
        $textContent = Connection::preparedFetchValue(
            "SELECT TxText FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings3),
            $bindings3,
            'TxText'
        );
        TextParsing::parseAndSave($textContent, (int) $lgId, $textId);

        // Delete from archived
        $deleted = QueryBuilder::table('archivedtexts')
            ->where('AtID', '=', $archivedId)
            ->delete();
        $deleted = "Archived Texts deleted: $deleted";

        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();

        // Get statistics
        $bindings4 = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings4, '', 'texts'),
            $bindings4,
            'cnt'
        );
        $bindings5 = [$textId];
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings5, '', 'texts'),
            $bindings5,
            'cnt'
        );

        $message = "{$deleted} / {$insertedMsg} / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

        return ['message' => $message, 'textId' => (int) $textId];
    }

    /**
     * Unarchive multiple texts.
     *
     * @param array $archivedIds Array of archived text IDs
     *
     * @return string Result message
     */
    public function unarchiveMultiple(array $archivedIds): string
    {
        if (empty($archivedIds)) {
            return "Multiple Actions: 0";
        }

        $count = 0;
        $ids = array_map('intval', $archivedIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $records = Connection::preparedFetchAll(
            "SELECT AtID, AtLgID FROM archivedtexts WHERE AtID IN ({$placeholders})"
            . UserScopedQuery::forTablePrepared('archivedtexts', $ids),
            $ids
        );

        foreach ($records as $record) {
            $ida = $record['AtID'];
            $bindings1 = [$ida];
            $mess = Connection::preparedExecute(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertColumn('texts')
                . ") SELECT AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI"
                    . UserScopedQuery::insertValue('texts')
                . " FROM archivedtexts
                WHERE AtID = ?"
                . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
                $bindings1
            );
            $count += $mess;

            $id = Connection::lastInsertId();

            $bindings2 = [$id, $ida];
            Connection::preparedExecute(
                "INSERT INTO texttags (TtTxID, TtT2ID)
                SELECT ?, AgT2ID
                FROM archtexttags
                WHERE AgAtID = ?"
                . UserScopedQuery::forTablePrepared('archtexttags', $bindings2, '', 'archivedtexts'),
                $bindings2
            );

            $bindings3 = [$id];
            $textContent = Connection::preparedFetchValue(
                "SELECT TxText FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings3),
                $bindings3,
                'TxText'
            );
            TextParsing::parseAndSave($textContent, (int) $record['AtLgID'], $id);

            QueryBuilder::table('archivedtexts')
                ->where('AtID', '=', $ida)
                ->delete();
        }

        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();

        return "Unarchived Text(s): {$count}";
    }

    /**
     * Clean up orphaned text tags.
     */
    private function cleanupTextTags(): void
    {
        Connection::execute(
            "DELETE texttags
            FROM (
                texttags
                LEFT JOIN texts ON TtTxID = TxID
            )
            WHERE TxID IS NULL"
            . UserScopedQuery::forTable('texttags', '', 'texts'),
            ''
        );
    }

    /**
     * Clean up orphaned archived text tags.
     */
    private function cleanupArchivedTextTags(): void
    {
        Connection::execute(
            "DELETE archtexttags
            FROM (
                archtexttags
                LEFT JOIN archivedtexts ON AgAtID = AtID
            )
            WHERE AtID IS NULL"
            . UserScopedQuery::forTable('archtexttags', '', 'archivedtexts'),
            ''
        );
    }
}
