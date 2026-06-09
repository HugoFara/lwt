<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\GdlClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for GdlClient against a captured GDL contentsearch response.
 *
 * The fixture below is trimmed from a real response to
 * content.digitallibrary.io/wp-json/content-api/v1/contentsearch?language=en&query=monster
 * (captured 2026-06; CC-BY/CC-BY-SA content). Field shapes — the `topic[]`
 * "Level N" term, the `thumbnail` that is `false` when absent, the per-book
 * `license[]`, and `meta.count` — are reproduced verbatim.
 */
class GdlClientTest extends TestCase
{
    private TestableGdlClient $client;

    protected function setUp(): void
    {
        $this->client = new TestableGdlClient();
    }

    /**
     * A captured contentsearch response: one levelled book with a cover, one
     * StoryWeaver book with no level and no thumbnail, and one entry that has
     * no ePUB (must be dropped).
     *
     * @return array<string, mixed>
     */
    private function sampleResponse(): array
    {
        return [
            'books' => [
                [
                    'postId' => 35879,
                    'title' => 'Where the River Monster Roams',
                    'description' => 'Somenea wants to see the creature in the river.',
                    'postLink' => 'https://content.digitallibrary.io/en/book/where-the-river-monster-roams/',
                    'h5pId' => '14165',
                    'epubUrl' => 'https://content.digitallibrary.io/wp-json/epub-generator/v1/book/14165',
                    'topic' => [
                        ['name' => 'Level 4', 'slug' => 'level-4', 'taxonomy' => 'topic'],
                        ['name' => 'Library Books', 'slug' => 'library-books', 'taxonomy' => 'topic'],
                    ],
                    'thumbnail' => 'https://content.digitallibrary.io/wp-content/uploads/2023/04/35879-cover.png',
                    'publisher' => '',
                    'language' => [['name' => 'English', 'slug' => 'en']],
                    'level' => [],
                    'license' => [['name' => 'CC-BY-4.0', 'slug' => 'cc-by-4-0']],
                ],
                [
                    'postId' => 45239,
                    'title' => 'I Love My Mom',
                    'description' => 'The girl in this story learns from her mother every day.',
                    'postLink' => 'https://content.digitallibrary.io/en/book/i-love-my-mom-3/',
                    'h5pId' => '17974',
                    'epubUrl' => 'https://content.digitallibrary.io/wp-json/epub-generator/v1/book/17974',
                    'topic' => [],
                    'thumbnail' => false,
                    'publisher' => 'StoryWeaver',
                    'language' => [['name' => 'English', 'slug' => 'en']],
                    'level' => [],
                    'license' => [['name' => 'CC-BY-4.0', 'slug' => 'cc-by-4-0']],
                ],
                [
                    'postId' => 99999,
                    'title' => 'Audio-only resource',
                    'description' => 'No ePUB available.',
                    'epubUrl' => '',
                    'topic' => [],
                    'thumbnail' => false,
                    'language' => [['name' => 'English', 'slug' => 'en']],
                    'license' => [['name' => 'CC-BY-4.0', 'slug' => 'cc-by-4-0']],
                ],
            ],
            'meta' => ['count' => 10, 'limit' => 20, 'skip' => null],
        ];
    }

    // ---------------------------------------------------------------
    // search() mapping
    // ---------------------------------------------------------------

    #[Test]
    public function searchMapsBookFields(): void
    {
        $this->client->setMockResponse($this->sampleResponse());
        $result = $this->client->search('monster', 'en');

        // The third entry has no ePUB and must be dropped.
        $this->assertCount(2, $result['results']);

        $book = $result['results'][0];
        $this->assertSame(35879, $book['id']);
        $this->assertSame('Where the River Monster Roams', $book['title']);
        $this->assertSame('Somenea wants to see the creature in the river.', $book['description']);
        $this->assertSame('en', $book['language']);
        $this->assertSame('CC-BY-4.0', $book['license']);
        $this->assertSame('Level 4', $book['level']);
        $this->assertSame(
            'https://content.digitallibrary.io/wp-json/epub-generator/v1/book/14165',
            $book['epubUrl']
        );
    }

    #[Test]
    public function searchSkipsBooksWithoutEpub(): void
    {
        $this->client->setMockResponse($this->sampleResponse());
        $result = $this->client->search('', 'en');

        $ids = array_column($result['results'], 'id');
        $this->assertNotContains(99999, $ids);
    }

    #[Test]
    public function searchDerivesDifficultyTierFromLevel(): void
    {
        $this->client->setMockResponse($this->sampleResponse());
        $result = $this->client->search('', 'en');

        // Level 4 -> hard; no level -> medium fallback.
        $this->assertSame('hard', $result['results'][0]['difficultyTier']);
        $this->assertSame('medium', $result['results'][1]['difficultyTier']);
    }

    #[Test]
    public function searchExposesPublisherAttribution(): void
    {
        $this->client->setMockResponse($this->sampleResponse());
        $result = $this->client->search('', 'en');

        $this->assertSame('StoryWeaver', $result['results'][1]['publisher']);
    }

