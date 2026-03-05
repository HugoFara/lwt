<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\VocabularyApiRouter;
use Lwt\Modules\Vocabulary\Http\TermCrudApiHandler;
use Lwt\Modules\Vocabulary\Http\WordFamilyApiHandler;
use Lwt\Modules\Vocabulary\Http\MultiWordApiHandler;
use Lwt\Modules\Vocabulary\Http\WordListApiHandler;
use Lwt\Modules\Vocabulary\Http\TermTranslationApiHandler;
use Lwt\Modules\Vocabulary\Http\TermStatusApiHandler;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Shared\Http\ApiRoutableInterface;
use Lwt\Shared\Infrastructure\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for VocabularyApiRouter.
 *
 * Tests route dispatch logic, constructor structure, and edge cases
 * without requiring a database connection.
 */
class VocabularyApiRouterTest extends TestCase
{
    /** @var TermCrudApiHandler&MockObject */
    private TermCrudApiHandler $termHandler;

    /** @var WordFamilyApiHandler&MockObject */
    private WordFamilyApiHandler $wordFamilyHandler;

    /** @var MultiWordApiHandler&MockObject */
    private MultiWordApiHandler $multiWordHandler;

    /** @var WordListApiHandler&MockObject */
    private WordListApiHandler $wordListHandler;

    /** @var TermTranslationApiHandler&MockObject */
    private TermTranslationApiHandler $termTranslationHandler;

    /** @var TermStatusApiHandler&MockObject */
    private TermStatusApiHandler $termStatusHandler;

    /** @var TextFacade&MockObject */
    private TextFacade $textFacade;

    private VocabularyApiRouter $router;

    protected function setUp(): void
    {
        $this->termHandler = $this->createMock(TermCrudApiHandler::class);
        $this->wordFamilyHandler = $this->createMock(WordFamilyApiHandler::class);
        $this->multiWordHandler = $this->createMock(MultiWordApiHandler::class);
        $this->wordListHandler = $this->createMock(WordListApiHandler::class);
        $this->termTranslationHandler = $this->createMock(TermTranslationApiHandler::class);
        $this->termStatusHandler = $this->createMock(TermStatusApiHandler::class);
        $this->textFacade = $this->createMock(TextFacade::class);

        $this->router = new VocabularyApiRouter(
            $this->termHandler,
            $this->wordFamilyHandler,
            $this->multiWordHandler,
            $this->wordListHandler,
            $this->termTranslationHandler,
            $this->termStatusHandler,
            $this->textFacade
        );
    }

    // =========================================================================
    // Constructor / class structure tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidInstance(): void
    {
        $this->assertInstanceOf(VocabularyApiRouter::class, $this->router);
    }

    #[Test]
    public function implementsApiRoutableInterface(): void
    {
        $this->assertInstanceOf(ApiRoutableInterface::class, $this->router);
    }

    #[Test]
    public function constructorAcceptsSevenDependencies(): void
    {
        $ref = new ReflectionClass(VocabularyApiRouter::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(7, $constructor->getParameters());
    }

    #[Test]
    public function classHasExpectedPublicRouteMethods(): void
    {
        $ref = new ReflectionClass(VocabularyApiRouter::class);
        $methods = ['routeGet', 'routePost', 'routePut', 'routeDelete'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $ref->hasMethod($method),
                "Missing public method: $method"
            );
            $this->assertTrue(
                $ref->getMethod($method)->isPublic(),
                "$method should be public"
            );
        }
    }

    #[Test]
    public function routeTermStatusPostIsPrivate(): void
    {
        $ref = new ReflectionClass(VocabularyApiRouter::class);
        $this->assertTrue($ref->hasMethod('routeTermStatusPost'));
        $this->assertTrue($ref->getMethod('routeTermStatusPost')->isPrivate());
    }

    // =========================================================================
    // routeGet dispatch tests
    // =========================================================================

