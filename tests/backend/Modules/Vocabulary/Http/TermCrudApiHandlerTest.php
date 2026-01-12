<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\TermCrudApiHandler;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Application\Services\WordContextService;
use Lwt\Modules\Vocabulary\Application\Services\WordDiscoveryService;
use Lwt\Modules\Vocabulary\Application\Services\WordLinkingService;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use Lwt\Modules\Language\Domain\ValueObject\LanguageId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TermCrudApiHandler.
 *
 * Tests term CRUD API operations including get, create, update, and delete.
 */
class TermCrudApiHandlerTest extends TestCase
{
    /** @var VocabularyFacade&MockObject */
    private VocabularyFacade $facade;

    /** @var FindSimilarTerms&MockObject */
    private FindSimilarTerms $findSimilarTerms;

    /** @var WordContextService&MockObject */
    private WordContextService $contextService;

    /** @var WordDiscoveryService&MockObject */
    private WordDiscoveryService $discoveryService;

    /** @var WordLinkingService&MockObject */
    private WordLinkingService $linkingService;

    private TermCrudApiHandler $handler;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(VocabularyFacade::class);
        $this->findSimilarTerms = $this->createMock(FindSimilarTerms::class);
        $this->contextService = $this->createMock(WordContextService::class);
        $this->discoveryService = $this->createMock(WordDiscoveryService::class);
        $this->linkingService = $this->createMock(WordLinkingService::class);

