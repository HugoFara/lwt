<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Services;

use Lwt\Modules\Vocabulary\Application\Services\WordLinkingService;
use Lwt\Modules\Vocabulary\Http\TermTranslationApiHandler;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * `linkToTextItems()` must not re-point occurrences in a language the caller
 * does not own.
 *
 * The statement is
 *   `UPDATE word_occurrences SET Ti2WoID = ? WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?`
 * with no user filter. Its comment says the table "inherits user context via
 * Ti2TxID -> texts FK", but the query never joins `texts`, so it inherits
 * nothing. What actually confines it is that a `languages` row belongs to one
 * user — which only holds while the language ID is the caller's own.
 *
 * Two of the three call sites derive the language from a user-scoped text
 * lookup and are fine. `TermEditController::createWord()` takes it from the
 * request (`InputValidator::getInt('WoLgID')`) and passes it straight through,
 * and `/word/new` is registered for every HTTP method — so `POST /word/new`
 * with somebody else's `WoLgID` reaches this update.
 *
 * `TermTranslationApiHandler` (POST /api/v1/terms/new) had a second copy of
 * the same statement inline, reachable straight from the API, which the fix to
 * the service did not cover until the copy was replaced by a call to it.
 */
class WordLinkingOwnershipTest extends TestCase
{
    private WordLinkingService $service;

    /** @var list<array{0: int, 1: int}> Ti2TxID/Ti2Order pairs to clean up */
    private array $rows = [];

    /** A language ID that belongs to nobody in this session. */
    private const FOREIGN_LANGUAGE_ID = 210;

    /** A text ID used only by this test's fixtures. */
    private const FIXTURE_TEXT_ID = 32100;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->service = new WordLinkingService();
    }

    protected function tearDown(): void
    {
        foreach ($this->rows as [$textId, $order]) {
            Connection::preparedExecute(
                'DELETE FROM word_occurrences WHERE Ti2TxID = ? AND Ti2Order = ?',
                [$textId, $order]
            );
        }
        $this->rows = [];
    }

    /**
     * Insert an occurrence belonging to a foreign language, unlinked.
     *
     * @param int    $order Position, unique per fixture
     * @param string $text  Occurrence text
     */
    private function makeForeignOccurrence(int $order, string $text): void
    {
        Connection::preparedExecute(
            'INSERT INTO word_occurrences
             (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES (NULL, ?, ?, 1, ?, 1, ?)',
            [self::FOREIGN_LANGUAGE_ID, self::FIXTURE_TEXT_ID, $order, $text]
        );
        $this->rows[] = [self::FIXTURE_TEXT_ID, $order];
    }

    /**
     * Read back the word an occurrence is linked to.
     *
     * @param int $order Position of the fixture row
     */
    private function linkedWordId(int $order): ?int
    {
        $rows = Connection::preparedFetchAll(
            'SELECT Ti2WoID FROM word_occurrences WHERE Ti2TxID = ? AND Ti2Order = ?',
            [self::FIXTURE_TEXT_ID, $order]
        );
        $value = $rows[0]['Ti2WoID'] ?? null;
        return $value === null ? null : (int) $value;
    }

    #[Test]
    public function linkingDoesNotTouchOccurrencesOfAForeignLanguage(): void
    {
        $term = 'cylinktest' . substr(uniqid(), -6);
        $this->makeForeignOccurrence(9001, $term);

        // 987654 stands in for a word the caller just created. The language,
        // however, is not theirs.
        $this->service->linkToTextItems(987654, self::FOREIGN_LANGUAGE_ID, $term);

        $this->assertNull(
            $this->linkedWordId(9001),
            'linkToTextItems() re-pointed an occurrence in a language the caller '
            . 'does not own. word_occurrences has no owner column and this query '
            . 'does not join texts, so the language ID must be validated as the '
            . "caller's before the update — TermEditController::createWord() "
            . 'takes it straight from the request.'
        );
    }

    #[Test]
    public function linkingStillWorksForALanguageTheCallerOwns(): void
    {
        // The gate must not break the ordinary path. In single-user mode every
        // language is the caller's, which is how most installs run.
        $langId = $this->firstOwnedLanguageId();
        if ($langId === null) {
            $this->markTestSkipped('No language available in the test database');
        }

        $term = 'cylinkok' . substr(uniqid(), -6);
        Connection::preparedExecute(
            'INSERT INTO word_occurrences
             (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES (NULL, ?, ?, 1, ?, 1, ?)',
            [$langId, self::FIXTURE_TEXT_ID, 9002, $term]
        );
        $this->rows[] = [self::FIXTURE_TEXT_ID, 9002];

        $this->service->linkToTextItems(987654, $langId, $term);

        $this->assertSame(
            987654,
            $this->linkedWordId(9002),
            'A language the caller owns must still link normally.'
        );
    }

    #[Test]
    public function theTermsApiDelegatesLinkingRatherThanRepeatingIt(): void
    {
        // POST /api/v1/terms/new takes language_id straight from the request
        // and used to carry its own copy of the linking UPDATE, without the
        // ownership check — so fixing WordLinkingService did not fix it.
        //
        // A behavioural test cannot reach that code: the word INSERT fails on
        // the languages foreign key before the linking runs, so it would pass
        // against the unfixed version too. What actually protects the endpoint
        // is that it delegates, so that is what this asserts.
        $source = (string) file_get_contents(
            dirname(__DIR__, 5) . '/src/Modules/Vocabulary/Http/TermTranslationApiHandler.php'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/UPDATE\s+word_occurrences/i',
            $source,
            'TermTranslationApiHandler writes word_occurrences directly again. '
            . 'That table has no owner column and the language ID comes from '
            . 'the request, so the update must go through '
            . 'WordLinkingService::linkToTextItems(), which holds the check.'
        );

        $this->assertStringContainsString(
            'linkToTextItems(',
            $source,
            'The handler should link occurrences through WordLinkingService.'
        );
    }

    /**
     * The first language visible to the current (scoped) caller.
     */
    private function firstOwnedLanguageId(): ?int
    {
        $bindings = [];
        $scope = \Lwt\Shared\Infrastructure\Database\UserScopedQuery::forTablePrepared('languages', $bindings);
        $rows = Connection::preparedFetchAll(
            'SELECT LgID FROM languages WHERE 1=1' . $scope . ' ORDER BY LgID LIMIT 1',
            $bindings
        );
        return isset($rows[0]['LgID']) ? (int) $rows[0]['LgID'] : null;
    }
}
