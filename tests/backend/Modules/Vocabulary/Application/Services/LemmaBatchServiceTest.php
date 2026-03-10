<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Application\Services;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Application\Services\LemmaBatchService;
use Lwt\Modules\Vocabulary\Domain\LemmatizerInterface;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;

/**
 * Unit tests for LemmaBatchService.
 *
 * Tests lemma suggestion, batch processing, and propagation.
 */
class LemmaBatchServiceTest extends TestCase
{
    private LemmaBatchService $service;
    private LemmatizerInterface $mockLemmatizer;
    private MySqlTermRepository $mockRepository;

    protected function setUp(): void
    {
        $this->mockLemmatizer = $this->createMock(LemmatizerInterface::class);
        $this->mockRepository = $this->createMock(MySqlTermRepository::class);
        $this->service = new LemmaBatchService($this->mockLemmatizer, $this->mockRepository);
    }

    // =========================================================================
    // suggestLemma Tests
    // =========================================================================

    public function testSuggestLemmaReturnsLemma(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatize')
            ->with('running', 'en')
            ->willReturn('run');

        $result = $this->service->suggestLemma('running', 'en');

        $this->assertSame('run', $result);
    }

    public function testSuggestLemmaReturnsNullForEmptyWord(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatize');

        $result = $this->service->suggestLemma('', 'en');

        $this->assertNull($result);
    }

    public function testSuggestLemmaReturnsNullForEmptyLanguage(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatize');

        $result = $this->service->suggestLemma('running', '');

        $this->assertNull($result);
    }

    public function testSuggestLemmaReturnsNullWhenNotFound(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatize')
            ->with('xyz', 'en')
            ->willReturn(null);

        $result = $this->service->suggestLemma('xyz', 'en');

        $this->assertNull($result);
    }

    public function testSuggestLemmaWithUnicodeWord(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatize')
            ->with('laufend', 'de')
            ->willReturn('laufen');

        $result = $this->service->suggestLemma('laufend', 'de');

        $this->assertSame('laufen', $result);
    }

    // =========================================================================
    // suggestLemmasBatch Tests
    // =========================================================================

    public function testSuggestLemmasBatchReturnsMapping(): void
    {
        $words = ['running', 'walks'];
        $expected = ['running' => 'run', 'walks' => 'walk'];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatizeBatch')
            ->with($words, 'en')
            ->willReturn($expected);

        $result = $this->service->suggestLemmasBatch($words, 'en');

        $this->assertSame($expected, $result);
    }

    public function testSuggestLemmasBatchReturnsEmptyForEmptyArray(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatizeBatch');

        $result = $this->service->suggestLemmasBatch([], 'en');

        $this->assertSame([], $result);
    }

    public function testSuggestLemmasBatchReturnsEmptyForEmptyLanguage(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatizeBatch');

        $result = $this->service->suggestLemmasBatch(['running'], '');

        $this->assertSame([], $result);
    }

    // =========================================================================
    // setLemma Tests
    // =========================================================================

    public function testSetLemmaSuccess(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(42, 'run')
            ->willReturn(true);

        $result = $this->service->setLemma(42, 'run');

        $this->assertTrue($result);
    }

    public function testSetLemmaFailure(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(42, 'run')
            ->willReturn(false);

        $result = $this->service->setLemma(42, 'run');

        $this->assertFalse($result);
    }

    public function testSetLemmaWithEmptyString(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(1, '')
            ->willReturn(true);

        $result = $this->service->setLemma(1, '');

        $this->assertTrue($result);
    }

    public function testSetLemmaWithUnicode(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(1, '走る')
            ->willReturn(true);

        $result = $this->service->setLemma(1, '走る');

        $this->assertTrue($result);
    }

    // =========================================================================
    // linkTextItemsByLemma Tests
    // =========================================================================

    public function testLinkTextItemsByLemmaUnsupportedLanguageReturnsZeros(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('unsupported')
            ->willReturn(false);

        $result = $this->service->linkTextItemsByLemma(1, 'unsupported');

        $this->assertSame(['linked' => 0, 'unmatched' => 0, 'errors' => 0], $result);
    }

    public function testLinkTextItemsByLemmaReturnStructureHasRequiredKeys(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('xx')
            ->willReturn(false);

        $result = $this->service->linkTextItemsByLemma(1, 'xx');

        $this->assertArrayHasKey('linked', $result);
        $this->assertArrayHasKey('unmatched', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testLinkTextItemsByLemmaWithTextIdUnsupported(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('zz')
            ->willReturn(false);

        $result = $this->service->linkTextItemsByLemma(1, 'zz', 100);

        $this->assertSame(['linked' => 0, 'unmatched' => 0, 'errors' => 0], $result);
    }

    // =========================================================================
    // applyLemmasToVocabulary Tests
    // =========================================================================

    public function testApplyLemmasToVocabularyUnsupportedLanguageReturnsZeros(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('unsupported')
            ->willReturn(false);

        $result = $this->service->applyLemmasToVocabulary(1, 'unsupported');

        $this->assertSame(['processed' => 0, 'updated' => 0, 'skipped' => 0], $result);
    }

    public function testApplyLemmasToVocabularyReturnStructure(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('xyz')
            ->willReturn(false);

        $result = $this->service->applyLemmasToVocabulary(1, 'xyz', 50);

        $this->assertArrayHasKey('processed', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('skipped', $result);
    }

    // =========================================================================
    // propagateLemma Tests
    // =========================================================================

    public function testPropagateLemmaTermNotFoundReturnsZero(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->propagateLemma(999, 1, 'en');

        $this->assertSame(0, $result);
    }

    public function testPropagateLemmaTermWithNoLemmaReturnsZero(): void
    {
        $mockTerm = $this->createMock(Term::class);
        $mockTerm->method('lemma')->willReturn(null);
        $mockTerm->method('lemmaLc')->willReturn(null);

        $this->mockRepository
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($mockTerm);

        $result = $this->service->propagateLemma(10, 1, 'en');

        $this->assertSame(0, $result);
    }

    public function testPropagateLemmaTermWithEmptyLemmaReturnsZero(): void
    {
        $mockTerm = $this->createMock(Term::class);
        $mockTerm->method('lemma')->willReturn(null);
        $mockTerm->method('lemmaLc')->willReturn('');

        $this->mockRepository
            ->expects($this->once())
            ->method('find')
            ->with(20)
            ->willReturn($mockTerm);

        $result = $this->service->propagateLemma(20, 1, 'en');

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // findWordIdByLemma Tests
    // =========================================================================

    public function testFindWordIdByLemmaNonExistentReturnsNull(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->findWordIdByLemma(999999, 'nonexistent_lemma');

        $this->assertNull($result);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorAcceptsDependencies(): void
    {
        $lemmatizer = $this->createMock(LemmatizerInterface::class);
        $repository = $this->createMock(MySqlTermRepository::class);
        $service = new LemmaBatchService($lemmatizer, $repository);

        $this->assertInstanceOf(LemmaBatchService::class, $service);
    }
}
