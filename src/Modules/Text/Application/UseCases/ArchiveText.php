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

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Maintenance;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\TextParsing;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

/**
 * Use case for archiving and unarchiving texts.
 *
 * Texts are archived by setting TxArchivedAt to the current timestamp.
 * Unarchiving sets TxArchivedAt back to NULL.
 *
 * @since 3.0.0
 */
class ArchiveText
{
    /**
     * Archive an active text.
     *
     * Sets TxArchivedAt to current timestamp and deletes parsed data.
     *
     * @param int $textId Text ID
     *
     * @return string Result message
     */
    public function execute(int $textId): string
    {
        // Delete parsed data
        $count3 = QueryBuilder::table('word_occurrences')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        $count2 = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();

        // Mark as archived
        $bindings = [$textId];
        $archived = Connection::preparedExecute(
            "UPDATE texts SET TxArchivedAt = NOW(), TxPosition = 0, TxAudioPosition = 0
            WHERE TxID = ? AND TxArchivedAt IS NULL"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );

        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        return "Sentences deleted: $count2, Text items deleted: $count3, Archived: $archived";
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

        $ids = array_map('intval', $textIds);
        $count = 0;

        foreach ($ids as $textId) {
            // Delete parsed data
            QueryBuilder::table('word_occurrences')
                ->where('Ti2TxID', '=', $textId)
                ->delete();
            QueryBuilder::table('sentences')
                ->where('SeTxID', '=', $textId)
                ->delete();

            // Mark as archived
            $bindings = [$textId];
            $count += Connection::preparedExecute(
                "UPDATE texts SET TxArchivedAt = NOW(), TxPosition = 0, TxAudioPosition = 0
                WHERE TxID = ? AND TxArchivedAt IS NULL"
                . UserScopedQuery::forTablePrepared('texts', $bindings),
                $bindings
            );
        }

        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        return "Archived Text(s): {$count}";
    }

    /**
     * Unarchive a text (restore from archived state).
     *
     * Sets TxArchivedAt to NULL and re-parses the text.
     *
     * @param int $textId Text ID (archived)
     *
     * @return array{message: string, textId: int|null} Result with message and text ID
     */
    public function unarchive(int $textId): array
    {
        // Get language ID first
        $bindings = [$textId];
        $text = Connection::preparedFetchOne(
            "SELECT TxLgID, TxText FROM texts
            WHERE TxID = ? AND TxArchivedAt IS NOT NULL"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );

        if ($text === null) {
            return ['message' => 'Archived text not found', 'textId' => null];
        }

        // Unarchive
        $bindings2 = [$textId];
        $unarchived = Connection::preparedExecute(
            "UPDATE texts SET TxArchivedAt = NULL
            WHERE TxID = ? AND TxArchivedAt IS NOT NULL"
            . UserScopedQuery::forTablePrepared('texts', $bindings2),
            $bindings2
        );

        // Re-parse the text
        TextParsing::parseAndSave((string)($text['TxText'] ?? ''), (int) $text['TxLgID'], $textId);

        // Get statistics
        $bindings3 = [$textId];
        $sentenceCount = (int)Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings3, '', 'texts'),
            $bindings3,
            'cnt'
        );
        $bindings4 = [$textId];
        $itemCount = (int)Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM word_occurrences WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('word_occurrences', $bindings4, '', 'texts'),
            $bindings4,
            'cnt'
        );

        $message = "Unarchived: $unarchived / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

        return ['message' => $message, 'textId' => $textId];
    }

    /**
     * Unarchive multiple texts.
     *
     * @param array $textIds Array of archived text IDs
     *
     * @return string Result message
     */
    public function unarchiveMultiple(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $ids = array_map('intval', $textIds);
        $count = 0;

        foreach ($ids as $textId) {
            $result = $this->unarchive($textId);
            if ($result['textId'] !== null) {
                $count++;
            }
        }

        return "Unarchived Text(s): {$count}";
    }
}
