<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\TermDisplayController;
use Lwt\Modules\Vocabulary\Http\VocabularyBaseController;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use Lwt\Modules\Language\Domain\ValueObject\LanguageId;
use Lwt\Shared\Infrastructure\Dictionary\DictionaryAdapter;
use Lwt\Modules\Language\Application\LanguageFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TermDisplayController.
 *
 * Tests constructor behavior, class structure, method signatures,
 * show/edit/hover logic, and edge cases.
 */
class TermDisplayControllerTest extends TestCase
{
    /** @var VocabularyFacade&MockObject */
    private VocabularyFacade $facade;

    /** @var CreateTermFromHover&MockObject */
    private CreateTermFromHover $createTermFromHover;

    /** @var FindSimilarTerms&MockObject */
    private FindSimilarTerms $findSimilarTerms;

    /** @var DictionaryAdapter&MockObject */
    private DictionaryAdapter $dictionaryAdapter;

    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageFacade;

    private TermDisplayController $controller;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(VocabularyFacade::class);
        $this->createTermFromHover = $this->createMock(CreateTermFromHover::class);
        $this->findSimilarTerms = $this->createMock(FindSimilarTerms::class);
        $this->dictionaryAdapter = $this->createMock(DictionaryAdapter::class);
        $this->languageFacade = $this->createMock(LanguageFacade::class);
        $this->controller = new TermDisplayController(
            $this->facade,
            $this->createTermFromHover,
            $this->findSimilarTerms,
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
        $this->assertInstanceOf(TermDisplayController::class, $this->controller);
    }

    #[Test]
    public function constructorAcceptsAllNullParameters(): void
    {
        $controller = new TermDisplayController(null, null, null, null, null);
        $this->assertInstanceOf(TermDisplayController::class, $controller);
    }

