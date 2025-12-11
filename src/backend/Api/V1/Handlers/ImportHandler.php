<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Database\Connection;

/**
 * Handler for imported terms API operations.
 *
 * Extracted from api_v1.php lines 377-463.
 */
class ImportHandler
{
    /**
     * Limit the current page within valid bounds.
     *
     * @param int $currentpage Current page number
     * @param int $recno       Record number
     * @param int $maxperpage  Maximum records per page
     *
     * @return int Valid page number
     */
    public function limitCurrentPage(int $currentpage, int $recno, int $maxperpage): int
    {
        $pages = intval(($recno - 1) / $maxperpage) + 1;
        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if ($currentpage > $pages) {
            $currentpage = $pages;
        }
        return $currentpage;
    }

    /**
     * Select imported terms from the database.
     *
     * @param string $lastUpdate Last update timestamp
     * @param int    $offset     Offset for pagination
     * @param int    $maxTerms   Maximum terms to return
     *
     * @return array<array<float|int|null|string>>
     */
    public function selectImportedTerms(string $lastUpdate, int $offset, int $maxTerms): array
    {
        $wordsTable = Globals::table('words');
        $wordtagsTable = Globals::table('wordtags');
        $tagsTable = Globals::table('tags');
        $sql = "SELECT WoID, WoText, WoTranslation, WoRomanization, WoSentence,
        IFNULL(WoSentence, '') LIKE CONCAT('%{', WoText, '}%') AS SentOK,
        WoStatus,
        IFNULL(
            group_concat(DISTINCT TgText ORDER BY TgText separator ','),
            ''
        ) AS taglist
        FROM (
            ({$wordsTable} LEFT JOIN {$wordtagsTable} ON WoID = WtWoID)
            LEFT JOIN {$tagsTable} ON TgID = WtTgID
        )
        WHERE WoStatusChanged > ?
        GROUP BY WoID
        LIMIT ?, ?";

        return Connection::preparedFetchAll($sql, [$lastUpdate, $offset, $maxTerms]);
    }

    /**
     * Return the list of imported terms with pagination information.
     *
     * @param string $lastUpdate  Terms import time
     * @param int    $currentpage Current page number
     * @param int    $recno       Number of imported terms
     *
     * @return array{navigation: array{current_page: int, total_pages: int}, terms: array<array<float|int|null|string>>}
     */
    public function importedTermsList(string $lastUpdate, int $currentpage, int $recno): array
    {
        $maxperpage = 100;
        $currentpage = $this->limitCurrentPage($currentpage, $recno, $maxperpage);
        $offset = ($currentpage - 1) * $maxperpage;

        $pages = intval(($recno - 1) / $maxperpage) + 1;
        return [
            "navigation" => [
                "current_page" => $currentpage,
                "total_pages" => $pages
            ],
            "terms" => $this->selectImportedTerms($lastUpdate, $offset, $maxperpage)
        ];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for getting imported terms.
     *
     * @param string $lastUpdate Last update timestamp
     * @param int    $page       Page number
     * @param int    $count      Total count of terms
     *
     * @return array{navigation: array{current_page: int, total_pages: int}, terms: array<array<float|int|null|string>>}
     */
    public function formatImportedTerms(string $lastUpdate, int $page, int $count): array
    {
        return $this->importedTermsList($lastUpdate, $page, $count);
    }
}
