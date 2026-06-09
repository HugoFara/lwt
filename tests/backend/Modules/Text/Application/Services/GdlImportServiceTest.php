<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Text\Application\Services;

use Lwt\Modules\Text\Application\Services\GdlImportService;
use Lwt\Shared\Infrastructure\Http\GdlClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GdlImportService.
 *
 * The ePUB download and the Book-module parser are stubbed via seams so the
 * orchestration — download failure, parse failure, low-text rejection, and
 * the happy path — is exercised without the network or the zip extension.
 */
class GdlImportServiceTest extends TestCase
{
    /**
     * Build a service whose ePUB download returns $bytes and whose parser
     * returns $parsed (or throws when $parsed is an exception).
     *
     * @param string|null            $bytes  fetchEpub() result
     * @param array{title:string,text:string}|\Throwable $parsed parseEpub() result
     */
    private function makeService(?string $bytes, array|\Throwable $parsed): GdlImportService
    {
        $client = new class ($bytes) extends GdlClient {
            public function __construct(private ?string $bytes)
            {
            }

            public function fetchEpub(string $url): ?string
            {
                return $this->bytes;
            }
        };

        return new class ($client, $parsed) extends GdlImportService {
            /** @var array{title:string,text:string}|\Throwable */
            private $parsed;

            public function __construct(GdlClient $client, array|\Throwable $parsed)
            {
                parent::__construct($client);
                $this->parsed = $parsed;
            }

            protected function parseEpub(string $bytes): array
            {
                if ($this->parsed instanceof \Throwable) {
                    throw $this->parsed;
                }
                return $this->parsed;
            }
        };
    }

    #[Test]
    public function returnsErrorWhenDownloadFails(): void
    {
        $service = $this->makeService(null, ['title' => 'x', 'text' => 'y']);
        $result = $service->extractText('https://gdl/book.epub');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('download', strtolower($result['error']));
    }

    #[Test]
    public function returnsErrorWhenParseFails(): void
    {
        $service = $this->makeService('PK-bytes', new \RuntimeException('corrupt'));
        $result = $service->extractText('https://gdl/book.epub');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('EPUB', $result['error']);
    }

    #[Test]
    public function rejectsImageOnlyPictureBook(): void
    {
        // A handful of words — below the MIN_WORDS threshold.
        $service = $this->makeService('PK-bytes', [
            'title' => 'All Pictures',
            'text' => 'The end.',
        ]);
        $result = $service->extractText('https://gdl/book.epub');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('too little readable text', $result['error']);
        $this->assertStringContainsString('2 words', $result['error']);
    }

    #[Test]
    public function importsBookWithSufficientText(): void
    {
        $text = str_repeat('word ', 40);
        $service = $this->makeService('PK-bytes', [
            'title' => 'A Real Story',
            'text' => trim($text),
        ]);
        $result = $service->extractText('https://gdl/book/14165');

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame('A Real Story', $result['title']);
        $this->assertSame(trim($text), $result['text']);
        $this->assertSame('https://gdl/book/14165', $result['sourceUri']);
    }

    #[Test]
    public function buildTextConcatenatesChaptersAndSkipsBlanks(): void
    {
        $service = new class () extends GdlImportService {
            /** @param array<array{content?: string}> $chapters */
            public function exposeBuildText(array $chapters): string
            {
                return $this->buildText($chapters);
            }
        };

        $text = $service->exposeBuildText([
            ['num' => 1, 'content' => 'Chapter one.'],
            ['num' => 2, 'content' => '   '],
            ['num' => 3, 'content' => 'Chapter three.'],
        ]);

        $this->assertSame("Chapter one.\n\nChapter three.", $text);
    }
}
