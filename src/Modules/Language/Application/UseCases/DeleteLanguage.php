<?php declare(strict_types=1);
/**
 * Delete Language Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Language\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Language\Application\UseCases;

use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Modules\Language\Domain\LanguageRepositoryInterface;
use Lwt\Modules\Language\Infrastructure\MySqlLanguageRepository;

/**
 * Use case for deleting a language with dependency checking.
 *
 * @since 3.0.0
 */
class DeleteLanguage
{
    private LanguageRepositoryInterface $repository;

    /**
     * @param LanguageRepositoryInterface|null $repository Repository instance
     */
    public function __construct(?LanguageRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new MySqlLanguageRepository();
    }

    /**
     * Delete a language.
     *
     * @param int $id Language ID
     *
     * @return string Result message
     */
    public function execute(int $id): string
    {
        // Check for related data
        $stats = $this->getRelatedDataCounts($id);

        if ($stats['texts'] > 0 || $stats['archivedTexts'] > 0 ||
            $stats['words'] > 0 || $stats['feeds'] > 0) {
            return 'You must first delete texts, archived texts, news_feeds and words with this language!';
        }

        $affected = QueryBuilder::table('languages')
            ->where('LgID', '=', $id)
            ->delete();
        return "Deleted: " . $affected;
    }

    /**
     * Delete a language by ID (API-friendly version).
     *
     * @param int $id Language ID
     *
     * @return bool True if deleted
     */
    public function deleteById(int $id): bool
    {
        $affected = QueryBuilder::table('languages')
            ->where('LgID', '=', $id)
            ->delete();
        return $affected > 0;
    }

    /**
     * Check if a language can be deleted (no related data).
     *
     * @param int $id Language ID
     *
     * @return bool
     */
    public function canDelete(int $id): bool
    {
        $stats = $this->getRelatedDataCounts($id);
        return $stats['texts'] === 0 &&
               $stats['archivedTexts'] === 0 &&
               $stats['words'] === 0 &&
               $stats['feeds'] === 0;
    }

    /**
     * Get counts of related data for a language.
     *
     * @param int $id Language ID
     *
     * @return array{texts: int, archivedTexts: int, words: int, feeds: int}
     */
    public function getRelatedDataCounts(int $id): array
    {
        return [
            'texts' => QueryBuilder::table('texts')
                ->where('TxLgID', '=', $id)
                ->count(),
            'archivedTexts' => QueryBuilder::table('archived_texts')
                ->where('AtLgID', '=', $id)
                ->count(),
            'words' => QueryBuilder::table('words')
                ->where('WoLgID', '=', $id)
                ->count(),
            'feeds' => QueryBuilder::table('news_feeds')
                ->where('NfLgID', '=', $id)
                ->count(),
        ];
    }
}
