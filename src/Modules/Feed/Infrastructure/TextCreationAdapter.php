<?php declare(strict_types=1);
/**
 * Text Creation Adapter
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Feed\Infrastructure;

use Lwt\Database\Connection;
use Lwt\Database\Maintenance;
use Lwt\Database\QueryBuilder;
use Lwt\Database\TextParsing;
use Lwt\Database\UserScopedQuery;
use Lwt\Modules\Feed\Domain\TextCreationInterface;

/**
 * Adapter implementing TextCreationInterface.
 *
 * Provides text creation functionality for the Feed module by
 * using existing LWT infrastructure (TextParsing, QueryBuilder, etc.).
 *
 * @since 3.0.0
 */
class TextCreationAdapter implements TextCreationInterface
{
    /**
     * {@inheritdoc}
     */
    public function createText(
        int $languageId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri,
        string $tagName
    ): int {
        // Ensure tag exists - use raw SQL for INSERT IGNORE
        $bindings = [$tagName];
        $sql = "INSERT IGNORE INTO tags2 (T2Text) VALUES (?)"
            . UserScopedQuery::forTablePrepared('tags2', $bindings);
        Connection::preparedExecute($sql, $bindings);

        // Create the text
        $textId = QueryBuilder::table('texts')
            ->insertPrepared([
                'TxLgID' => $languageId,
                'TxTitle' => $title,
                'TxText' => $text,
                'TxAudioURI' => $audioUri,
                'TxSourceURI' => $sourceUri,
            ]);

        // Parse the text into sentences and textitems
        TextParsing::parseAndSave(
            $text,
            $languageId,
            (int) $textId
        );

        // Apply tag to the text
        $bindings = [(int) $textId, $tagName];
        $sql = "INSERT INTO texttags (TtTxID, TtT2ID)
             SELECT ?, T2ID FROM tags2
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('tags2', $bindings);
        Connection::preparedExecute($sql, $bindings);

        return (int) $textId;
    }

    /**
     * {@inheritdoc}
     */
    public function archiveOldTexts(string $tagName, int $maxTexts): array
    {
        // Get all text IDs with this tag
        $bindings = [$tagName];
        $sql = "SELECT TtTxID FROM texttags
             JOIN tags2 ON TtT2ID = T2ID
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('tags2', $bindings);
        $rows = Connection::preparedFetchAll($sql, $bindings);

        $textIds = [];
        foreach ($rows as $row) {
            $textIds[] = (int) $row['TtTxID'];
        }

        $stats = ['archived' => 0, 'sentences' => 0, 'textitems' => 0];

        if (count($textIds) <= $maxTexts) {
            return $stats;
        }

        // Sort by ID (oldest first) and archive the excess
        sort($textIds, SORT_NUMERIC);
        $textsToArchive = array_slice($textIds, 0, count($textIds) - $maxTexts);

        foreach ($textsToArchive as $textId) {
            // Delete textitems
            $stats['textitems'] += QueryBuilder::table('textitems2')
                ->where('Ti2TxID', '=', $textId)
                ->delete();

            // Delete sentences
            $stats['sentences'] += QueryBuilder::table('sentences')
                ->where('SeTxID', '=', $textId)
                ->delete();

            // Archive the text
            $bindings = [$textId];
            $sql = "INSERT INTO archivedtexts (
                    AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI"
                    . UserScopedQuery::insertColumn('archivedtexts')
                . ")
                SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertValue('archivedtexts')
                . " FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings);
            Connection::preparedExecute($sql, $bindings);

            $archiveId = (int) Connection::lastInsertId();

            // Copy tags to archive
            Connection::preparedExecute(
                "INSERT INTO archtexttags (AgAtID, AgT2ID)
                 SELECT ?, TtT2ID FROM texttags
                 WHERE TtTxID = ?",
                [$archiveId, $textId]
            );

            // Delete original text
            $stats['archived'] += QueryBuilder::table('texts')
                ->where('TxID', '=', $textId)
                ->delete();

            Maintenance::adjustAutoIncrement('texts', 'TxID');
            Maintenance::adjustAutoIncrement('sentences', 'SeID');

            // Clean orphaned text tags
            Connection::execute(
                "DELETE texttags FROM (
                    texttags LEFT JOIN texts ON TtTxID = TxID
                ) WHERE TxID IS NULL"
            );
        }

        return $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function countTextsWithTag(string $tagName): int
    {
        $bindings = [$tagName];
        $sql = "SELECT COUNT(DISTINCT TtTxID) as cnt FROM texttags
             JOIN tags2 ON TtT2ID = T2ID
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('tags2', $bindings);

        $row = Connection::preparedFetchOne($sql, $bindings);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function sourceUriExists(string $sourceUri): bool
    {
        $trimmedUri = trim($sourceUri);

        // Check texts table
        $textExists = QueryBuilder::table('texts')
            ->where('TxSourceURI', '=', $trimmedUri)
            ->existsPrepared();

        if ($textExists) {
            return true;
        }

        // Check archivedtexts table
        return QueryBuilder::table('archivedtexts')
            ->where('AtSourceURI', '=', $trimmedUri)
            ->existsPrepared();
    }
}
