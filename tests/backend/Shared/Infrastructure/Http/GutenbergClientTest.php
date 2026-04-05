<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\GutenbergClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GutenbergClientTest extends TestCase
{
    private TestableGutenbergClient $client;

    protected function setUp(): void
    {
        $this->client = new TestableGutenbergClient();
    }

    // ---------------------------------------------------------------
    // guessLanguageCode
    // ---------------------------------------------------------------

    #[Test]
    public function guessLanguageCodeDirectMatchLowercase(): void
    {
        $this->assertSame('en', GutenbergClient::guessLanguageCode('english'));
    }

    #[Test]
    public function guessLanguageCodeDirectMatchTitleCase(): void
    {
        $this->assertSame('fr', GutenbergClient::guessLanguageCode('French'));
    }

    #[Test]
    public function guessLanguageCodeCaseInsensitiveUppercase(): void
    {
        $this->assertSame('de', GutenbergClient::guessLanguageCode('GERMAN'));
    }

    #[Test]
    public function guessLanguageCodePartialMatch(): void
    {
        $this->assertSame('pt', GutenbergClient::guessLanguageCode('Brazilian Portuguese'));
    }

    #[Test]
    public function guessLanguageCodeUnknownReturnsNull(): void
    {
        $this->assertNull(GutenbergClient::guessLanguageCode('Klingon'));
    }

    #[Test]
    public function guessLanguageCodeEmptyStringReturnsNull(): void
    {
        $this->assertNull(GutenbergClient::guessLanguageCode(''));
    }

    #[Test]
    public function guessLanguageCodeTrimsWhitespace(): void
    {
        $this->assertSame('en', GutenbergClient::guessLanguageCode('  english  '));
    }

    #[Test]
    public function guessLanguageCodeMixedCasePartialMatch(): void
    {
        $this->assertSame('pt', GutenbergClient::guessLanguageCode('Old Portuguese'));
    }

    #[Test]
    #[DataProvider('allLanguagesProvider')]
    public function guessLanguageCodeMapsAllKnownLanguages(
        string $name,
        string $expectedCode
    ): void {
        $this->assertSame(
            $expectedCode,
            GutenbergClient::guessLanguageCode($name)
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function allLanguagesProvider(): iterable
    {
        $languages = [
            'english' => 'en', 'french' => 'fr', 'german' => 'de',
            'spanish' => 'es', 'italian' => 'it', 'portuguese' => 'pt',
            'dutch' => 'nl', 'finnish' => 'fi', 'swedish' => 'sv',
            'danish' => 'da', 'norwegian' => 'no', 'hungarian' => 'hu',
            'polish' => 'pl', 'czech' => 'cs', 'greek' => 'el',
            'russian' => 'ru', 'chinese' => 'zh', 'japanese' => 'ja',
            'korean' => 'ko', 'arabic' => 'ar', 'hebrew' => 'he',
            'turkish' => 'tr', 'romanian' => 'ro', 'catalan' => 'ca',
            'latin' => 'la', 'esperanto' => 'eo', 'tagalog' => 'tl',
        ];

        foreach ($languages as $name => $code) {
            yield $name => [$name, $code];
        }
    }

    #[Test]
    public function guessLanguageCodeNumericStringReturnsNull(): void
    {
        $this->assertNull(GutenbergClient::guessLanguageCode('12345'));
    }

    // ---------------------------------------------------------------
    // extractTextUrl
    // ---------------------------------------------------------------

    #[Test]
    public function extractTextUrlReturnsUtf8PlainText(): void
    {
        $book = [
            'formats' => [
                'text/html' => 'https://example.com/book.html',
                'text/plain; charset=utf-8' => 'https://example.com/book.txt',
                'text/plain; charset=us-ascii' => 'https://example.com/book-ascii.txt',
            ],
        ];

        $this->assertSame(
            'https://example.com/book.txt',
            $this->client->testExtractTextUrl($book)
        );
    }

    #[Test]
    public function extractTextUrlFallsBackToNonUtf8PlainText(): void
    {
        $book = [
            'formats' => [
                'text/html' => 'https://example.com/book.html',
                'text/plain; charset=us-ascii' => 'https://example.com/book-ascii.txt',
            ],
        ];

        $this->assertSame(
            'https://example.com/book-ascii.txt',
            $this->client->testExtractTextUrl($book)
        );
    }

    #[Test]
    public function extractTextUrlReturnsNullWhenNoPlainText(): void
    {
        $book = [
            'formats' => [
                'text/html' => 'https://example.com/book.html',
                'application/epub+zip' => 'https://example.com/book.epub',
            ],
        ];

        $this->assertNull($this->client->testExtractTextUrl($book));
    }

    #[Test]
    public function extractTextUrlPrefersUtf8OverAscii(): void
    {
        $book = [
            'formats' => [
                'text/plain; charset=us-ascii' => 'https://example.com/ascii.txt',
                'text/plain; charset=utf-8' => 'https://example.com/utf8.txt',
            ],
        ];

        $this->assertSame(
            'https://example.com/utf8.txt',
            $this->client->testExtractTextUrl($book)
        );
    }

    #[Test]
    public function extractTextUrlHandlesEmptyFormats(): void
    {
        $book = ['formats' => []];
        $this->assertNull($this->client->testExtractTextUrl($book));
    }

    #[Test]
    public function extractTextUrlHandlesMissingFormatsKey(): void
    {
        $book = ['title' => 'No Formats'];
        $this->assertNull($this->client->testExtractTextUrl($book));
    }

    // ---------------------------------------------------------------
    // search — URL construction
    // ---------------------------------------------------------------

    #[Test]
    public function searchBuildsUrlWithSearchQuery(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('hamlet');

        $this->assertStringContainsString('search=hamlet', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchBuildsUrlWithLanguageCode(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('', 'en');

        $this->assertStringContainsString('languages=en', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchLowercasesLanguageCode(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('', 'EN');

        $this->assertStringContainsString('languages=en', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchAddsPageParameterForPageGreaterThanOne(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('test', null, 3);

        $this->assertStringContainsString('page=3', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchDoesNotAddPageParameterForPageOne(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('test', null, 1);

        $this->assertStringNotContainsString('page=', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchOmitsEmptySearchParam(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('', 'fr');

        $this->assertStringNotContainsString('search=', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchOmitsNullLanguage(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('test', null);

        $this->assertStringNotContainsString('languages=', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function searchOmitsEmptyLanguage(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('test', '');

        $this->assertStringNotContainsString('languages=', $this->client->lastFetchedUrl);
    }

    // ---------------------------------------------------------------
    // search — error handling
    // ---------------------------------------------------------------

    #[Test]
    public function searchReturnsErrorWhenFetchJsonReturnsNull(): void
    {
        $this->client->setMockResponse(null);
        $result = $this->client->search('test');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Gutenberg', $result['error']);
    }

    // ---------------------------------------------------------------
    // search — result parsing
    // ---------------------------------------------------------------

    #[Test]
    public function searchParsesResultsCorrectly(): void
    {
        $this->client->setMockResponse([
            'count' => 1,
            'next' => 'https://gutendex.com/books?page=2',
            'results' => [
                [
                    'id' => 1342,
                    'title' => 'Pride and Prejudice',
                    'authors' => [
                        ['name' => 'Austen, Jane'],
                    ],
                    'languages' => ['en'],
                    'subjects' => ['Fiction', 'Romance', 'England', 'Extra Subject'],
                    'download_count' => 50000,
                    'formats' => [
                        'text/plain; charset=utf-8' => 'https://example.com/1342.txt',
                    ],
                ],
            ],
        ]);

        $result = $this->client->search('pride');

        $this->assertArrayHasKey('results', $result);
        $this->assertCount(1, $result['results']);

        $book = $result['results'][0];
        $this->assertSame(1342, $book['id']);
        $this->assertSame('Pride and Prejudice', $book['title']);
        $this->assertSame(['Austen, Jane'], $book['authors']);
        $this->assertSame(['en'], $book['languages']);
        $this->assertCount(3, $book['subjects']); // Capped at 3
        $this->assertSame(50000, $book['downloadCount']);
        $this->assertSame('https://example.com/1342.txt', $book['textUrl']);
    }

    #[Test]
    public function searchSkipsBooksWithoutPlainTextUrl(): void
    {
        $this->client->setMockResponse([
            'count' => 2,
            'next' => null,
            'results' => [
                [
                    'id' => 1,
                    'title' => 'Has Text',
                    'authors' => [],
                    'formats' => [
                        'text/plain; charset=utf-8' => 'https://example.com/1.txt',
                    ],
                ],
                [
                    'id' => 2,
                    'title' => 'No Text',
                    'authors' => [],
                    'formats' => [
                        'application/epub+zip' => 'https://example.com/2.epub',
                    ],
                ],
            ],
        ]);

        $result = $this->client->search('test');
        $this->assertCount(1, $result['results']);
        $this->assertSame('Has Text', $result['results'][0]['title']);
    }

    #[Test]
    public function searchReturnsPaginationInfo(): void
    {
        $this->client->setMockResponse([
            'count' => 100,
            'next' => 'https://gutendex.com/books?page=2',
            'results' => [],
        ]);

        $result = $this->client->search('test');
        $this->assertSame(100, $result['count']);
        $this->assertTrue($result['next']);
    }

    #[Test]
    public function searchReturnsFalseNextWhenNoMorePages(): void
    {
        $this->client->setMockResponse([
            'count' => 5,
            'next' => null,
            'results' => [],
        ]);

        $result = $this->client->search('test');
        $this->assertFalse($result['next']);
    }

    // ---------------------------------------------------------------
    // browse
    // ---------------------------------------------------------------

    #[Test]
    public function browseCallsSearchWithEmptyQuery(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->browse('en');

        $this->assertStringNotContainsString('search=', $this->client->lastFetchedUrl);
        $this->assertStringContainsString('languages=en', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function browsePassesLanguageCodeThrough(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->browse('fr');

        $this->assertStringContainsString('languages=fr', $this->client->lastFetchedUrl);
    }

    #[Test]
    public function browsePassesPageNumberThrough(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->browse('en', 5);

        $this->assertStringContainsString('page=5', $this->client->lastFetchedUrl);
    }

    // ---------------------------------------------------------------
    // Integration-style tests
    // ---------------------------------------------------------------

    #[Test]
    public function fullSearchParseFilterFlow(): void
    {
        $this->client->setMockResponse([
            'count' => 3,
            'next' => 'https://gutendex.com/books?page=2',
            'results' => [
                [
                    'id' => 10,
                    'title' => 'Book A',
                    'authors' => [['name' => 'Author One'], ['name' => 'Author Two']],
                    'languages' => ['en'],
                    'subjects' => ['Drama'],
                    'download_count' => 1000,
                    'formats' => [
                        'text/plain; charset=utf-8' => 'https://example.com/10.txt',
                        'text/html' => 'https://example.com/10.html',
                    ],
                ],
                [
                    'id' => 20,
                    'title' => 'Book B (epub only)',
                    'authors' => [],
                    'formats' => [
                        'application/epub+zip' => 'https://example.com/20.epub',
                    ],
                ],
                [
                    'id' => 30,
                    'title' => 'Book C',
                    'authors' => [['name' => 'Author Three']],
                    'languages' => ['fr'],
                    'subjects' => [],
                    'download_count' => 500,
                    'formats' => [
                        'text/plain; charset=us-ascii' => 'https://example.com/30.txt',
                    ],
                ],
            ],
        ]);

        $result = $this->client->search('books', 'en', 1);

        // Book B filtered out (no plain text)
        $this->assertCount(2, $result['results']);
        $this->assertSame(3, $result['count']);
        $this->assertTrue($result['next']);

        // Book A
        $this->assertSame(10, $result['results'][0]['id']);
        $this->assertSame(['Author One', 'Author Two'], $result['results'][0]['authors']);

        // Book C (ascii fallback)
        $this->assertSame(30, $result['results'][1]['id']);
        $this->assertSame('https://example.com/30.txt', $result['results'][1]['textUrl']);
    }

    #[Test]
    public function emptyResultsFromApi(): void
    {
        $this->client->setMockResponse([
            'count' => 0,
            'next' => null,
            'results' => [],
        ]);

        $result = $this->client->search('nonexistent_query_xyz');

        $this->assertSame(0, $result['count']);
        $this->assertFalse($result['next']);
        $this->assertSame([], $result['results']);
    }

    #[Test]
    public function missingResponseFieldsHandledGracefully(): void
    {
        $this->client->setMockResponse([
            'results' => [
                [
                    // Minimal: only formats with plain text
                    'formats' => [
                        'text/plain; charset=utf-8' => 'https://example.com/x.txt',
                    ],
                ],
            ],
        ]);

        $result = $this->client->search('test');

        $this->assertArrayHasKey('results', $result);
        $this->assertCount(1, $result['results']);

        $book = $result['results'][0];
        $this->assertSame(0, $book['id']);
        $this->assertSame('', $book['title']);
        $this->assertSame([], $book['authors']);
        $this->assertSame([], $book['languages']);
        $this->assertSame([], $book['subjects']);
        $this->assertSame(0, $book['downloadCount']);
        $this->assertSame('https://example.com/x.txt', $book['textUrl']);

        // Missing count and next
        $this->assertSame(0, $result['count']);
        $this->assertFalse($result['next']);
    }

    #[Test]
    public function searchUrlStartsWithApiBase(): void
    {
        $this->client->setMockResponse(['results' => [], 'count' => 0]);
        $this->client->search('test');

        $this->assertStringStartsWith(
            'https://gutendex.com/books?',
            $this->client->lastFetchedUrl
        );
    }

    #[Test]
    public function authorWithMissingNameDefaultsToEmptyString(): void
    {
        $this->client->setMockResponse([
            'count' => 1,
            'next' => null,
            'results' => [
                [
                    'id' => 99,
                    'title' => 'Anon',
                    'authors' => [['birth_year' => 1800]],
                    'formats' => [
                        'text/plain; charset=utf-8' => 'https://example.com/99.txt',
                    ],
                ],
            ],
        ]);

        $result = $this->client->search('anon');
        $this->assertSame([''], $result['results'][0]['authors']);
    }

    #[Test]
    public function subjectsCappedAtThree(): void
    {
        $this->client->setMockResponse([
            'count' => 1,
            'next' => null,
            'results' => [
                [
                    'id' => 5,
                    'title' => 'Many Subjects',
                    'subjects' => ['A', 'B', 'C', 'D', 'E'],
                    'formats' => [
                        'text/plain; charset=utf-8' => 'https://example.com/5.txt',
                    ],
                ],
            ],
        ]);

        $result = $this->client->search('test');
        $this->assertCount(3, $result['results'][0]['subjects']);
        $this->assertSame(['A', 'B', 'C'], $result['results'][0]['subjects']);
    }
}
