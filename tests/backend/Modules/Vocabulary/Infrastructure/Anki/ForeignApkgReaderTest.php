<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Infrastructure\Anki;

use Lwt\Modules\Vocabulary\Infrastructure\Anki\ForeignApkgReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Reading decks that LWT did not create — the gap issue #228 describes.
 */
final class ForeignApkgReaderTest extends TestCase
{
    private string $tmpFile = '';

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite required to read an .apkg collection');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'lwt_foreign_test_');
        $this->tmpFile = $tmp !== false ? $tmp . '.apkg' : '';
    }

    protected function tearDown(): void
    {
        if ($this->tmpFile !== '') {
            ForeignDeckBuilder::cleanup($this->tmpFile);
        }
    }

    public function testReportsNotetypesWithTheirFieldNames(): void
    {
        (new ForeignDeckBuilder('Basic', ['Front', 'Back']))
            ->addMatureNote(['der Hund', 'the dog'])
            ->addMatureNote(['die Katze', 'the cat'])
            ->build($this->tmpFile);

        $notetypes = (new ForeignApkgReader())->notetypes($this->tmpFile);

        $this->assertCount(1, $notetypes);
        $this->assertSame('Basic', $notetypes[0]->name);
        $this->assertSame(['Front', 'Back'], $notetypes[0]->fields);
        $this->assertSame(2, $notetypes[0]->noteCount);
    }

    public function testHandlesArbitraryFieldNames(): void
    {
        // A real shared deck: nothing here is called Front/Back or Term.
        (new ForeignDeckBuilder('Japanese Core', ['Expression', 'Reading', 'Meaning']))
            ->addMatureNote(['日本語', 'にほんご', 'Japanese language'])
            ->build($this->tmpFile);

        $reader = new ForeignApkgReader();
        $notetypes = $reader->notetypes($this->tmpFile);

        $this->assertSame(['Expression', 'Reading', 'Meaning'], $notetypes[0]->fields);

        $notes = $reader->notes($this->tmpFile, $notetypes[0]->id);

        $this->assertCount(1, $notes);
        $this->assertSame('日本語', $notes[0]->field('Expression'));
        $this->assertSame('にほんご', $notes[0]->field('Reading'));
        $this->assertSame('Japanese language', $notes[0]->field('Meaning'));
    }

    public function testExposesSchedulingStatePerNote(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['mature', 'x'], 120)
            ->addYoungNote(['young', 'x'], 5)
            ->addNote(['brandnew', 'x'])
            ->addSuspendedNote(['parked', 'x'])
            ->build($this->tmpFile);

        $reader = new ForeignApkgReader();
        $notes = $reader->notes($this->tmpFile, $reader->notetypes($this->tmpFile)[0]->id);

        $byTerm = [];
        foreach ($notes as $note) {
            $byTerm[$note->field('Front')] = $note;
        }

        $this->assertSame(120, $byTerm['mature']->interval);
        $this->assertFalse($byTerm['mature']->isNew);

        $this->assertSame(5, $byTerm['young']->interval);

        $this->assertTrue($byTerm['brandnew']->isNew);
        $this->assertSame(0, $byTerm['brandnew']->interval);

        $this->assertTrue($byTerm['parked']->suspended);
    }

    public function testNegativeIntervalsAreTreatedAsUnlearned(): void
    {
        // Anki stores sub-day learning steps as negative seconds in `ivl`.
        (new ForeignDeckBuilder())
            ->addNote(['subday', 'x'], '', -600, 1, 1)
            ->build($this->tmpFile);

        $reader = new ForeignApkgReader();
        $notes = $reader->notes($this->tmpFile, $reader->notetypes($this->tmpFile)[0]->id);

        $this->assertSame(0, $notes[0]->interval, 'a negative ivl must not become a huge interval');
    }

    public function testReadsAnkiTags(): void
    {
        (new ForeignDeckBuilder())
            ->addMatureNote(['tagged', 'x'], 30, ' noun german ')
            ->build($this->tmpFile);

        $reader = new ForeignApkgReader();
        $notes = $reader->notes($this->tmpFile, $reader->notetypes($this->tmpFile)[0]->id);

        $this->assertSame(['noun', 'german'], $notes[0]->tags);
    }

    public function testUnknownNotetypeIsRejected(): void
    {
        (new ForeignDeckBuilder())->addMatureNote(['x', 'y'])->build($this->tmpFile);

        $this->expectException(RuntimeException::class);
        (new ForeignApkgReader())->notes($this->tmpFile, 99999);
    }

    public function testMissingFileIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        (new ForeignApkgReader())->notetypes('/nonexistent/deck.apkg');
    }
}
