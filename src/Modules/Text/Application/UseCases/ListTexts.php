<?php declare(strict_types=1);
/**
 * List Texts Use Case
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
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Text\Domain\TextRepositoryInterface;

/**
 * Use case for listing texts with filtering and pagination.
 *
 * Handles both active and archived text listing with support for
 * language filtering, search queries, tag filters, and pagination.
 *
 * @since 3.0.0
 */
class ListTexts
{
    private TextRepositoryInterface $textRepository;
    private BuildTextFilters $filterBuilder;

    /**
     * Constructor.
     *
     * @param TextRepositoryInterface $textRepository Text repository
     * @param BuildTextFilters|null   $filterBuilder  Filter builder (optional)
     */
    public function __construct(
        TextRepositoryInterface $textRepository,
        ?BuildTextFilters $filterBuilder = null
    ) {
        $this->textRepository = $textRepository;
        $this->filterBuilder = $filterBuilder ?? new BuildTextFilters();
    }

    /**
     * Get texts per page setting.
     *
     * @return int Items per page
     */
    public function getTextsPerPage(): int
    {
        return (int) Settings::getWithDefault('set-texts-per-page');
    }

    /**
     * Get archived texts per page setting.
     *
     * @return int Items per page
     */
    public function getArchivedTextsPerPage(): int
    {
        return (int) Settings::getWithDefault('set-archivedtexts-per-page');
    }

    /**
     * Calculate pagination info.
     *
     * @param int $totalCount  Total number of items
     * @param int $currentPage Current page number
     * @param int $perPage     Items per page
     *
     * @return array{pages: int, currentPage: int, limit: string}
     */
    public function getPagination(int $totalCount, int $currentPage, int $perPage): array
    {
        $pages = $totalCount === 0 ? 0 : (int) ceil($totalCount / $perPage);

        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if ($currentPage > $pages && $pages > 0) {
            $currentPage = $pages;
        }

        $offset = ($currentPage - 1) * $perPage;
        $limit = "LIMIT {$offset},{$perPage}";

        return [
            'pages' => $pages,
            'currentPage' => $currentPage,
            'limit' => $limit
        ];
    }

    /**
     * Get count of active texts matching filters.
     *
     * @param string $whLang  Language WHERE clause
     * @param string $whQuery Query WHERE clause
     * @param string $whTag   Tag HAVING clause
     *
     * @return int Number of matching texts
     */
    public function getTextCount(
        string $whLang,
        string $whQuery,
        string $whTag
    ): int {
        $sql = "SELECT COUNT(*) AS cnt FROM (
            SELECT TxID FROM (
                texts
                LEFT JOIN texttags ON TxID = TtTxID
            ) WHERE (1=1) {$whLang}{$whQuery}
            GROUP BY TxID {$whTag}
        ) AS dummy" . UserScopedQuery::forTable('texts');
        return (int) Connection::fetchValue($sql, 'cnt');
    }

    /**
     * Get active texts list with pagination.
     *
     * @param string $whLang  Language WHERE clause
     * @param string $whQuery Query WHERE clause
     * @param string $whTag   Tag HAVING clause
     * @param int    $sort    Sort index (1-based)
     * @param int    $page    Page number (1-based)
     * @param int    $perPage Items per page
     *
     * @return array Array of text records
     */
    public function getTextsList(
        string $whLang,
        string $whQuery,
        string $whTag,
        int $sort,
        int $page,
        int $perPage
    ): array {
        $sorts = ['TxTitle', 'TxID desc', 'TxID'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;
        $limit = "LIMIT {$offset},{$perPage}";

        $sql = "SELECT TxID, TxTitle, LgName, TxAudioURI, TxSourceURI,
            LENGTH(TxAnnotatedText) AS annotlen,
            (SELECT COUNT(*) FROM sentences WHERE SeTxID = TxID) AS sentnum,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (texts
                LEFT JOIN texttags ON TxID = TtTxID)
                LEFT JOIN text_tags ON T2ID = TtT2ID
            ), languages
            WHERE LgID=TxLgID {$whLang}{$whQuery}
            GROUP BY TxID {$whTag}
            ORDER BY {$sortColumn}
            {$limit}"
            . UserScopedQuery::forTable('texts')
            . UserScopedQuery::forTable('text_tags')
            . UserScopedQuery::forTable('languages');

        $res = Connection::query($sql);
        $texts = [];
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $texts[] = $record;
            }
            mysqli_free_result($res);
        }
        return $texts;
    }

    /**
     * Get count of archived texts matching filters.
     *
     * @param string $whLang  Language WHERE clause
     * @param string $whQuery Query WHERE clause
     * @param string $whTag   Tag HAVING clause
     *
     * @return int Number of matching archived texts
     */
    public function getArchivedTextCount(
        string $whLang,
        string $whQuery,
        string $whTag
    ): int {
        $sql = "SELECT COUNT(*) AS cnt FROM (
            SELECT AtID FROM (
                archivedtexts
                LEFT JOIN archtexttags ON AtID = AgAtID
            ) WHERE (1=1) {$whLang}{$whQuery}
            GROUP BY AtID {$whTag}
        ) AS dummy" . UserScopedQuery::forTable('archivedtexts');
        return (int) Connection::fetchValue($sql, 'cnt');
    }

    /**
     * Get archived texts list with pagination.
     *
     * @param string $whLang  Language WHERE clause
     * @param string $whQuery Query WHERE clause
     * @param string $whTag   Tag HAVING clause
     * @param int    $sort    Sort index (1-based)
     * @param int    $page    Page number (1-based)
     * @param int    $perPage Items per page
     *
     * @return array Array of archived text records
     */
    public function getArchivedTextsList(
        string $whLang,
        string $whQuery,
        string $whTag,
        int $sort,
        int $page,
        int $perPage
    ): array {
        $sorts = ['AtTitle', 'AtID desc', 'AtID'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;
        $limit = "LIMIT {$offset},{$perPage}";

        $sql = "SELECT AtID, AtTitle, LgName, AtAudioURI, AtSourceURI,
            LENGTH(AtAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (archivedtexts
                LEFT JOIN archtexttags ON AtID = AgAtID)
                LEFT JOIN text_tags ON T2ID = AgT2ID
            ), languages
            WHERE LgID=AtLgID {$whLang}{$whQuery}
            GROUP BY AtID {$whTag}
            ORDER BY {$sortColumn}
            {$limit}"
            . UserScopedQuery::forTable('archivedtexts')
            . UserScopedQuery::forTable('text_tags')
            . UserScopedQuery::forTable('languages');

        $res = Connection::query($sql);
        $texts = [];
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $texts[] = $record;
            }
            mysqli_free_result($res);
        }
        return $texts;
    }

    /**
     * Get texts for a specific language with pagination.
     *
     * @param int $languageId Language ID
     * @param int $page       Page number
     * @param int $perPage    Items per page
     *
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public function getTextsForLanguage(int $languageId, int $page = 1, int $perPage = 20): array
    {
        return $this->textRepository->findPaginated($languageId, $page, $perPage);
    }
}