    #[Test]
    public function routeGetListDelegatesToWordListHandler(): void
    {
        $this->wordListHandler->expects($this->once())
            ->method('getWordList')
            ->with(['page' => '1'])
            ->willReturn(['words' => []]);

        $response = $this->router->routeGet(
            ['terms', 'list'],
            ['page' => '1']
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['words' => []], $response->getData());
    }

    #[Test]
    public function routeGetFilterOptionsDelegatesToWordListHandler(): void
    {
        $this->wordListHandler->expects($this->once())
            ->method('getFilterOptions')
            ->with(5)
            ->willReturn(['tags' => []]);

        $response = $this->router->routeGet(
            ['terms', 'filter-options'],
            ['language_id' => '5']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetFilterOptionsPassesNullWhenLanguageIdEmpty(): void
    {
        $this->wordListHandler->expects($this->once())
            ->method('getFilterOptions')
            ->with(null)
            ->willReturn([]);

        $this->router->routeGet(
            ['terms', 'filter-options'],
            ['language_id' => '']
        );
    }

    #[Test]
    public function routeGetFilterOptionsPassesNullWhenLanguageIdMissing(): void
    {
        $this->wordListHandler->expects($this->once())
            ->method('getFilterOptions')
            ->with(null)
            ->willReturn([]);

        $this->router->routeGet(
            ['terms', 'filter-options'],
            []
        );
    }

    #[Test]
    public function routeGetNumericIdDelegatesToTermHandler(): void
    {
        $this->termHandler->expects($this->once())
            ->method('formatGetTerm')
            ->with(42)
            ->willReturn(['id' => 42]);

        $response = $this->router->routeGet(
            ['terms', '42'],
            []
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetNumericIdWithDetailsDelegatesToTermHandler(): void
    {
        $this->termHandler->expects($this->once())
            ->method('formatGetTermDetails')
            ->with(10, 'ann_value')
            ->willReturn(['id' => 10]);

        $response = $this->router->routeGet(
            ['terms', '10', 'details'],
            ['ann' => 'ann_value']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetNumericIdWithTranslationsDelegatesToTextFacade(): void
    {
        $this->textFacade->expects($this->once())
            ->method('getTermTranslations')
            ->with('hello', 5)
            ->willReturn(['translations' => []]);

        $response = $this->router->routeGet(
            ['terms', '7', 'translations'],
            ['term_lc' => 'hello', 'text_id' => '5']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetNumericIdWithFamilyDelegatesToWordFamilyHandler(): void
    {
        $this->wordFamilyHandler->expects($this->once())
            ->method('getTermFamily')
            ->with(15)
            ->willReturn(['family' => []]);

        $response = $this->router->routeGet(
            ['terms', '15', 'family'],
            []
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetNumericIdWithUnknownSubPathReturns404(): void
    {
        $response = $this->router->routeGet(
            ['terms', '10', 'unknown'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
        $data = $response->getData();
        $this->assertSame('Expected "translations", "details", "family", or no sub-path', $data['error']);
    }

    #[Test]
    public function routeGetUnknownEndpointReturns404(): void
    {
        $response = $this->router->routeGet(
            ['terms', 'nonexistent'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
        $data = $response->getData();
        $this->assertStringContainsString('nonexistent', $data['error']);
    }

    #[Test]
    public function routeGetFamilyWithoutTermIdReturnsError(): void
    {
        $response = $this->router->routeGet(
            ['terms', 'family'],
            []
        );

        $this->assertSame(400, $response->getStatusCode());
        $data = $response->getData();
        $this->assertSame('term_id is required', $data['error']);
    }

    #[Test]
    public function routeGetFamilySuggestionDelegatesToWordFamilyHandler(): void
    {
        $this->wordFamilyHandler->expects($this->once())
            ->method('getFamilyUpdateSuggestion')
            ->with(3, 2)
            ->willReturn(['suggestion' => true]);

        $response = $this->router->routeGet(
            ['terms', 'family', 'suggestion'],
            ['term_id' => '3', 'status' => '2']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetImportedDelegatesToWordListHandler(): void
    {
        $this->wordListHandler->expects($this->once())
            ->method('importedTermsList')
            ->with('2026-01-01', 1, 10)
            ->willReturn(['items' => []]);

        $response = $this->router->routeGet(
            ['terms', 'imported'],
            ['last_update' => '2026-01-01', 'page' => '1', 'count' => '10']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    // =========================================================================
    // routePost dispatch tests
    // =========================================================================

    #[Test]
    public function routePostNewDelegatesToTermTranslationHandler(): void
    {
        $this->termTranslationHandler->expects($this->once())
            ->method('formatAddTranslation')
            ->with('hello', 1, 'hola')
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', 'new'],
            ['term_text' => 'hello', 'language_id' => '1', 'translation' => 'hola']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostQuickDelegatesToTermHandler(): void
    {
        $this->termHandler->expects($this->once())
            ->method('formatQuickCreate')
            ->with(5, 3, 98)
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', 'quick'],
            ['text_id' => '5', 'position' => '3', 'status' => '98']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostFullDelegatesToTermHandler(): void
    {
        $params = ['langId' => '1', 'text' => 'hello'];
        $this->termHandler->expects($this->once())
            ->method('formatCreateTermFull')
            ->with($params)
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', 'full'],
            $params
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostMultiDelegatesToMultiWordHandler(): void
    {
        $params = ['text' => 'hello world'];
        $this->multiWordHandler->expects($this->once())
            ->method('createMultiWordTerm')
            ->with($params)
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', 'multi'],
            $params
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostNumericIdWithStatusUpDelegatesToTermStatusHandler(): void
    {
        $this->termStatusHandler->expects($this->once())
            ->method('formatIncrementStatus')
            ->with(42, true)
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', '42', 'status', 'up'],
            []
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostNumericIdWithStatusDownDelegatesToTermStatusHandler(): void
    {
        $this->termStatusHandler->expects($this->once())
            ->method('formatIncrementStatus')
            ->with(42, false)
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', '42', 'status', 'down'],
            []
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostNumericIdWithStatusNumericDelegatesToTermStatusHandler(): void
    {
        $this->termStatusHandler->expects($this->once())
            ->method('formatSetStatus')
            ->with(42, 3)
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', '42', 'status', '3'],
            []
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostNumericIdWithStatusUnknownReturns404(): void
    {
        $response = $this->router->routePost(
            ['terms', '42', 'status', 'bogus'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
        $data = $response->getData();
        $this->assertStringContainsString('bogus', $data['error']);
    }

    #[Test]
    public function routePostNumericIdWithTranslationsDelegatesToTermTranslationHandler(): void
    {
        $this->termTranslationHandler->expects($this->once())
            ->method('formatUpdateTranslation')
            ->with(7, 'nueva')
            ->willReturn(['success' => true]);

        $response = $this->router->routePost(
            ['terms', '7', 'translations'],
            ['translation' => 'nueva']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePostNumericIdWithUnknownSubPathReturns404(): void
    {
        $response = $this->router->routePost(
            ['terms', '7', 'unknown'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function routePostUnknownEndpointReturns404(): void
    {
        $response = $this->router->routePost(
            ['terms', 'nonexistent'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    // =========================================================================
    // routePut dispatch tests
    // =========================================================================

    #[Test]
    public function routePutBulkStatusDelegatesToTermStatusHandler(): void
    {
        $this->termStatusHandler->expects($this->once())
            ->method('formatBulkStatus')
            ->with([1, 2, 3], 5)
            ->willReturn(['updated' => 3]);

        $response = $this->router->routePut(
            ['terms', 'bulk-status'],
            ['term_ids' => [1, 2, 3], 'status' => '5']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutBulkStatusDefaultsToEmptyArrayWhenTermIdsNotArray(): void
    {
        $this->termStatusHandler->expects($this->once())
            ->method('formatBulkStatus')
            ->with([], 0)
            ->willReturn(['updated' => 0]);

        $response = $this->router->routePut(
            ['terms', 'bulk-status'],
            ['term_ids' => 'not-an-array']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutBulkActionDelegatesToWordListHandler(): void
    {
        $this->wordListHandler->expects($this->once())
            ->method('bulkAction')
            ->with([1, 2], 'delete', null)
            ->willReturn(['done' => true]);

        $response = $this->router->routePut(
            ['terms', 'bulk-action'],
            ['ids' => [1, 2], 'action' => 'delete']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutNumericIdInlineEditDelegatesToWordListHandler(): void
    {
        $this->wordListHandler->expects($this->once())
            ->method('inlineEdit')
            ->with(10, 'translation', 'hola')
            ->willReturn(['success' => true]);

        $response = $this->router->routePut(
            ['terms', '10', 'inline-edit'],
            ['field' => 'translation', 'value' => 'hola']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutMultiNumericIdDelegatesToMultiWordHandler(): void
    {
        $params = ['text' => 'updated'];
        $this->multiWordHandler->expects($this->once())
            ->method('updateMultiWordTerm')
            ->with(55, $params)
            ->willReturn(['success' => true]);

        $response = $this->router->routePut(
            ['terms', 'multi', '55'],
            $params
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutNumericIdWithTranslationDelegatesToTermTranslationHandler(): void
    {
        $this->termTranslationHandler->expects($this->once())
            ->method('formatUpdateTranslation')
            ->with(8, 'nuevo')
            ->willReturn(['success' => true]);

        $response = $this->router->routePut(
            ['terms', '8', 'translation'],
            ['translation' => 'nuevo']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutNumericIdWithNoSubPathDelegatesToTermHandler(): void
    {
        $params = ['translation' => 'hola'];
        $this->termHandler->expects($this->once())
            ->method('formatUpdateTermFull')
            ->with(8, $params)
            ->willReturn(['success' => true]);

        $response = $this->router->routePut(
            ['terms', '8'],
            $params
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutNumericIdWithUnknownSubPathReturns404(): void
    {
        $response = $this->router->routePut(
            ['terms', '8', 'unknown'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function routePutFamilyStatusDelegatesToWordFamilyHandler(): void
    {
        $this->wordFamilyHandler->expects($this->once())
            ->method('updateWordFamilyStatus')
            ->with(1, 'test', 3)
            ->willReturn(['updated' => true]);

        $response = $this->router->routePut(
            ['terms', 'family', 'status'],
            ['language_id' => '1', 'lemma_lc' => 'test', 'status' => '3']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutFamilyStatusReturns400WhenLanguageIdMissing(): void
    {
        $response = $this->router->routePut(
            ['terms', 'family', 'status'],
            ['lemma_lc' => 'test', 'status' => '3']
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function routePutFamilyStatusReturns400WhenLemmaLcEmpty(): void
    {
        $response = $this->router->routePut(
            ['terms', 'family', 'status'],
            ['language_id' => '1', 'lemma_lc' => '', 'status' => '3']
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function routePutFamilyApplyDelegatesToWordFamilyHandler(): void
    {
        $this->wordFamilyHandler->expects($this->once())
            ->method('applyFamilyUpdate')
            ->with([1, 2], 5)
            ->willReturn(['applied' => true]);

        $response = $this->router->routePut(
            ['terms', 'family', 'apply'],
            ['term_ids' => [1, 2], 'status' => '5']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routePutFamilyApplyReturns400WhenTermIdsEmpty(): void
    {
        $response = $this->router->routePut(
            ['terms', 'family', 'apply'],
            ['term_ids' => [], 'status' => '5']
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function routePutFamilyUnknownSubPathReturns404(): void
    {
        $response = $this->router->routePut(
            ['terms', 'family', 'unknown'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function routePutUnknownEndpointReturns404(): void
    {
        $response = $this->router->routePut(
            ['terms', 'nonexistent'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    // =========================================================================
    // routeDelete dispatch tests
    // =========================================================================

    #[Test]
    public function routeDeleteWithNumericIdDelegatesToTermHandler(): void
    {
        $this->termHandler->expects($this->once())
            ->method('formatDeleteTerm')
            ->with(42)
            ->willReturn(['deleted' => true]);

        $response = $this->router->routeDelete(
            ['terms', '42'],
            []
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeDeleteWithoutIdReturns404(): void
    {
        $response = $this->router->routeDelete(
            ['terms', ''],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
        $data = $response->getData();
        $this->assertSame('Term ID (Integer) Expected', $data['error']);
    }

    #[Test]
    public function routeDeleteWithNonNumericIdReturns404(): void
    {
        $response = $this->router->routeDelete(
            ['terms', 'abc'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function routeDeleteWithMissingFragmentReturns404(): void
    {
        $response = $this->router->routeDelete(
            ['terms'],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    #[Test]
    public function routeGetForEditDelegatesToTermHandler(): void
    {
        $this->termHandler->expects($this->once())
            ->method('formatGetTermForEdit')
            ->with(10, 5, null)
            ->willReturn(['term' => []]);

        $response = $this->router->routeGet(
            ['terms', 'for-edit'],
            ['term_id' => '10', 'ord' => '5']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetForEditPassesWidWhenPresent(): void
    {
        $this->termHandler->expects($this->once())
            ->method('formatGetTermForEdit')
            ->with(10, 5, 99)
            ->willReturn(['term' => []]);

        $this->router->routeGet(
            ['terms', 'for-edit'],
            ['term_id' => '10', 'ord' => '5', 'wid' => '99']
        );
    }

    #[Test]
    public function routeGetForEditPassesNullWidWhenEmpty(): void
    {
        $this->termHandler->expects($this->once())
            ->method('formatGetTermForEdit')
            ->with(0, 0, null)
            ->willReturn([]);

        $this->router->routeGet(
            ['terms', 'for-edit'],
            ['wid' => '']
        );
    }

    #[Test]
    public function routeGetMultiDelegatesToMultiWordHandler(): void
    {
        $this->multiWordHandler->expects($this->once())
            ->method('getMultiWordForEdit')
            ->with(10, 5, 'hello', 99)
            ->willReturn(['term' => []]);

        $response = $this->router->routeGet(
            ['terms', 'multi'],
            ['term_id' => '10', 'ord' => '5', 'txt' => 'hello', 'wid' => '99']
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function routeGetEmptyFragmentReturns404(): void
    {
        $response = $this->router->routeGet(
            ['terms', ''],
            []
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function routeMethodsReturnJsonResponse(): void
    {
        $ref = new ReflectionClass(VocabularyApiRouter::class);

        foreach (['routeGet', 'routePost', 'routePut', 'routeDelete'] as $method) {
            $returnType = $ref->getMethod($method)->getReturnType();
            $this->assertNotNull($returnType, "$method should have a return type");
            $this->assertSame(
                JsonResponse::class,
                $returnType->getName(),
                "$method should return JsonResponse"
            );
        }
    }

    #[Test]
    public function routePutAllActionDelegatesToWordListHandler(): void
    {
        $filters = ['status' => '1'];
        $this->wordListHandler->expects($this->once())
            ->method('allAction')
            ->with($filters, 'delete', 'extra')
            ->willReturn(['done' => true]);

        $response = $this->router->routePut(
            ['terms', 'all-action'],
            ['filters' => $filters, 'action' => 'delete', 'data' => 'extra']
        );

        $this->assertSame(200, $response->getStatusCode());
    }
}
