<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\ImportUtilities;
use Lwt\Modules\Vocabulary\Application\Services\SimpleImportService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for SimpleImportService.
 *
 * Tests constructor injection, method signatures, and internal structure.
 * Actual import logic requires DB, so we focus on contract verification.
 */
class SimpleImportServiceTest extends TestCase
{
    // =========================================================================
    // Constructor
    // =========================================================================

    #[Test]
    public function constructorAcceptsImportUtilities(): void
    {
        $utilities = $this->createMock(ImportUtilities::class);
        $service = new SimpleImportService($utilities);

        $this->assertInstanceOf(SimpleImportService::class, $service);
    }

    #[Test]
    public function constructorStoresUtilitiesInPrivateProperty(): void
    {
        $utilities = $this->createMock(ImportUtilities::class);
        $service = new SimpleImportService($utilities);

        $prop = new ReflectionProperty(SimpleImportService::class, 'utilities');
        $prop->setAccessible(true);
        $this->assertSame($utilities, $prop->getValue($service));
    }

    #[Test]
    public function utilitiesPropertyIsPrivate(): void
    {
        $prop = new ReflectionProperty(SimpleImportService::class, 'utilities');
        $this->assertTrue($prop->isPrivate());
    }

    #[Test]
    public function utilitiesPropertyIsTypedImportUtilities(): void
    {
        $prop = new ReflectionProperty(SimpleImportService::class, 'utilities');
        $type = $prop->getType();
        $this->assertNotNull($type);
        $this->assertSame(ImportUtilities::class, $type->getName());
    }

    // =========================================================================
    // importSimple method signature
    // =========================================================================

    #[Test]
    public function importSimpleMethodExists(): void
    {
        $this->assertTrue(
            method_exists(SimpleImportService::class, 'importSimple')
        );
    }

    #[Test]
    public function importSimpleIsPublic(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function importSimpleReturnsVoid(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $this->assertSame('void', $method->getReturnType()?->getName());
    }

    #[Test]
    public function importSimpleAcceptsSevenParameters(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $this->assertCount(7, $method->getParameters());
    }

    #[Test]
    public function importSimpleFirstParamIsLangId(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $param = $method->getParameters()[0];
        $this->assertSame('langId', $param->getName());
        $this->assertSame('int', $param->getType()?->getName());
    }

    #[Test]
    public function importSimpleSecondParamIsFields(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $param = $method->getParameters()[1];
        $this->assertSame('fields', $param->getName());
        $this->assertSame('array', $param->getType()?->getName());
    }

    #[Test]
    public function importSimpleThirdParamIsColumnsClause(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $param = $method->getParameters()[2];
        $this->assertSame('columnsClause', $param->getName());
        $this->assertSame('string', $param->getType()?->getName());
    }

    #[Test]
    public function importSimpleFourthParamIsDelimiter(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $param = $method->getParameters()[3];
        $this->assertSame('delimiter', $param->getName());
        $this->assertSame('string', $param->getType()?->getName());
    }

    #[Test]
    public function importSimpleFifthParamIsFileName(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $param = $method->getParameters()[4];
        $this->assertSame('fileName', $param->getName());
        $this->assertSame('string', $param->getType()?->getName());
    }

    #[Test]
    public function importSimpleSixthParamIsStatus(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $param = $method->getParameters()[5];
        $this->assertSame('status', $param->getName());
        $this->assertSame('int', $param->getType()?->getName());
    }

    #[Test]
    public function importSimpleSeventhParamIsIgnoreFirst(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimple');
        $param = $method->getParameters()[6];
        $this->assertSame('ignoreFirst', $param->getName());
        $this->assertSame('bool', $param->getType()?->getName());
    }

    // =========================================================================
    // Private methods exist
    // =========================================================================

    #[Test]
    public function importSimpleWithLoadDataMethodExists(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimpleWithLoadData');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function importSimpleWithPHPMethodExists(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'importSimpleWithPHP');
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function executeSimpleImportBatchMethodExists(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, 'executeSimpleImportBatch');
        $this->assertTrue($method->isPrivate());
    }

    // =========================================================================
    // Class structure
    // =========================================================================

    #[Test]
    public function classIsNotAbstract(): void
    {
        $class = new ReflectionClass(SimpleImportService::class);
        $this->assertFalse($class->isAbstract());
    }

    #[Test]
    public function classHasOnePublicMethod(): void
    {
        $class = new ReflectionClass(SimpleImportService::class);
        $publicMethods = array_filter(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            fn(ReflectionMethod $m) => $m->getDeclaringClass()->getName() === SimpleImportService::class
        );
        // Only importSimple and __construct
        $this->assertCount(2, $publicMethods);
    }

    #[Test]
    public function constructorRequiresOneParameter(): void
    {
        $method = new ReflectionMethod(SimpleImportService::class, '__construct');
        $this->assertCount(1, $method->getParameters());
        $this->assertSame(ImportUtilities::class, $method->getParameters()[0]->getType()?->getName());
    }
}
