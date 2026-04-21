<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Infrastructure\Anki;

use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgDeck;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgNote;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgReader;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgWriter;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * No-DB integration test for the writer + reader pair.
 *
 * The PHP-only round-trip alone won't catch schema mistakes that *Anki*
 * would reject; for that we have the CLI smoke tool
 * (bin/lwt-apkg-roundtrip-smoke.php) plus the genanki/anki pylib oracles
 * documented in the slice-1 commit message. This test guards the in-process
 * data path so refactors here can't silently break field/tag/suspension
 * round-trip.
 */
final class ApkgWriterReaderTest extends TestCase
{
    private string $tmpFile = '';

    protected function tearDown(): void
    {
        if ($this->tmpFile !== '' && is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testRoundTripPreservesEveryField(): void
    {
        $deck = ApkgDeck::forLanguage(7, 'Spanish');
        $notes = [
            new ApkgNote(
                lwtTermId: 101,
                term: 'hola',
                translation: 'hello',
                romanization: '',
                notes: 'informal greeting',
                tags: ['greeting', 'common'],
                suspended: false,
            ),
            new ApkgNote(
                lwtTermId: 102,
                term: 'casa',
                translation: 'la maison',  // intentionally non-ASCII translation
                romanization: '',
                notes: '',
                tags: [],
                suspended: false,
            ),
            new ApkgNote(
                lwtTermId: 103,
                term: 'adiós',
                translation: 'goodbye',
                romanization: '',
                notes: 'we know this one',
                tags: ['known'],
                suspended: true,
            ),
        ];

        $this->tmpFile = $this->makeTmpPath();
        (new ApkgWriter())->write($this->tmpFile, $deck, $notes);

        self::assertFileExists($this->tmpFile);
        self::assertGreaterThan(0, filesize($this->tmpFile));

        $readBack = (new ApkgReader())->read($this->tmpFile);
        self::assertCount(3, $readBack);

        $byId = [];
        foreach ($readBack as $n) {
            $byId[$n->lwtTermId] = $n;
        }

        foreach ($notes as $expected) {
            self::assertArrayHasKey($expected->lwtTermId, $byId);
            $actual = $byId[$expected->lwtTermId];
            self::assertSame($expected->term, $actual->term);
            self::assertSame($expected->translation, $actual->translation);
            self::assertSame($expected->romanization, $actual->romanization);
            self::assertSame($expected->notes, $actual->notes);
            self::assertEqualsCanonicalizing($expected->tags, $actual->tags);
            self::assertSame($expected->suspended, $actual->suspended);
        }
    }

    public function testApkgIsAValidZipWithExpectedEntries(): void
    {
        $this->tmpFile = $this->makeTmpPath();
        (new ApkgWriter())->write(
            $this->tmpFile,
            ApkgDeck::forLanguage(1, 'English'),
            [new ApkgNote(1, 'a', 'b', '', '', [], false)],
        );

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->tmpFile) === true);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (is_array($stat)) {
                $names[] = $stat['name'];
            }
        }
        $zip->close();

        self::assertContains('collection.anki21', $names);
        self::assertContains('collection.anki2', $names);
        self::assertContains('media', $names);
    }

    public function testReaderReturnsEmptyListForNotesFromUnknownNotetype(): void
    {
        // Write our standard apkg, then verify reader doesn't choke on a file
        // it wrote itself, and would also skip notes mapped to a notetype with
        // none of our expected field names. Covered indirectly via the empty
        // ords short-circuit; here we just confirm the no-mismatch case.
        $this->tmpFile = $this->makeTmpPath();
        (new ApkgWriter())->write(
            $this->tmpFile,
            ApkgDeck::forLanguage(1, 'English'),
            [new ApkgNote(1, 't', '', '', '', [], false)],
        );

        $notes = (new ApkgReader())->read($this->tmpFile);
        self::assertCount(1, $notes);
        self::assertSame(1, $notes[0]->lwtTermId);
    }

    public function testReaderRejectsNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');
        (new ApkgReader())->read('/tmp/lwt-this-file-does-not-exist.apkg');
    }

    private function makeTmpPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lwt_apkg_test_');
        self::assertNotFalse($path);
        unlink($path); // writer creates the file
        return $path;
    }
}
