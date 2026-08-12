<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Http;

use Lwt\Modules\Language\Http\LanguageApiHandler;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Guards the pairing between what PUT /languages/{id} accepts and what
 * GET /languages/{id} returns.
 *
 * The language edit form reads its values from GET and posts them back to
 * PUT. That makes any field the writer accepts but the reader omits actively
 * destructive rather than merely incomplete: the client cannot send back a
 * value it was never given, so `normalizeLanguageData()` substitutes its
 * default and the setting is wiped on the first save.
 *
 * That was the state of `parserType`, `sourceLang`, `targetLang`,
 * `dict1PopUp`, `dict2PopUp`, `translatorPopUp` and `localDictMode` — all
 * writable, none readable. Saving a language through the API would have
 * quietly reset its parser to the default and cleared its dictionary popup
 * settings.
 *
 * A superset assertion would not have caught it either way round, so this
 * round-trips real values through the real endpoints.
 */
class LanguageFieldRoundTripTest extends TestCase
{
    private LanguageApiHandler $handler;

    /** @var list<int> */
    private array $createdIds = [];

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->handler = new LanguageApiHandler(null);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdIds as $id) {
            QueryBuilder::table('languages')->where('LgID', '=', $id)->delete();
        }
        $this->createdIds = [];
    }

    /**
     * Values chosen to differ from every default `normalizeLanguageData()`
     * would substitute, so a dropped field shows up as a changed value rather
     * than coincidentally matching.
     *
     * @return array<string, mixed>
     */
    private function distinctiveFields(): array
    {
        return [
            'dict1Uri' => 'https://example.test/d1?w=###',
            'dict2Uri' => 'https://example.test/d2?w=###',
            'translatorUri' => 'https://example.test/tr?w=###',
            'exportTemplate' => '$w\t$t\n',
            'textSize' => 175,
            'characterSubstitutions' => 'x=y|z=w',
            'regexpSplitSentences' => '.!?;',
            'exceptionsSplitSentences' => 'Mr.|Dr.',
            'regexpWordCharacters' => 'a-zA-ZÀ-ÿ',
            'removeSpaces' => true,
            'splitEachChar' => true,
            'rightToLeft' => true,
            'ttsVoiceApi' => 'https://example.test/tts',
            'showRomanization' => true,
            'parserType' => 'mecab',
            'sourceLang' => 'ja',
            'targetLang' => 'fr',
            'dict1PopUp' => true,
            'dict2PopUp' => true,
            'translatorPopUp' => true,
            'localDictMode' => 3,
        ];
    }

    #[Test]
    public function everyWritableFieldSurvivesAWriteThenRead(): void
    {
        $name = 'RoundTrip ' . uniqid();
        $created = $this->handler->formatCreate(['name' => $name]);
        $this->assertTrue($created['success'], 'language should be creatable');
        $id = $created['id'];
        $this->createdIds[] = $id;

        $payload = $this->distinctiveFields();
        $payload['name'] = $name;

        $updated = $this->handler->formatUpdate($id, $payload);
        $this->assertTrue($updated['success'], 'language should be updatable');

        $read = $this->handler->formatGetOne($id);
        $this->assertNotNull($read);
        $language = $read['language'];

        foreach ($this->distinctiveFields() as $field => $expected) {
            $this->assertArrayHasKey(
                $field,
                $language,
                "GET /languages/{id} omits '$field', which PUT accepts. A client "
                . 'round-tripping the language cannot send back a value it was '
                . 'never given, so saving resets this field.'
            );
            $this->assertSame(
                $expected,
                $language[$field],
                "Field '$field' did not survive a write followed by a read."
            );
        }
    }

    #[Test]
    public function asecondSaveOfTheReadPayloadChangesNothing(): void
    {
        // The form's real failure mode: load, save without touching anything.
        // Anything the reader drops silently reverts on this second write.
        $name = 'Idempotent ' . uniqid();
        $created = $this->handler->formatCreate(['name' => $name]);
        $id = $created['id'];
        $this->createdIds[] = $id;

        $payload = $this->distinctiveFields();
        $payload['name'] = $name;
        $this->handler->formatUpdate($id, $payload);

        $first = $this->handler->formatGetOne($id);
        $this->assertNotNull($first);

        // Send exactly what the reader handed back, as the form would.
        $this->handler->formatUpdate($id, $first['language']);

        $second = $this->handler->formatGetOne($id);
        $this->assertNotNull($second);

        $this->assertSame(
            $first['language'],
            $second['language'],
            'Re-saving the payload GET returned changed the language. A field '
            . 'the reader omits is reset here, which is what an untouched '
            . '"Save" click in the edit form would do.'
        );
    }
}
