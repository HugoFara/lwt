<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services\Anki;

use Lwt\Modules\Vocabulary\Application\Services\Anki\AnkiDeckImportService;
use Lwt\Modules\Vocabulary\Application\Services\Anki\DeckImportSettings;
use Lwt\Tests\Modules\Vocabulary\Infrastructure\Anki\ForeignDeckBuilder;
use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end import of a deck built in Anki (issue #228).
 *
 * This is the scenario the issue describes: a user with thousands of words
 * already studied in Anki, who does not want to reclassify them by hand.
 */
#[Group('integration')]
final class AnkiDeckImportIntegrationTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $languageId = 0;
    private string $deckPath = '';

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbName = 'test_' . $config['dbname'];

        if (!Globals::getDbConnection()) {
            try {
                $connection = Configuration::connect(
                    $config['server'],
                    $config['userid'],
                    $config['passwd'],
                    $testDbName,
                    $config['socket'] ?? ''
                );
                Globals::setDbConnection($connection);
                self::$dbConnected = true;
            } catch (\Throwable) {
                self::$dbConnected = false;
            }
        } else {
            self::$dbConnected = true;
        }

        if (!self::$dbConnected) {
            return;
        }

        Connection::query(
            "INSERT INTO languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters,
                LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization
            ) VALUES (
                'AnkiDeckImportTest_Lang', 'https://dict.test/deck', '', '',
                100, '.!?', 'a-zA-Z',
                0, 0, 0, 1
            )"
        );
        self::$languageId = (int) mysqli_insert_id(Globals::getDbConnection());
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected || self::$languageId === 0) {
            return;
        }

        Connection::query(
            'DELETE FROM word_tag_map WHERE WtWoID IN ('
            . 'SELECT WoID FROM words WHERE WoLgID = ' . self::$languageId . ')'
        );
        Connection::query('DELETE FROM words WHERE WoLgID = ' . self::$languageId);
        Connection::query('DELETE FROM languages WHERE LgID = ' . self::$languageId);
    }

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite required to read an .apkg collection');
        }
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        if (!self::$dbConnected) {
            $this->markTestSkipped('Test database setup failed');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'lwt_deckimport_');
        $this->deckPath = $tmp !== false ? $tmp . '.apkg' : '';
    }

    protected function tearDown(): void
    {
        if ($this->deckPath !== '') {
            ForeignDeckBuilder::cleanup($this->deckPath);
        }
        if (self::$dbConnected && self::$languageId > 0) {
            Connection::query(
                'DELETE FROM word_tag_map WHERE WtWoID IN ('
                . 'SELECT WoID FROM words WHERE WoLgID = ' . self::$languageId . ')'
            );
            Connection::query('DELETE FROM words WHERE WoLgID = ' . self::$languageId);
        }
    }

    private function settings(bool $derive = true, ?string $translation = 'Back'): DeckImportSettings
    {
        return new DeckImportSettings(
            notetypeId: 1500000000000,
            termField: 'Front',
            translationField: $translation,
            languageId: self::$languageId,
            deriveStatus: $derive,
        );
    }

    public function testImportsAForeignDeckAndDerivesStatusFromMaturity(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['hund', 'the dog'], 200)
            ->addYoungNote(['katze', 'the cat'], 8)
            ->addNote(['pferd', 'the horse'])
            ->addSuspendedNote(['maus', 'the mouse'])
            ->build($this->deckPath);

        $result = AnkiDeckImportService::default()->import($this->deckPath, $this->settings());

        $this->assertSame(4, $result->totalNotes);
        $this->assertSame(4, $result->created);
        $this->assertSame(0, $result->skippedExisting);

        $rows = Connection::preparedFetchAll(
            'SELECT WoTextLC, WoStatus, WoTranslation FROM words WHERE WoLgID = ? ORDER BY WoTextLC',
            [self::$languageId]
        );

        $byTerm = [];
        foreach ($rows as $row) {
            $byTerm[(string) $row['WoTextLC']] = $row;
        }

        $this->assertSame(99, (int) $byTerm['hund']['WoStatus'], 'a mature card is well known');
        $this->assertSame(3, (int) $byTerm['katze']['WoStatus'], '8-day interval lands mid-learning');
        $this->assertSame(1, (int) $byTerm['pferd']['WoStatus'], 'an unstudied card starts at 1');
        $this->assertSame(98, (int) $byTerm['maus']['WoStatus'], 'a suspended card is ignored');

        $this->assertSame('the dog', (string) $byTerm['hund']['WoTranslation']);
    }

    public function testReimportingTheSameDeckCreatesNothingNew(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['wiederholung', 'repetition'], 60)
            ->build($this->deckPath);

        $service = AnkiDeckImportService::default();

        $first = $service->import($this->deckPath, $this->settings());
        $this->assertSame(1, $first->created);

        $second = $service->import($this->deckPath, $this->settings());
        $this->assertSame(0, $second->created, 'importing twice must not duplicate');
        $this->assertSame(1, $second->skippedExisting);

        $count = (int) Connection::preparedFetchValue(
            'SELECT COUNT(*) AS value FROM words WHERE WoLgID = ? AND WoTextLC = ?',
            [self::$languageId, 'wiederholung']
        );
        $this->assertSame(1, $count);
    }

    public function testDuplicateWordsWithinOneDeckAreCollapsed(): void
    {
        // Forward and reverse notes for the same word are common.
        (new ForeignDeckBuilder())
            ->addMatureNote(['gleich', 'same'], 40)
            ->addMatureNote(['Gleich', 'same (capitalised)'], 40)
            ->build($this->deckPath);

        $result = AnkiDeckImportService::default()->import($this->deckPath, $this->settings());

        $this->assertSame(1, $result->created, 'case-insensitive duplicates collapse');
        $this->assertSame(1, $result->skippedExisting);
    }

    public function testHtmlInFieldsIsStrippedBeforeStoring(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['<div><b>der&nbsp;Baum</b><br>[sound:baum.mp3]</div>', '<i>the tree</i>'], 50)
            ->build($this->deckPath);

        AnkiDeckImportService::default()->import($this->deckPath, $this->settings());

        $row = Connection::preparedFetchOne(
            'SELECT WoText, WoTranslation FROM words WHERE WoLgID = ?',
            [self::$languageId]
        );

        $this->assertNotNull($row);
        $this->assertSame('der Baum', (string) $row['WoText']);
        $this->assertSame('the tree', (string) $row['WoTranslation']);
    }

    public function testEmptyTermFieldsAreSkippedNotStored(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['', 'orphan translation'], 30)
            ->addMatureNote(['gut', 'good'], 30)
            ->build($this->deckPath);

        $result = AnkiDeckImportService::default()->import($this->deckPath, $this->settings());

        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->skippedEmpty);
    }

    public function testFixedStatusAppliesToEveryTerm(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['eins', 'one'], 200)
            ->addNote(['zwei', 'two'])
            ->build($this->deckPath);

        $settings = new DeckImportSettings(
            notetypeId: 1500000000000,
            termField: 'Front',
            translationField: 'Back',
            languageId: self::$languageId,
            deriveStatus: false,
            fixedStatus: 99,
        );

        AnkiDeckImportService::default()->import($this->deckPath, $settings);

        $statuses = Connection::preparedFetchAll(
            'SELECT DISTINCT WoStatus FROM words WHERE WoLgID = ?',
            [self::$languageId]
        );

        $this->assertCount(1, $statuses);
        $this->assertSame(99, (int) $statuses[0]['WoStatus']);
    }

    public function testTagsAreImportedWhenRequested(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['getaggt', 'tagged'], 40, 'noun core1000')
            ->build($this->deckPath);

        AnkiDeckImportService::default()->import($this->deckPath, $this->settings());

        $wordId = (int) Connection::preparedFetchValue(
            'SELECT WoID AS value FROM words WHERE WoLgID = ? AND WoTextLC = ?',
            [self::$languageId, 'getaggt']
        );

        $tagCount = (int) Connection::preparedFetchValue(
            'SELECT COUNT(*) AS value FROM word_tag_map WHERE WtWoID = ?',
            [$wordId]
        );

        $this->assertSame(2, $tagCount);
    }

    public function testTranslationIsOptional(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['nurwort', 'ignored'], 40)
            ->build($this->deckPath);

        $result = AnkiDeckImportService::default()
            ->import($this->deckPath, $this->settings(translation: null));

        $this->assertSame(1, $result->created);

        $row = Connection::preparedFetchOne(
            'SELECT WoTranslation FROM words WHERE WoLgID = ?',
            [self::$languageId]
        );
        $this->assertNotNull($row);
        // WordCrudService normalises an empty translation to the '*' placeholder.
        $this->assertContains((string) $row['WoTranslation'], ['', '*']);
    }
}
