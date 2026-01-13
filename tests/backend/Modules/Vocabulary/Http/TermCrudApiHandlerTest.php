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
    // updateTerm additional tests
    // =========================================================================

    public function testUpdateTermTrimsWhitespace(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'old', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->expects($this->once())
            ->method('updateTerm')
            ->with(
                1,
                null,
                'new translation',
                $this->anything(),
                $this->anything()
            );

        $this->handler->updateTerm(1, ['translation' => '  new translation  ']);
    }

    public function testUpdateTermHandlesException(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'old', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->method('updateTerm')
            ->willThrowException(new \Exception('Update failed'));

        $result = $this->handler->updateTerm(1, ['translation' => 'new']);

        $this->assertFalse($result['success']);
        $this->assertSame('Update failed', $result['error']);
    }

    public function testUpdateTermIgnoresInvalidStatus(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'old', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->expects($this->once())
            ->method('updateTerm')
            ->with(
                1,
                null, // Invalid status should be converted to null
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->handler->updateTerm(1, ['status' => 999]);
    }

    public function testUpdateTermPassesAllFields(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'old', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->expects($this->once())
            ->method('updateTerm');

        $result = $this->handler->updateTerm(1, [
            'translation' => 'nueva',
            'romanization' => 'elo',
            'sentence' => 'Hello world',
            'status' => 3
        ]);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // deleteTerm tests
    // =========================================================================

    public function testDeleteTermReturnsErrorWhenNotFound(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->deleteTerm(999);

        $this->assertFalse($result['deleted']);
        $this->assertSame('Term not found', $result['error']);
    }

    public function testDeleteTermCallsFacade(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'hola', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->expects($this->once())
            ->method('deleteTerm')
            ->with(1)
            ->willReturn(true);

        $result = $this->handler->deleteTerm(1);

        $this->assertTrue($result['deleted']);
    }

    public function testDeleteTermReturnsFalseOnFailure(): void
    {
        $term = $this->createMockTerm(1, 'hello', 'hola', 1);
        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->method('deleteTerm')
            ->willReturn(false);

        $result = $this->handler->deleteTerm(1);

        $this->assertFalse($result['deleted']);
    }

    // =========================================================================
    // deleteTerms tests
    // =========================================================================

    public function testDeleteTermsReturnsErrorWhenEmpty(): void
    {
        $result = $this->handler->deleteTerms([]);

        $this->assertSame(0, $result['deleted']);
        $this->assertSame('No term IDs provided', $result['error']);
    }

    public function testDeleteTermsCallsFacadeWithIds(): void
    {
        $this->facade->expects($this->once())
            ->method('deleteTerms')
            ->with([1, 2, 3])
            ->willReturn(3);

        $result = $this->handler->deleteTerms([1, 2, 3]);

        $this->assertSame(3, $result['deleted']);
    }

    public function testDeleteTermsReturnsPartialCount(): void
    {
        $this->facade->method('deleteTerms')
            ->willReturn(2);

        $result = $this->handler->deleteTerms([1, 2, 3]);

        $this->assertSame(2, $result['deleted']);
    }

    // =========================================================================
    // formatGetTerm tests
    // =========================================================================

    public function testFormatGetTermDelegatesToGetTerm(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->formatGetTerm(999);

        $this->assertArrayHasKey('error', $result);
    }

    // =========================================================================
    // formatCreateTerm tests
    // =========================================================================

    public function testFormatCreateTermDelegatesToCreateTerm(): void
    {
        $result = $this->handler->formatCreateTerm([
            'langId' => 0,
            'text' => 'hello'
        ]);

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // formatUpdateTerm tests
    // =========================================================================

    public function testFormatUpdateTermDelegatesToUpdateTerm(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->formatUpdateTerm(999, []);

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // formatDeleteTerm tests
    // =========================================================================

    public function testFormatDeleteTermDelegatesToDeleteTerm(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->formatDeleteTerm(999);

        $this->assertFalse($result['deleted']);
    }

    // =========================================================================
    // createQuickTerm tests
    // =========================================================================

    public function testCreateQuickTermReturnsErrorForInvalidStatus(): void
    {
        $result = $this->handler->createQuickTerm(1, 5, 1);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Status must be 98 (ignored) or 99 (well-known)', $result['error']);
    }

    public function testCreateQuickTermReturnsErrorWhenWordNotFound(): void
    {
        $this->linkingService->method('getWordAtPosition')
            ->willReturn(null);

        $result = $this->handler->createQuickTerm(1, 5, 98);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Word not found at position', $result['error']);
    }

    public function testCreateQuickTermReturnsSuccessData(): void
    {
        $this->linkingService->method('getWordAtPosition')
            ->willReturn('hello');
        $this->discoveryService->method('insertWordWithStatus')
            ->willReturn([
                'id' => 123,
                'term' => 'hello',
                'termlc' => 'hello',
                'hex' => 'hex123'
            ]);

        $result = $this->handler->createQuickTerm(1, 5, 99);

        $this->assertSame(123, $result['term_id']);
        $this->assertSame('hello', $result['term']);
        $this->assertSame('hello', $result['term_lc']);
        $this->assertSame('hex123', $result['hex']);
    }

    public function testCreateQuickTermHandlesException(): void
    {
        $this->linkingService->method('getWordAtPosition')
            ->willReturn('hello');
        $this->discoveryService->method('insertWordWithStatus')
            ->willThrowException(new \RuntimeException('Insert failed'));

        $result = $this->handler->createQuickTerm(1, 5, 98);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Insert failed', $result['error']);
    }

    // =========================================================================
    // formatQuickCreate tests
    // =========================================================================

    public function testFormatQuickCreateDelegatesToCreateQuickTerm(): void
    {
        $result = $this->handler->formatQuickCreate(1, 5, 1);

        $this->assertArrayHasKey('error', $result);
    }

    // =========================================================================
    // createTerm valid status tests
    // =========================================================================

    public function testCreateTermAcceptsStatus98(): void
    {
        $term = $this->createMockTerm(1, 'hello', '*', 98);
        $this->facade->method('createTerm')
            ->willReturn($term);

        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello',
            'status' => 98
        ]);

        $this->assertTrue($result['success']);
    }

    public function testCreateTermAcceptsStatus99(): void
    {
        $term = $this->createMockTerm(1, 'hello', '*', 99);
        $this->facade->method('createTerm')
            ->willReturn($term);

        $result = $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello',
            'status' => 99
        ]);

        $this->assertTrue($result['success']);
    }

    public function testCreateTermDefaultsStatus(): void
    {
        $term = $this->createMockTerm(1, 'hello', '*', 1);
        $this->facade->expects($this->once())
            ->method('createTerm')
            ->with(1, 'hello', 1, '*', '', '')
            ->willReturn($term);

        $this->handler->createTerm([
            'langId' => 1,
            'text' => 'hello'
        ]);
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