    #[Test]
    public function constructorSetsFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TermDisplayController::class, 'facade');
        $reflection->setAccessible(true);

        $this->assertSame($this->facade, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsCreateTermFromHoverProperty(): void
    {
        $reflection = new \ReflectionProperty(TermDisplayController::class, 'createTermFromHover');
        $reflection->setAccessible(true);

        $this->assertSame($this->createTermFromHover, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsFindSimilarTermsProperty(): void
    {
        $reflection = new \ReflectionProperty(TermDisplayController::class, 'findSimilarTerms');
        $reflection->setAccessible(true);

        $this->assertSame($this->findSimilarTerms, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsDictionaryAdapterProperty(): void
    {
        $reflection = new \ReflectionProperty(TermDisplayController::class, 'dictionaryAdapter');
        $reflection->setAccessible(true);

        $this->assertSame($this->dictionaryAdapter, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsLanguageFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TermDisplayController::class, 'languageFacade');
        $reflection->setAccessible(true);

        $this->assertSame($this->languageFacade, $reflection->getValue($this->controller));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsVocabularyBaseController(): void
    {
        $reflection = new \ReflectionClass(TermDisplayController::class);

        $this->assertSame(
            VocabularyBaseController::class,
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TermDisplayController::class);

        $expectedMethods = [
            'show',
            'showWord',
            'edit',
            'hoverCreate',
            'similarTerms',
            'listEditAlpine',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermDisplayController should have method: $methodName"
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
        $reflection = new \ReflectionClass(TermDisplayController::class);

        $expectedMethods = [
            'createFromHover',
            'getDictionaryLinks',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermDisplayController should have private method: $methodName"
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
    public function showAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'show');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function showWordAcceptsNullableIntParameter(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'showWord');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('wid', $params[0]->getName());
        $this->assertTrue($params[0]->allowsNull());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertNull($params[0]->getDefaultValue());
    }

    #[Test]
    public function editAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'edit');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function hoverCreateAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'hoverCreate');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function similarTermsAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'similarTerms');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function listEditAlpineAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'listEditAlpine');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    // =========================================================================
    // show tests
    // =========================================================================

    #[Test]
    public function showWithZeroTermIdOutputsError(): void
    {
        // No GET params, termId defaults to 0
        ob_start();
        $this->controller->show([]);
        $output = ob_get_clean();

        $this->assertSame('Term ID required', $output);
    }

    #[Test]
    public function showWithNonexistentTermOutputsNotFound(): void
    {
        $_REQUEST['wid'] = '42';

        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(42)
            ->willReturn(null);

        ob_start();
        $this->controller->show([]);
        $output = ob_get_clean();

        $this->assertSame('Term not found', $output);

        unset($_REQUEST['wid']);
    }

    // =========================================================================
    // edit tests
    // =========================================================================

    #[Test]
    public function editWithZeroTermIdOutputsError(): void
    {
        ob_start();
        $this->controller->edit([]);
        $output = ob_get_clean();

        $this->assertSame('Term ID required', $output);
    }

    #[Test]
    public function editWithNonexistentTermOutputsNotFound(): void
    {
        $_REQUEST['wid'] = '99';

        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(99)
            ->willReturn(null);

        ob_start();
        $this->controller->edit([]);
        $output = ob_get_clean();

        $this->assertSame('Term not found', $output);

        unset($_REQUEST['wid']);
    }

    // =========================================================================
    // similarTerms tests
    // =========================================================================

    #[Test]
    public function similarTermsCallsFindSimilarTerms(): void
    {
        $_REQUEST['lgid'] = '5';
        $_REQUEST['term'] = 'hello';

        $this->findSimilarTerms->expects($this->once())
            ->method('getFormattedTerms')
            ->with(5, 'hello')
            ->willReturn('<tr><td>hello</td></tr>');

        ob_start();
        $this->controller->similarTerms([]);
        $output = ob_get_clean();

        $this->assertSame('<tr><td>hello</td></tr>', $output);

        unset($_REQUEST['lgid'], $_REQUEST['term']);
    }

    #[Test]
    public function similarTermsWithEmptyTermReturnsEmpty(): void
    {
        $_REQUEST['lgid'] = '1';
        $_REQUEST['term'] = '';

        $this->findSimilarTerms->expects($this->once())
            ->method('getFormattedTerms')
            ->with(1, '')
            ->willReturn('');

        ob_start();
        $this->controller->similarTerms([]);
        $output = ob_get_clean();

        $this->assertSame('', $output);

        unset($_REQUEST['lgid'], $_REQUEST['term']);
    }

    #[Test]
    public function similarTermsWithZeroLangId(): void
    {
        // No lgid param, defaults to 0
        $this->findSimilarTerms->expects($this->once())
            ->method('getFormattedTerms')
            ->with(0, '');

        ob_start();
        $this->controller->similarTerms([]);
        ob_get_clean();
    }

    // =========================================================================
    // getDictionaryLinks tests via reflection
    // =========================================================================

    #[Test]
    public function getDictionaryLinksCallsAdapter(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'getDictionaryLinks');
        $method->setAccessible(true);

        $this->dictionaryAdapter->expects($this->once())
            ->method('createDictLinksInEditWin')
            ->with(1, 'test', 'sentctrl', true)
            ->willReturn('<div>links</div>');

        $result = $method->invoke($this->controller, 1, 'test', 'sentctrl', true);

        $this->assertSame('<div>links</div>', $result);
    }

    #[Test]
    public function getDictionaryLinksDefaultOpenFirstIsFalse(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'getDictionaryLinks');
        $method->setAccessible(true);

        $params = $method->getParameters();
        $this->assertSame('openFirst', $params[3]->getName());
        $this->assertTrue($params[3]->isDefaultValueAvailable());
        $this->assertFalse($params[3]->getDefaultValue());
    }

    // =========================================================================
    // createFromHover tests via reflection
    // =========================================================================

    #[Test]
    public function createFromHoverCallsUseCaseExecute(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'createFromHover');
        $method->setAccessible(true);

        $this->createTermFromHover->expects($this->once())
            ->method('shouldSetNoCacheHeaders')
            ->with(2)
            ->willReturn(false);

        $this->createTermFromHover->expects($this->once())
            ->method('execute')
            ->with(5, 'word', 2, 'de', 'en')
            ->willReturn(['wid' => 10, 'hex' => 'ff', 'word' => 'word']);

        $result = $method->invoke($this->controller, 5, 'word', 2, 'de', 'en');

        $this->assertSame(10, $result['wid']);
    }

    #[Test]
    public function createFromHoverUsesDefaultEmptyLanguageParams(): void
    {
        $method = new \ReflectionMethod(TermDisplayController::class, 'createFromHover');
        $method->setAccessible(true);

        $params = $method->getParameters();
        $this->assertSame('sourceLang', $params[3]->getName());
        $this->assertSame('', $params[3]->getDefaultValue());
        $this->assertSame('targetLang', $params[4]->getName());
        $this->assertSame('', $params[4]->getDefaultValue());
    }

    // =========================================================================
    // showWord tests
    // =========================================================================

    #[Test]
    public function showWordWithNullWidAndNoQueryParamOutputsError(): void
    {
        ob_start();
        $this->controller->showWord(null);
        $output = ob_get_clean();

        $this->assertStringContainsString('Word ID is required', $output);
    }

    #[Test]
    public function showWordWithNonexistentTermOutputsNotFound(): void
    {
        $this->facade->expects($this->once())
            ->method('getTerm')
            ->with(42)
            ->willReturn(null);

        ob_start();
        $this->controller->showWord(42);
        $output = ob_get_clean();

        $this->assertStringContainsString('Word not found', $output);
    }

    // =========================================================================
    // View path and render tests
    // =========================================================================

    #[Test]
    public function viewPathPointsToModuleViews(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'viewPath');
        $reflection->setAccessible(true);

        $path = $reflection->getValue($this->controller);

        $this->assertStringEndsWith('Views/', $path);
        $this->assertStringContainsString('Vocabulary', $path);
    }

    #[Test]
    public function setViewPathWorks(): void
    {
        $this->controller->setViewPath('/tmp/views');

        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'viewPath');
        $reflection->setAccessible(true);

        $this->assertSame('/tmp/views/', $reflection->getValue($this->controller));
    }
}
