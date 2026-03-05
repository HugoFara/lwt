<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\TermImportController;
use Lwt\Modules\Vocabulary\Http\VocabularyBaseController;
use Lwt\Modules\Language\Application\LanguageFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TermImportController.
 *
 * Tests class structure, method signatures, constructor behavior,
 * and lazy-loaded service accessors via reflection.
 */
class TermImportControllerTest extends TestCase
{
    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageFacade;

    private TermImportController $controller;

    protected function setUp(): void
    {
        $this->languageFacade = $this->createMock(LanguageFacade::class);
        $this->controller = new TermImportController($this->languageFacade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(TermImportController::class, $this->controller);
    }

    #[Test]
    public function constructorAcceptsNullLanguageFacade(): void
    {
        $controller = new TermImportController(null);
        $this->assertInstanceOf(TermImportController::class, $controller);
    }

    #[Test]
    public function constructorSetsLanguageFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TermImportController::class, 'languageFacade');
        $reflection->setAccessible(true);

        $facade = $reflection->getValue($this->controller);

        $this->assertSame($this->languageFacade, $facade);
    }

    #[Test]
    public function constructorWithNullCreatesDefaultLanguageFacade(): void
    {
        $controller = new TermImportController(null);

        $reflection = new \ReflectionProperty(TermImportController::class, 'languageFacade');
        $reflection->setAccessible(true);

        $facade = $reflection->getValue($controller);

        $this->assertInstanceOf(LanguageFacade::class, $facade);
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsVocabularyBaseController(): void
    {
        $reflection = new \ReflectionClass(TermImportController::class);

        $this->assertSame(
            VocabularyBaseController::class,
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TermImportController::class);

        $expectedMethods = ['bulkTranslate', 'upload'];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermImportController should have method: $methodName"
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
        $reflection = new \ReflectionClass(TermImportController::class);

        $expectedMethods = [
            'handleBulkSave',
            'displayBulkTranslateForm',
            'displayUploadForm',
            'handleUploadImport',
            'importTerms',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermImportController should have method: $methodName"
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
    public function bulkTranslateAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'bulkTranslate');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    #[Test]
    public function bulkTranslateReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'bulkTranslate');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    #[Test]
    public function uploadAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'upload');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    #[Test]
    public function uploadReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'upload');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    #[Test]
    public function importTermsHasCorrectParameterCount(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'importTerms');
        $params = $method->getParameters();

        $this->assertCount(11, $params);

        $expectedNames = [
            'uploadService', 'langId', 'fields', 'col', 'tabType',
            'fileName', 'status', 'overwrite', 'ignoreFirst',
            'translDelim', 'lastUpdate',
        ];

        foreach ($expectedNames as $i => $name) {
            $this->assertSame(
                $name,
                $params[$i]->getName(),
                "Parameter $i should be named $name"
            );
        }
    }

    #[Test]
    public function importTermsParameterTypes(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'importTerms');
        $params = $method->getParameters();

        // uploadService
        $this->assertSame(
            'Lwt\Modules\Vocabulary\Application\Services\WordUploadService',
            $params[0]->getType()->getName()
        );
        // langId
        $this->assertSame('int', $params[1]->getType()->getName());
        // fields
        $this->assertSame('array', $params[2]->getType()->getName());
        // col
        $this->assertSame('array', $params[3]->getType()->getName());
        // tabType
        $this->assertSame('string', $params[4]->getType()->getName());
        // fileName
        $this->assertSame('string', $params[5]->getType()->getName());
        // status
        $this->assertSame('int', $params[6]->getType()->getName());
        // overwrite
        $this->assertSame('int', $params[7]->getType()->getName());
        // ignoreFirst
        $this->assertSame('bool', $params[8]->getType()->getName());
        // translDelim
        $this->assertSame('string', $params[9]->getType()->getName());
        // lastUpdate
        $this->assertSame('string', $params[10]->getType()->getName());
    }

    // =========================================================================
    // View path tests
    // =========================================================================

    #[Test]
    public function viewPathPointsToVocabularyViews(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'viewPath');
        $reflection->setAccessible(true);

        $viewPath = $reflection->getValue($this->controller);

        $this->assertStringEndsWith('/Views/', $viewPath);
        $this->assertStringContainsString('Vocabulary', $viewPath);
    }

    // =========================================================================
    // Lazy-loaded service accessor tests
    // =========================================================================

    #[Test]
    public function lazyLoadedServicesAreInitiallyNull(): void
    {
        $nullableServices = [
            'crudService',
            'contextService',
            'bulkService',
            'discoveryService',
            'linkingService',
            'multiWordService',
            'sentenceService',
            'expressionService',
            'uploadService',
            'textStatisticsService',
        ];

        foreach ($nullableServices as $serviceName) {
            $reflection = new \ReflectionProperty(VocabularyBaseController::class, $serviceName);
            $reflection->setAccessible(true);

            $this->assertNull(
                $reflection->getValue($this->controller),
                "Service $serviceName should be null initially"
            );
        }
    }

    #[Test]
    public function languageFacadePropertyIsPrivate(): void
    {
        $reflection = new \ReflectionProperty(TermImportController::class, 'languageFacade');

        $this->assertTrue($reflection->isPrivate());
    }

    #[Test]
    public function handleBulkSaveParameterTypes(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'handleBulkSave');
        $params = $method->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('terms', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
        $this->assertSame('tid', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
        $this->assertSame('cleanUp', $params[2]->getName());
        $this->assertSame('bool', $params[2]->getType()->getName());
    }

    #[Test]
    public function displayBulkTranslateFormParameterTypes(): void
    {
        $method = new \ReflectionMethod(TermImportController::class, 'displayBulkTranslateForm');
        $params = $method->getParameters();

        $this->assertCount(4, $params);
        $this->assertSame('tid', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
        $this->assertSame('sl', $params[1]->getName());
        $this->assertTrue($params[1]->getType()->allowsNull());
        $this->assertSame('tl', $params[2]->getName());
        $this->assertTrue($params[2]->getType()->allowsNull());
        $this->assertSame('pos', $params[3]->getName());
        $this->assertSame('int', $params[3]->getType()->getName());
    }
}