    #[Test]
    public function searchHandlesFalseThumbnail(): void
    {
        $this->client->setMockResponse($this->sampleResponse());
        $result = $this->client->search('', 'en');

        $this->assertSame('', $result['results'][1]['thumbnail']);
        $this->assertStringStartsWith('https://', $result['results'][0]['thumbnail']);
    }

    #[Test]
    public function searchReportsCountFromMeta(): void
    {
        $this->client->setMockResponse($this->sampleResponse());
        $result = $this->client->search('', 'en');

        $this->assertSame(10, $result['count']);
    }

    #[Test]
    public function searchReturnsErrorWhenFetchFails(): void
    {
        $this->client->setMockResponse(null);
        $result = $this->client->search('monster', 'en');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('results', $result);
    }

    // ---------------------------------------------------------------
    // Pagination (`next` + `_skip`)
    // ---------------------------------------------------------------

    #[Test]
    public function nextIsTrueWhenMorePagesRemain(): void
    {
        // count=10 with a page size of 20 means everything fits on page 1.
        $this->client->setMockResponse($this->sampleResponse());
        $result = $this->client->search('', 'en', 1);
        $this->assertFalse($result['next']);
    }

    #[Test]
    public function nextIsTrueWhenCountExceedsPage(): void
    {
        $response = $this->sampleResponse();
        $response['meta']['count'] = 45; // 3 pages of 20
        $this->client->setMockResponse($response);

        $result = $this->client->search('', 'en', 1);
        $this->assertTrue($result['next']);

        $this->client->setMockResponse($response);
        $last = $this->client->search('', 'en', 3);
        $this->assertFalse($last['next']);
    }

    // ---------------------------------------------------------------
    // URL building
    // ---------------------------------------------------------------

    #[Test]
    public function searchBuildsUrlWithQueryParam(): void
    {
        $this->client->setMockResponse(['books' => [], 'meta' => ['count' => 0]]);
        $this->client->search('monster');

        $this->assertStringContainsString('contentsearch?', $this->client->lastFetchedUrl);
        $this->assertStringContainsString('query=monster', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchLowercasesLanguageCode(): void
    {
        $this->client->setMockResponse(['books' => [], 'meta' => ['count' => 0]]);
        $this->client->search('', 'EN');

        $this->assertStringContainsString('language=en', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchUsesSkipOffsetForPageBeyondFirst(): void
    {
        $this->client->setMockResponse(['books' => [], 'meta' => ['count' => 0]]);
        $this->client->search('test', null, 3);

        // page 3 with PAGE_SIZE 20 -> _skip=40
        $this->assertStringContainsString('_skip=40', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchOmitsSkipOnFirstPage(): void
    {
        $this->client->setMockResponse(['books' => [], 'meta' => ['count' => 0]]);
        $this->client->search('test', null, 1);

        $this->assertStringNotContainsString('_skip=', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchOmitsEmptyQueryAndLanguage(): void
    {
        $this->client->setMockResponse(['books' => [], 'meta' => ['count' => 0]]);
        $this->client->search('', '');

        $this->assertStringNotContainsString('query=', $this->client->lastFetchedUrl);
        $this->assertStringNotContainsString('language=', $this->client->lastFetchedUrl);
    }

    // ---------------------------------------------------------------
    // extractLevel / levelToTier
    // ---------------------------------------------------------------

    #[Test]
    public function extractLevelReadsLevelTopicTerm(): void
    {
        $book = ['topic' => [
            ['name' => 'Fiction'],
            ['name' => 'Level 2'],
        ]];
        $this->assertSame('Level 2', $this->client->testExtractLevel($book));
    }

    #[Test]
    public function extractLevelReturnsEmptyWhenNoLevelTopic(): void
    {
        $book = ['topic' => [['name' => 'Library Books']]];
        $this->assertSame('', $this->client->testExtractLevel($book));
    }

    #[Test]
    public function extractEpubUrlReturnsNullForEmpty(): void
    {
        $this->assertNull($this->client->testExtractEpubUrl(['epubUrl' => '']));
        $this->assertNull($this->client->testExtractEpubUrl([]));
    }

    #[Test]
    #[DataProvider('levelTierProvider')]
    public function levelToTierMapsBands(string $level, string $expected): void
    {
        $this->assertSame($expected, GdlClient::levelToTier($level));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function levelTierProvider(): iterable
    {
        yield 'level 1 is easy' => ['Level 1', 'easy'];
        yield 'level 2 is easy' => ['Level 2', 'easy'];
        yield 'level 3 is medium' => ['Level 3', 'medium'];
        yield 'level 4 is hard' => ['Level 4', 'hard'];
        yield 'level 5 is hard' => ['Level 5', 'hard'];
        yield 'missing level is medium' => ['', 'medium'];
        yield 'non-numeric is medium' => ['Read aloud', 'medium'];
    }
}
