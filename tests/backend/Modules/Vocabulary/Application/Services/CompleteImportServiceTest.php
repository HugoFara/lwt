<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\CompleteImportService;
use Lwt\Modules\Vocabulary\Application\Services\ImportUtilities;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for CompleteImportService.
 *
 * Tests constructor injection, method signatures, and internal structure.
 * Actual import logic requires DB/temp tables, so we verify contracts.
 */
class CompleteImportServiceTest extends TestCase
{
    // =========================================================================
    // Constructor
    // =========================================================================

    #[Test]
    public function constructorAcceptsImportUtilities(): void
    {
        $utilities = $this->createMock(ImportUtilities::class);
        $service = new CompleteImportService($utilities);

        $this->assertInstanceOf(CompleteImportService::class, $service);
    }

    #[Test]
    public function constructorStoresUtilitiesInPrivateProperty(): void
    {
        $utilities = $this->createMock(ImportUtilities::class);
        $service = new CompleteImportService($utilities);

        $prop = new ReflectionProperty(CompleteImportService::class, 'utilities');
        $prop->setAccessible(true);
        $this->assertSame($utilities, $prop->getValue($service));
    }

    #[Test]
    public function utilitiesPropertyIsPrivate(): void
    {
        $prop = new ReflectionProperty(CompleteImportService::class, 'utilities');
        $this->assertTrue($prop->isPrivate());
    }

    #[Test]
    public function utilitiesPropertyIsTypedImportUtilities(): void
    {
        $prop = new ReflectionProperty(CompleteImportService::class, 'utilities');
        $type = $prop->getType();
        $this->assertNotNull($type);
        $this->assertSame(ImportUtilities::class, $type->getName());
    }

    // =========================================================================
    // importComplete method signature
    // =========================================================================

    #[Test]
    public function importCompleteMethodExists(): void
    {
        $this->assertTrue(
            method_exists(CompleteImportService::class, 'importComplete')
        );
    }

    #[Test]
    public function importCompleteIsPublic(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function importCompleteReturnsVoid(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $this->assertSame('void', $method->getReturnType()?->getName());
    }

    #[Test]
    public function importCompleteAcceptsTenParameters(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $this->assertCount(10, $method->getParameters());
    }

    #[Test]
    public function importCompleteParamLangIdIsInt(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $param = $method->getParameters()[0];
        $this->assertSame('langId', $param->getName());
        $this->assertSame('int', $param->getType()?->getName());
    }

    #[Test]
    public function importCompleteParamFieldsIsArray(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $param = $method->getParameters()[1];
        $this->assertSame('fields', $param->getName());
        $this->assertSame('array', $param->getType()?->getName());
    }

    #[Test]
    public function importCompleteParamOverwriteIsInt(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $param = $method->getParameters()[6];
        $this->assertSame('overwrite', $param->getName());
        $this->assertSame('int', $param->getType()?->getName());
    }

    #[Test]
    public function importCompleteParamTranslDelimIsString(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $param = $method->getParameters()[8];
        $this->assertSame('translDelim', $param->getName());
        $this->assertSame('string', $param->getType()?->getName());
    }

    #[Test]
    public function importCompleteParamTabTypeIsString(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importComplete');
        $param = $method->getParameters()[9];
        $this->assertSame('tabType', $param->getName());
        $this->assertSame('string', $param->getType()?->getName());
    }

    // =========================================================================
    // importTagsOnly method signature
    // =========================================================================

    #[Test]
    public function importTagsOnlyMethodExists(): void
    {
        $this->assertTrue(
            method_exists(CompleteImportService::class, 'importTagsOnly')
        );
    }

    #[Test]
    public function importTagsOnlyIsPublic(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importTagsOnly');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function importTagsOnlyReturnsVoid(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importTagsOnly');
        $this->assertSame('void', $method->getReturnType()?->getName());
    }

    #[Test]
    public function importTagsOnlyAcceptsFourParameters(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importTagsOnly');
        $this->assertCount(4, $method->getParameters());
    }

    #[Test]
    public function importTagsOnlyParamFieldsIsArray(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importTagsOnly');
        $param = $method->getParameters()[0];
        $this->assertSame('fields', $param->getName());
        $this->assertSame('array', $param->getType()?->getName());
    }

    #[Test]
    public function importTagsOnlyParamTabTypeIsString(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importTagsOnly');
        $param = $method->getParameters()[1];
        $this->assertSame('tabType', $param->getName());
        $this->assertSame('string', $param->getType()?->getName());
    }

    #[Test]
    public function importTagsOnlyParamFileNameIsString(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importTagsOnly');
        $param = $method->getParameters()[2];
        $this->assertSame('fileName', $param->getName());
        $this->assertSame('string', $param->getType()?->getName());
    }

    #[Test]
    public function importTagsOnlyParamIgnoreFirstIsBool(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'importTagsOnly');
        $param = $method->getParameters()[3];
        $this->assertSame('ignoreFirst', $param->getName());
        $this->assertSame('bool', $param->getType()?->getName());
    }

    // =========================================================================
    // Private methods exist
    // =========================================================================

    #[Test]
    public function initTempTablesMethodExists(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'initTempTables');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function loadDataToTempTableMethodExists(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'loadDataToTempTable');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function loadDataToTempTableWithPHPMethodExists(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'loadDataToTempTableWithPHP');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function handleTranslationMergeMethodExists(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'handleTranslationMerge');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function executeMainImportQueryMethodExists(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'executeMainImportQuery');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function handleTagsImportMethodExists(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'handleTagsImport');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function cleanupTempTablesMethodExists(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, 'cleanupTempTables');
        $this->assertTrue($method->isPrivate());
    }

    // =========================================================================
    // Class structure
    // =========================================================================

    #[Test]
    public function classIsNotAbstract(): void
    {
        $class = new ReflectionClass(CompleteImportService::class);
        $this->assertFalse($class->isAbstract());
    }

    #[Test]
    public function classHasTwoPublicMethods(): void
    {
        $class = new ReflectionClass(CompleteImportService::class);
        $publicMethods = array_filter(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            fn(ReflectionMethod $m) => $m->getDeclaringClass()->getName() === CompleteImportService::class
        );
        // __construct, importComplete, importTagsOnly
        $this->assertCount(3, $publicMethods);
    }

    #[Test]
    public function constructorRequiresOneParameter(): void
    {
        $method = new ReflectionMethod(CompleteImportService::class, '__construct');
        $this->assertCount(1, $method->getParameters());
        $this->assertSame(ImportUtilities::class, $method->getParameters()[0]->getType()?->getName());
    }
}
