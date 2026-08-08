<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\Http;

use Lwt\Modules\Tags\Http\TagCrudApiHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Round-trips the tag CRUD the list page runs on.
 *
 * These hit the real repository rather than a mock: the interesting behaviour
 * lives in the facade and the tag-type split, which a mocked facade would
 * assert away.
 */
#[CoversClass(TagCrudApiHandler::class)]
class TagCrudApiHandlerTest extends TestCase
{
    private TagCrudApiHandler $handler;

    /** @var list<array{0: string, 1: int}> */
    private array $created = [];

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->handler = new TagCrudApiHandler();
    }

    protected function tearDown(): void
    {
        foreach ($this->created as [$type, $id]) {
            $this->handler->delete($type, $id);
        }
        $this->created = [];
    }

    /**
     * Create a tag and register it for cleanup.
     *
     * @param string $type Tag type
     * @param string $text Tag text
     *
     * @return int The new tag's ID
     */
    private function makeTag(string $type, string $text): int
    {
        $result = $this->handler->create($type, ['text' => $text, 'comment' => 'from test']);
        $this->assertTrue($result['success'], 'tag should be creatable: ' . ($result['error'] ?? ''));
        $id = $result['id'];
        $this->created[] = [$type, $id];
        return $id;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function tagTypes(): array
    {
        return ['term tags' => ['term'], 'text tags' => ['text']];
    }

    #[Test]
    #[DataProvider('tagTypes')]
    public function aTagCanBeCreatedReadUpdatedAndDeleted(string $type): void
    {
        $text = 'cy' . substr(uniqid(), -8);
        $id = $this->makeTag($type, $text);

        $read = $this->handler->get($type, $id);
        $this->assertArrayHasKey('tag', $read);
        $this->assertSame($text, $read['tag']['text']);

        $updated = $this->handler->update($type, $id, ['text' => $text . 'x', 'comment' => 'edited']);
        $this->assertTrue($updated['success']);

        $reread = $this->handler->get($type, $id);
        $this->assertSame($text . 'x', $reread['tag']['text']);
        $this->assertSame('edited', $reread['tag']['comment']);

        $deleted = $this->handler->delete($type, $id);
        $this->assertTrue($deleted['success']);
        $this->assertSame(1, $deleted['deleted']);
        $this->created = [];

        $this->assertArrayHasKey('error', $this->handler->get($type, $id));
    }

    #[Test]
    #[DataProvider('tagTypes')]
    public function listingReturnsThePagingEnvelopeAndTheTagsUrls(string $type): void
    {
        $text = 'cy' . substr(uniqid(), -8);
        $this->makeTag($type, $text);

        $result = $this->handler->list($type, ['query' => $text]);

        $this->assertSame($type, $result['type']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('sortOptions', $result);
        $this->assertNotEmpty($result['tags'], 'the tag just created should match its own name');

        $row = $result['tags'][0];
        // The links are built from patterns the tag type owns; rebuilding them
        // client-side from a guessed shape is what this avoids.
        $this->assertArrayHasKey('itemsUrl', $row);
        $this->assertNotSame('', $row['itemsUrl']);
        $this->assertSame(0, $row['usageCount'], 'a fresh tag is on nothing');
    }

    #[Test]
    public function termAndTextTagsAreSeparateCollections(): void
    {
        $text = 'cy' . substr(uniqid(), -8);
        $this->makeTag('term', $text);

        $inTerm = $this->handler->list('term', ['query' => $text]);
        $inText = $this->handler->list('text', ['query' => $text]);

        $this->assertNotEmpty($inTerm['tags']);
        $this->assertEmpty($inText['tags'], 'a term tag must not surface among text tags');
    }

    #[Test]
    public function creatingWithoutTextIsRejected(): void
    {
        $result = $this->handler->create('term', ['text' => '   ']);

        $this->assertFalse($result['success']);
        $this->assertSame('Tag text is required', $result['error']);
    }

    #[Test]
    public function updatingAMissingTagIsRejected(): void
    {
        $result = $this->handler->update('term', 999999, ['text' => 'whatever']);

        $this->assertFalse($result['success']);
        $this->assertSame('Tag not found', $result['error']);
    }

    #[Test]
    public function deletingAMissingTagIsRejected(): void
    {
        $result = $this->handler->delete('term', 999999);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['deleted']);
    }

    #[Test]
    public function anUnknownTagTypeIsRejectedEverywhere(): void
    {
        $this->assertArrayHasKey('error', $this->handler->list('nonsense', []));
        $this->assertArrayHasKey('error', $this->handler->get('nonsense', 1));
        $this->assertFalse($this->handler->create('nonsense', ['text' => 'x'])['success']);
        $this->assertFalse($this->handler->update('nonsense', 1, ['text' => 'x'])['success']);
        $this->assertFalse($this->handler->delete('nonsense', 1)['success']);
        $this->assertFalse($this->handler->deleteMany('nonsense', ['ids' => [1]])['success']);
    }

    #[Test]
    public function deletingSelectedTagsRemovesExactlyThose(): void
    {
        $keep = 'cy' . substr(uniqid(), -8);
        $dropA = 'cy' . substr(uniqid(), -8);
        $dropB = 'cy' . substr(uniqid(), -8);

        $keepId = $this->makeTag('term', $keep);
        $idA = $this->makeTag('term', $dropA);
        $idB = $this->makeTag('term', $dropB);

        $result = $this->handler->deleteMany('term', ['ids' => [$idA, $idB]]);
        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['deleted']);

        $this->assertArrayHasKey('error', $this->handler->get('term', $idA));
        $this->assertArrayHasKey('error', $this->handler->get('term', $idB));
        $this->assertArrayHasKey('tag', $this->handler->get('term', $keepId));

        $this->created = [['term', $keepId]];
    }

    #[Test]
    public function deleteAllHonoursTheFilterRatherThanWipingEverything(): void
    {
        // "Delete all" on a filtered list must not delete tags the filter
        // excluded — the page only ever shows one page of matches.
        $prefix = 'cyall' . substr(uniqid(), -6);
        $this->makeTag('term', $prefix . 'one');
        $this->makeTag('term', $prefix . 'two');
        $survivor = 'cykeep' . substr(uniqid(), -6);
        $survivorId = $this->makeTag('term', $survivor);

        // The filter's wildcard is `*`, mapped to SQL `%` by buildWhereClause.
        $result = $this->handler->deleteMany('term', ['all' => true, 'query' => $prefix . '*']);
        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['deleted']);

        $this->assertArrayHasKey(
            'tag',
            $this->handler->get('term', $survivorId),
            'a tag outside the filter must survive "delete all"'
        );

        $this->created = [['term', $survivorId]];
    }

    #[Test]
    public function deletingWithNoIdsIsRejected(): void
    {
        $result = $this->handler->deleteMany('term', ['ids' => []]);

        $this->assertFalse($result['success']);
        $this->assertSame('No tags selected', $result['error']);
    }
}
