<?php

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

declare(strict_types=1);

namespace Lwt\Modules\Feed\Infrastructure;

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Maintenance;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\TextParsing;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
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
        $sql = "INSERT IGNORE INTO text_tags (T2Text) VALUES (?)"
            . UserScopedQuery::forTablePrepared('text_tags', $bindings);
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
        $sql = "INSERT INTO text_tag_map (TtTxID, TtT2ID)
             SELECT ?, T2ID FROM text_tags
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('text_tags', $bindings);
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
        $sql = "SELECT TtTxID FROM text_tag_map
             JOIN text_tags ON TtT2ID = T2ID
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('text_tags', $bindings);
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
            $stats['textitems'] += QueryBuilder::table('word_occurrences')
                ->where('Ti2TxID', '=', $textId)
                ->delete();

            // Delete sentences
            $stats['sentences'] += QueryBuilder::table('sentences')
                ->where('SeTxID', '=', $textId)
                ->delete();

            // Archive the text (soft delete - set TxArchivedAt)
            $bindings = [$textId];
            $sql = "UPDATE texts SET TxArchivedAt = NOW(), TxPosition = 0, TxAudioPosition = 0
                    WHERE TxID = ? AND TxArchivedAt IS NULL"
                . UserScopedQuery::forTablePrepared('texts', $bindings);
            $archived = Connection::preparedExecute($sql, $bindings);

            if ($archived > 0) {
                $stats['archived']++;
            }

            Maintenance::adjustAutoIncrement('sentences', 'SeID');
        }

        return $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function countTextsWithTag(string $tagName): int
    {
        $bindings = [$tagName];
        $sql = "SELECT COUNT(DISTINCT TtTxID) as cnt FROM text_tag_map
             JOIN text_tags ON TtT2ID = T2ID
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('text_tags', $bindings);

        $row = Connection::preparedFetchOne($sql, $bindings);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function sourceUriExists(string $sourceUri): bool
    {
        $trimmedUri = trim($sourceUri);

        // Check texts table (includes both active and archived texts)
        return QueryBuilder::table('texts')
            ->where('TxSourceURI', '=', $trimmedUri)
            ->existsPrepared();
    }
}
