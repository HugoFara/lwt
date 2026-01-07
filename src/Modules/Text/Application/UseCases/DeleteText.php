<?php declare(strict_types=1);
/**
 * Delete Text Use Case
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
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

/**
 * Use case for deleting texts.
 *
 * Handles deletion of both active and archived texts including
 * cleanup of related data (sentences, text items, tags).
 *
 * @since 3.0.0
 */
class DeleteText
{
    /**
     * Delete an active text.
     *
     * @param int $textId Text ID
     *
     * @return string Result message
     */
    public function execute(int $textId): string
    {
        $count3 = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        $count2 = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();
        $count1 = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->delete();

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "Texts deleted: $count1 / Sentences deleted: $count2 / Text items deleted: $count3";
    }

    /**
     * Delete multiple active texts.
     *
     * @param array $textIds Array of text IDs
     *
     * @return string Result message
     */
    public function deleteMultiple(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $ids = array_map('intval', $textIds);

        // Delete text items
        QueryBuilder::table('textitems2')
            ->whereIn('Ti2TxID', $ids)
            ->delete();

        // Delete sentences
        QueryBuilder::table('sentences')
            ->whereIn('SeTxID', $ids)
            ->delete();

        // Delete texts
        $affectedRows = QueryBuilder::table('texts')
            ->whereIn('TxID', $ids)
            ->delete();

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "Texts deleted: $affectedRows";
    }

    /**
     * Delete an archived text.
     *
     * @param int $textId Archived text ID
     *
     * @return string Result message
     */
    public function deleteArchivedText(int $textId): string
    {
        $deleted = QueryBuilder::table('archivedtexts')
            ->where('AtID', '=', $textId)
            ->delete();
        $message = "Archived Texts deleted: $deleted";
        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();
        return $message;
    }

    /**
     * Delete multiple archived texts.
     *
     * @param array $textIds Array of archived text IDs
     *
     * @return string Result message
     */
    public function deleteArchivedTexts(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $affectedRows = QueryBuilder::table('archivedtexts')
            ->whereIn('AtID', array_map('intval', $textIds))
            ->delete();
        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();
        return "Archived Texts deleted: $affectedRows";
    }

    /**
     * Clean up orphaned text tags.
     *
     * @return void
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
     *
     * @return void
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