        $this->handler = new TermCrudApiHandler(
            $this->facade,
            $this->findSimilarTerms,
            $this->contextService,
            $this->discoveryService,
            $this->linkingService
        );
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(TermCrudApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameters(): void
    {
        $handler = new TermCrudApiHandler(null, null, null, null, null);
        $this->assertInstanceOf(TermCrudApiHandler::class, $handler);
    }

    // =========================================================================
    // getTerm tests
    // =========================================================================

    public function testGetTermReturnsErrorWhenNotFound(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->getTerm(999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Term not found', $result['error']);
    }

    public function testGetTermReturnsTermData(): void
    {
        $term = $this->createMockTerm(123, 'hello', 'hola', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);

        $result = $this->handler->getTerm(123);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame(123, $result['id']);
        $this->assertSame('hello', $result['text']);
        $this->assertSame('hola', $result['translation']);
        $this->assertSame(1, $result['status']);
    }

    public function testGetTermIncludesAllFields(): void
    {
        $term = $this->createMockTerm(1, 'test', 'prueba', 3);
        $this->facade->method('getTerm')
            ->willReturn($term);

        $result = $this->handler->getTerm(1);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('textLc', $result);
        $this->assertArrayHasKey('lemma', $result);
        $this->assertArrayHasKey('lemmaLc', $result);
        $this->assertArrayHasKey('translation', $result);
        $this->assertArrayHasKey('romanization', $result);
        $this->assertArrayHasKey('sentence', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('statusLabel', $result);
        $this->assertArrayHasKey('langId', $result);
        $this->assertArrayHasKey('wordCount', $result);
    }

    // =========================================================================
    // createTerm tests
    // =========================================================================

    public function testCreateTermReturnsErrorWhenLanguageIdMissing(): void
    {
        $result = $this->handler->createTerm([
            'text' => 'hello'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language ID and text are required', $result['error']);
    }

    public function testCreateTermReturnsErrorWhenLanguageIdZero(): void
    {
        $result = $this->handler->createTerm([
            'langId' => 0,
            'text' => 'hello'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language ID and text are required', $result['error']);
    }

    public function testCreateTermReturnsErrorWhenTextEmpty(): void
    {
        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => ''
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language ID and text are required', $result['error']);
    }

    public function testCreateTermReturnsErrorWhenTextOnlyWhitespace(): void
    {
        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => '   '
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language ID and text are required', $result['error']);
    }

    public function testCreateTermReturnsErrorForInvalidStatus(): void
    {
        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello',
            'status' => 100
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    public function testCreateTermReturnsErrorForNegativeStatus(): void
    {
        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello',
            'status' => -1
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    public function testCreateTermReturnsSuccessWithValidData(): void
    {
        $term = $this->createMockTerm(123, 'hello', 'hola', 1);
        $this->facade->expects($this->once())
            ->method('createTerm')
            ->with(1, 'hello', 1, '*', '', '')
            ->willReturn($term);

        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello',
            'status' => 1
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(123, $result['id']);
        $this->assertArrayHasKey('textLc', $result);
        $this->assertArrayHasKey('hex', $result);
    }

    public function testCreateTermUsesDefaultTranslation(): void
    {
        $term = $this->createMockTerm(1, 'hello', '*', 1);
        $this->facade->expects($this->once())
            ->method('createTerm')
            ->with(1, 'hello', 1, '*', '', '')
            ->willReturn($term);

        $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello',
            'translation' => ''
        ]);
    }

    public function testCreateTermPassesOptionalFields(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'hola', 2);
        $this->facade->expects($this->once())
            ->method('createTerm')
            ->with(1, 'hello', 2, 'hola', 'elo', 'Hello world')
            ->willReturn($term);

        $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello',
            'status' => 2,
            'translation' => 'hola',
            'romanization' => 'elo',
            'sentence' => 'Hello world'
        ]);
    }

    public function testCreateTermTrimsWhitespace(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'hola', 1);
        $this->facade->expects($this->once())
            ->method('createTerm')
            ->with(1, 'hello', 1, 'hola', 'elo', 'test')
            ->willReturn($term);

        $this->handler->createTerm([
            'langId' => 1,
            'text' => '  hello  ',
            'translation' => '  hola  ',
            'romanization' => '  elo  ',
            'sentence' => '  test  '
        ]);
    }

    public function testCreateTermHandlesException(): void
    {
        $this->facade->method('createTerm')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Database error', $result['error']);
    }

    // =========================================================================
    // updateTerm tests
    // =========================================================================

    public function testUpdateTermReturnsErrorWhenNotFound(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->updateTerm(999, ['translation' => 'new']);

        $this->assertFalse($result['success']);
        $this->assertSame('Term not found', $result['error']);
    }

    public function testUpdateTermCallsFacadeWithValidData(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'old', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->expects($this->once())
            ->method('updateTerm');

        $result = $this->handler->updateTerm(1, ['translation' => 'new']);

        $this->assertTrue($result['success']);
    }

    public function testUpdateTermUsesDefaultTranslationForEmpty(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'old', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->expects($this->once())
            ->method('updateTerm')
            ->with(
                1,
                null,
                '*',
                $this->anything(),
                $this->anything()
            );

        $this->handler->updateTerm(1, ['translation' => '']);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a mock Term object.
     *
     * @param int    $id          Term ID
     * @param string $text        Term text
     * @param string $translation Translation
     * @param int    $status      Status
     *
     * @return Term&MockObject
     */
    private function createMockTerm(int $id, string $text, string $translation, int $status): Term
    {
        // Use real value objects since they are final readonly
        $termId = TermId::fromInt($id);
        $termStatus = TermStatus::fromInt($status);
        $languageId = LanguageId::fromInt(1);

        $term = $this->createMock(Term::class);
        $term->method('id')->willReturn($termId);
        $term->method('text')->willReturn($text);
        $term->method('textLowercase')->willReturn(strtolower($text));
        $term->method('lemma')->willReturn('');
        $term->method('lemmaLc')->willReturn('');
        $term->method('translation')->willReturn($translation);
        $term->method('romanization')->willReturn('');
        $term->method('sentence')->willReturn('');
        $term->method('status')->willReturn($termStatus);
        $term->method('languageId')->willReturn($languageId);
        $term->method('wordCount')->willReturn(1);

        return $term;
    }
}
