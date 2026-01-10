<?php declare(strict_types=1);
/**
 * Update Text Use Case
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
use Lwt\Modules\Text\Domain\TextRepositoryInterface;

/**
 * Use case for updating texts.
 *
 * Handles updates to both active and archived texts, including
 * reparsing when text content changes.
 *
 * @since 3.0.0
 */
class UpdateText
{
    private TextRepositoryInterface $textRepository;

    /**
     * Constructor.
     *
     * @param TextRepositoryInterface $textRepository Text repository
     */
    public function __construct(TextRepositoryInterface $textRepository)
    {
        $this->textRepository = $textRepository;
    }

    /**
     * Update an active text.
     *
     * @param int    $textId    Text ID
     * @param int    $languageId Language ID
     * @param string $title     Title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return array{message: string, reparsed: bool}
     */
    public function execute(
        int $textId,
        int $languageId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): array {
        // Remove soft hyphens
        $text = $this->removeSoftHyphens($text);

        // Check if text content changed
        $bindings1 = [$textId];
        $oldText = Connection::preparedFetchValue(
            "SELECT TxText FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1,
            'TxText'
        );
        $textChanged = $text !== $oldText;

        // Update text
        $bindings2 = [$languageId, $title, $text, $audioUri, $sourceUri, $textId];
        $affected = Connection::preparedExecute(
            "UPDATE texts SET
                TxLgID = ?, TxTitle = ?, TxText = ?, TxAudioURI = ?, TxSourceURI = ?
            WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings2),
            $bindings2
        );

        $message = $affected > 0 ? "Updated" : "No changes";
        $reparsed = false;

        // Reparse if text changed
        if ($affected > 0 && $textChanged) {
            $this->reparseText($textId, $languageId, $text);
            $reparsed = true;
            $message = "Updated and reparsed";
        }

        return ['message' => $message, 'reparsed' => $reparsed];
    }

    /**
     * Save text and reparse (alias for execute with reparse).
     *
     * @param int    $textId    Text ID
     * @param int    $languageId Language ID
     * @param string $title     Title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return string Result message
     */
    public function saveTextAndReparse(
        int $textId,
        int $languageId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): string {
        $result = $this->execute($textId, $languageId, $title, $text, $audioUri, $sourceUri);
        return $result['message'];
    }

    /**
     * Update an archived text.
     *
     * @param int    $textId    Archived text ID
     * @param int    $languageId Language ID
     * @param string $title     Title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return string Result message
     */
    public function updateArchivedText(
        int $textId,
        int $languageId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): string {
        // Check if text content changed
        $bindings1 = [$textId];
        $oldText = Connection::preparedFetchValue(
            "SELECT TxText FROM texts WHERE TxID = ? AND TxArchivedAt IS NOT NULL"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1,
            'TxText'
        );
        $textsdiffer = $text !== $oldText;

        $bindings2 = [$languageId, $title, $text, $audioUri, $sourceUri, $textId];
        $affected = Connection::preparedExecute(
            "UPDATE texts SET
                TxLgID = ?, TxTitle = ?, TxText = ?, TxAudioURI = ?, TxSourceURI = ?
             WHERE TxID = ? AND TxArchivedAt IS NOT NULL"
            . UserScopedQuery::forTablePrepared('texts', $bindings2),
            $bindings2
        );

        $message = $affected > 0 ? "Updated: {$affected}" : "Updated: 0";

        // Clear annotation if text changed
        if ($affected > 0 && $textsdiffer) {
            $bindings3 = [$textId];
            Connection::preparedExecute(
                "UPDATE texts SET TxAnnotatedText = '' WHERE TxID = ? AND TxArchivedAt IS NOT NULL"
                . UserScopedQuery::forTablePrepared('texts', $bindings3),
                $bindings3
            );
        }

        return $message;
    }

    /**
     * Rebuild/reparse multiple texts.
     *
     * @param array $textIds Array of text IDs
     *
     * @return string Result message
     */
    public function rebuildTexts(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $count = 0;
        $ids = array_map('intval', $textIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $records = Connection::preparedFetchAll(
            "SELECT TxID, TxLgID, TxText FROM texts WHERE TxID IN ({$placeholders})"
            . UserScopedQuery::forTablePrepared('texts', $ids),
            $ids
        );

        foreach ($records as $record) {
            $this->reparseText(
                (int) $record['TxID'],
                (int) $record['TxLgID'],
                (string) $record['TxText']
            );
            $count++;
        }

        return "Rebuilt Text(s): {$count}";
    }

    /**
     * Reparse a text (delete old parsed data and parse again).
     *
     * @param int    $textId     Text ID
     * @param int    $languageId Language ID
     * @param string $text       Text content
     */
    private function reparseText(int $textId, int $languageId, string $text): void
    {
        // Delete old parsed data
        QueryBuilder::table('word_occurrences')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();

        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        // Clear annotation
        $bindings = [$textId];
        Connection::preparedExecute(
            "UPDATE texts SET TxAnnotatedText = '' WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );

        // Parse again
        TextParsing::parseAndSave($text, $languageId, $textId);
    }

    /**
     * Remove soft hyphens from text.
     *
     * @param string $text Text to clean
     *
     * @return string Cleaned text
     */
    private function removeSoftHyphens(string $text): string
    {
        return str_replace("\xC2\xAD", "", $text);
    }
}
