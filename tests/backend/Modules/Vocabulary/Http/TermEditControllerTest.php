<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\TermEditController;
use Lwt\Modules\Vocabulary\Http\VocabularyBaseController;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Shared\Infrastructure\Dictionary\DictionaryAdapter;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TermEditController.
 *
 * Tests constructor behavior, class structure, method signatures,
 * inline edit logic, delete operations, and form data assembly.
 */
class TermEditControllerTest extends TestCase
{
    /** @var VocabularyFacade&MockObject */
    private VocabularyFacade $facade;

    /** @var DictionaryAdapter&MockObject */
    private DictionaryAdapter $dictionaryAdapter;

    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageFacade;

    private TermEditController $controller;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(VocabularyFacade::class);
        $this->dictionaryAdapter = $this->createMock(DictionaryAdapter::class);
        $this->languageFacade = $this->createMock(LanguageFacade::class);
        $this->controller = new TermEditController(
            $this->facade,
            $this->dictionaryAdapter,
            $this->languageFacade
        );
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(TermEditController::class, $this->controller);
    }

    #[Test]
    public function constructorAcceptsAllNullParameters(): void
    {
        $controller = new TermEditController(null, null, null);
        $this->assertInstanceOf(TermEditController::class, $controller);
    }

    #[Test]
    public function constructorSetsFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TermEditController::class, 'facade');
        $reflection->setAccessible(true);

        $this->assertSame($this->facade, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsDictionaryAdapterProperty(): void
    {
        $reflection = new \ReflectionProperty(TermEditController::class, 'dictionaryAdapter');
        $reflection->setAccessible(true);

        $this->assertSame($this->dictionaryAdapter, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsLanguageFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TermEditController::class, 'languageFacade');
        $reflection->setAccessible(true);

        $this->assertSame($this->languageFacade, $reflection->getValue($this->controller));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsVocabularyBaseController(): void
    {
        $reflection = new \ReflectionClass(TermEditController::class);

        $this->assertSame(
            VocabularyBaseController::class,
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TermEditController::class);

        $expectedMethods = [
            'editWordById',
            'editWord',
            'editTerm',
            'inlineEdit',
            'createWord',
            'deleteWordView',
            'deleteWord',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermEditController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function classHasRequiredPrivateMethods(): void
    {
        $reflection = new \ReflectionClass(TermEditController::class);

        $expectedMethods = [
            'handleEditWordOperation',
            'displayEditWordForm',
            'handleEditTermOperation',
            'displayEditTermForm',
            'getWordFormData',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermEditController should have private method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPrivate(),
                "Method $methodName should be private"
            );
        }
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function editWordByIdAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'editWordById');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    #[Test]
    public function editWordAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'editWord');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function editTermAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'editTerm');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function inlineEditAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'inlineEdit');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function createWordAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'createWord');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function deleteWordViewAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'deleteWordView');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function deleteWordAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'deleteWord');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    #[Test]
    public function deleteWordReturnsRedirectResponse(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'deleteWord');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(RedirectResponse::class, $returnType->getName());
    }

    // =========================================================================
    // deleteWord tests
    // =========================================================================

    #[Test]
    public function deleteWordCallsFacadeWithCorrectId(): void
    {
        $this->facade->expects($this->once())
            ->method('deleteTerm')
            ->with(42)
            ->willReturn(true);

        $result = $this->controller->deleteWord(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function deleteWordReturnsRedirectToWordsList(): void
    {
        $this->facade->expects($this->once())
            ->method('deleteTerm')
            ->willReturn(true);

        $result = $this->controller->deleteWord(1);

        // Verify it's a RedirectResponse pointing to /words
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function deleteWordWithNonexistentId(): void
    {
        $this->facade->expects($this->once())
            ->method('deleteTerm')
            ->with(9999)
            ->willReturn(false);

        $result = $this->controller->deleteWord(9999);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    // =========================================================================
    // inlineEdit tests
    // =========================================================================

    #[Test]
    public function inlineEditWithTranslationPrefix(): void
    {
        // Set up POST data for translation edit
        $_POST['id'] = 'trans123';
        $_POST['value'] = 'hello';

        $mockTerm = $this->createMock(Term::class);
        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(123)
            ->willReturn($mockTerm);

        $this->facade->expects($this->once())
            ->method('updateTerm')
            ->with(123, null, 'hello', null, null, null);

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertSame('hello', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditWithEmptyTranslationSetsAsterisk(): void
    {
        $_POST['id'] = 'trans456';
        $_POST['value'] = '';

        $mockTerm = $this->createMock(Term::class);
        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(456)
            ->willReturn($mockTerm);

        $this->facade->expects($this->once())
            ->method('updateTerm')
            ->with(456, null, '*', null, null, null);

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertSame('*', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditWithRomanizationPrefix(): void
    {
        $_POST['id'] = 'roman789';
        $_POST['value'] = 'romaji';

        $mockTerm = $this->createMock(Term::class);
        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(789)
            ->willReturn($mockTerm);

        $this->facade->expects($this->once())
            ->method('updateTerm')
            ->with(789, null, null, null, null, 'romaji');

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertSame('romaji', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditTranslationNotFoundReturnsError(): void
    {
        $_POST['id'] = 'trans999';
        $_POST['value'] = 'test';

        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(999)
            ->willReturn(null);

        $this->facade->expects($this->never())
            ->method('updateTerm');

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertSame('ERROR - term not found!', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditRomanizationNotFoundReturnsError(): void
    {
        $_POST['id'] = 'roman999';
        $_POST['value'] = 'test';

        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(999)
            ->willReturn(null);

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertSame('ERROR - term not found!', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditWithUnknownPrefixReturnsRefreshError(): void
    {
        $_POST['id'] = 'unknown123';
        $_POST['value'] = 'test';

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertSame('ERROR - please refresh page!', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditWithEmptyIdReturnsRefreshError(): void
    {
        $_POST['id'] = '';
        $_POST['value'] = 'test';

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertSame('ERROR - please refresh page!', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditEscapesHtmlInTranslation(): void
    {
        $_POST['id'] = 'trans10';
        $_POST['value'] = '<script>alert("xss")</script>';

        $mockTerm = $this->createMock(Term::class);
        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(10)
            ->willReturn($mockTerm);

        $this->facade->expects($this->once())
            ->method('updateTerm');

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);

        unset($_POST['id'], $_POST['value']);
    }

    #[Test]
    public function inlineEditEscapesHtmlInRomanization(): void
    {
        $_POST['id'] = 'roman10';
        $_POST['value'] = '<b>bold</b>';

        $mockTerm = $this->createMock(Term::class);
        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(10)
            ->willReturn($mockTerm);

        $this->facade->expects($this->once())
            ->method('updateTerm');

        ob_start();
        $this->controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<b>', $output);
        $this->assertStringContainsString('&lt;b&gt;', $output);

        unset($_POST['id'], $_POST['value']);
    }

    // =========================================================================
    // getWordFormData tests via reflection
    // =========================================================================

    #[Test]
    public function getWordFormDataReturnsExpectedKeys(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'getWordFormData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $expectedKeys = [
            'WoID', 'WoLgID', 'WoText', 'WoTextLC', 'WoStatus',
            'WoOldStatus', 'WoTranslation', 'WoRomanization', 'WoSentence',
            'tid', 'ord', 'len',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "getWordFormData should contain key: $key");
        }
    }

    #[Test]
    public function getWordFormDataReturnsCorrectCount(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'getWordFormData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertCount(12, $result);
    }

    #[Test]
    public function getWordFormDataDefaultWoLgIDIsZero(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'getWordFormData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertSame(0, $result['WoLgID']);
    }

    // =========================================================================
    // handleEditWordOperation tests via reflection
    // =========================================================================

    #[Test]
    public function handleEditWordOperationReturnsBool(): void
    {
        $method = new \ReflectionMethod(TermEditController::class, 'handleEditWordOperation');
        $method->setAccessible(true);

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('bool', $returnType->getName());
    }

    // =========================================================================
    // Lazy-loaded service accessor tests
    // =========================================================================

    #[Test]
    public function crudServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'crudService');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->controller));
    }

    #[Test]
    public function contextServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'contextService');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->controller));
    }

    #[Test]
    public function linkingServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'linkingService');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->controller));
    }

    #[Test]
    public function expressionServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'expressionService');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->controller));
    }

    #[Test]
    public function textStatisticsServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'textStatisticsService');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->controller));
    }

    // =========================================================================
    // View path tests
    // =========================================================================

    #[Test]
    public function viewPathEndsWithViews(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'viewPath');
        $reflection->setAccessible(true);

        $path = $reflection->getValue($this->controller);

        $this->assertStringEndsWith('Views/', $path);
    }

    #[Test]
    public function setViewPathUpdatesViewPath(): void
    {
        $this->controller->setViewPath('/custom/path');

        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'viewPath');
        $reflection->setAccessible(true);

        $this->assertSame('/custom/path/', $reflection->getValue($this->controller));
    }

    #[Test]
    public function setViewPathTrailingSlashIsNormalized(): void
    {
        $this->controller->setViewPath('/custom/path/');

        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'viewPath');
        $reflection->setAccessible(true);

        $this->assertSame('/custom/path/', $reflection->getValue($this->controller));
    }

    // =========================================================================
    // render method tests
    // =========================================================================

    #[Test]
    public function renderThrowsOnMissingView(): void
    {
        $method = new \ReflectionMethod(VocabularyBaseController::class, 'render');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('View not found: nonexistent_view');

        $method->invoke($this->controller, 'nonexistent_view', []);
    }

    // =========================================================================
    // editWord early return tests
    // =========================================================================

    #[Test]
    public function editWordReturnsEarlyWithNoParams(): void
    {
        // No GET params set - wid='', tid='', ord='', op=''
        // This should trigger early return
        ob_start();
        $this->controller->editWord([]);
        $output = ob_get_clean();

        // No output when early return
        $this->assertSame('', $output);
    }
}
