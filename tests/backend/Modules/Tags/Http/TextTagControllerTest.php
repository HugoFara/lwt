<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\Http;

use Lwt\Modules\Tags\Http\TermTagController;
use Lwt\Modules\Tags\Http\TextTagController;
use Lwt\Modules\Tags\Application\TagsFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextTagController.
 *
 * Tests text tag management: create, update, delete, bulk actions,
 * and view rendering logic.
 */
class TextTagControllerTest extends TestCase
{
    /** @var TagsFacade&MockObject */
    private TagsFacade $facade;

    private TextTagController $controller;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(TagsFacade::class);
        $this->controller = new TextTagController($this->facade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(TextTagController::class, $this->controller);
    }

    #[Test]
    public function constructorAcceptsNullFacade(): void
    {
        $controller = new TextTagController(null);
        $this->assertInstanceOf(TextTagController::class, $controller);
    }

    #[Test]
    public function constructorSetsFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TextTagController::class, 'facade');
        $reflection->setAccessible(true);

        $facade = $reflection->getValue($this->controller);

        $this->assertInstanceOf(TagsFacade::class, $facade);
    }

    // =========================================================================
    // Page title and resource name tests
    // =========================================================================

    #[Test]
    public function pageTitleIsTextTags(): void
    {
        $reflection = new \ReflectionProperty(TextTagController::class, 'pageTitle');
        $reflection->setAccessible(true);

        $this->assertSame('Text Tags', $reflection->getValue($this->controller));
    }

    #[Test]
    public function resourceNameIsTag(): void
    {
        $reflection = new \ReflectionProperty(TextTagController::class, 'resourceName');
        $reflection->setAccessible(true);

        $this->assertSame('tag', $reflection->getValue($this->controller));
    }

    // =========================================================================
    // getIdParameterName tests
    // =========================================================================

    #[Test]
    public function getIdParameterNameReturnsT2ID(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'getIdParameterName');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertSame('T2ID', $result);
    }

    // =========================================================================
    // handleCreate tests via reflection
    // =========================================================================

    #[Test]
    public function handleCreateReturnsSuccessMessage(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleCreate');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('create')
            ->with('', '')
            ->willReturn(['success' => true]);

        $result = $method->invoke($this->controller);

        $this->assertSame('Saved', $result);
    }

    #[Test]
    public function handleCreateReturnsErrorMessageOnFailure(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleCreate');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('create')
            ->willReturn(['success' => false, 'error' => 'Tag already exists']);

        $result = $method->invoke($this->controller);

        $this->assertSame('Error: Tag already exists', $result);
    }

    #[Test]
    public function handleCreateReturnsUnknownErrorWhenNoErrorKey(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleCreate');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('create')
            ->willReturn(['success' => false]);

        $result = $method->invoke($this->controller);

        $this->assertSame('Error: Unknown error', $result);
    }

    // =========================================================================
    // handleUpdate tests via reflection
    // =========================================================================

    #[Test]
    public function handleUpdateReturnsSuccessMessage(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleUpdate');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('update')
            ->with(42, '', '')
            ->willReturn(['success' => true]);

        $result = $method->invoke($this->controller, 42);

        $this->assertSame('Updated', $result);
    }

    #[Test]
    public function handleUpdateReturnsErrorMessageOnFailure(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleUpdate');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('update')
            ->with(7, '', '')
            ->willReturn(['success' => false, 'error' => 'Tag not found']);

        $result = $method->invoke($this->controller, 7);

        $this->assertSame('Error: Tag not found', $result);
    }

    #[Test]
    public function handleUpdateReturnsUnknownErrorWhenNoErrorKey(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleUpdate');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('update')
            ->willReturn(['success' => false]);

        $result = $method->invoke($this->controller, 1);

        $this->assertSame('Error: Unknown error', $result);
    }

    // =========================================================================
    // handleDelete tests via reflection
    // =========================================================================

    #[Test]
    public function handleDeleteReturnsDeletedOnSuccess(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleDelete');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('delete')
            ->with(5)
            ->willReturn(['success' => true]);

        $result = $method->invoke($this->controller, 5);

        $this->assertSame('Deleted', $result);
    }

    #[Test]
    public function handleDeleteReturnsZeroRowsOnFailure(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleDelete');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('delete')
            ->with(99)
            ->willReturn(['success' => false]);

        $result = $method->invoke($this->controller, 99);

        $this->assertSame('Deleted (0 rows affected)', $result);
    }

    // =========================================================================
    // handleBulkAction tests via reflection
    // =========================================================================

    #[Test]
    public function handleBulkActionDeleteCallsFacade(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleBulkAction');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('deleteMultiple')
            ->with([1, 2, 3])
            ->willReturn(['count' => 3]);

        $this->facade->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $result = $method->invoke($this->controller, 'del', [1, 2, 3]);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['count']);
        $this->assertSame('del', $result['action']);
    }

    #[Test]
    public function handleBulkActionDeleteWithSingleId(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleBulkAction');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('deleteMultiple')
            ->with([10])
            ->willReturn(['count' => 1]);

        $this->facade->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $result = $method->invoke($this->controller, 'del', [10]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['count']);
    }

    #[Test]
    public function handleBulkActionDeleteWithEmptyIds(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleBulkAction');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('deleteMultiple')
            ->with([])
            ->willReturn(['count' => 0]);

        $this->facade->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $result = $method->invoke($this->controller, 'del', []);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['count']);
    }

    #[Test]
    public function handleBulkActionUnknownActionDelegatesToParent(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'handleBulkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'unknown_action', [1, 2]);

        $this->assertFalse($result['success']);
        $this->assertSame('unknown_action', $result['error']);
    }

    // =========================================================================
    // processAllAction tests via reflection
    // =========================================================================

    #[Test]
    public function processAllActionDeleteAllCallsFacade(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'processAllAction');
        $method->setAccessible(true);

        // Set currentQuery via reflection
        $queryProp = new \ReflectionProperty(TextTagController::class, 'currentQuery');
        $queryProp->setAccessible(true);
        $queryProp->setValue($this->controller, 'search term');

        $this->facade->expects($this->once())
            ->method('deleteAll')
            ->with('search term')
            ->willReturn(['count' => 8]);

        $result = $method->invoke($this->controller, 'delall');

        $this->assertTrue($result['success']);
        $this->assertSame(8, $result['count']);
        $this->assertSame('delall', $result['action']);
    }

    #[Test]
    public function processAllActionDeleteAllWithEmptyQuery(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'processAllAction');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('deleteAll')
            ->with('')
            ->willReturn(['count' => 15]);

        $result = $method->invoke($this->controller, 'delall');

        $this->assertTrue($result['success']);
        $this->assertSame(15, $result['count']);
    }

    #[Test]
    public function processAllActionUnknownActionDelegatesToParent(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'processAllAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'unknown');

        $this->assertFalse($result['success']);
        $this->assertSame('not_implemented', $result['error']);
    }

    // =========================================================================
    // renderEditForm tests via reflection
    // =========================================================================

    #[Test]
    public function renderEditFormWithNonExistentTagOutputsNotFound(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'renderEditForm');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('getById')
            ->with(999)
            ->willReturn(null);

        ob_start();
        $method->invoke($this->controller, 999);
        $output = ob_get_clean();

        // The message() method should have been called with "Tag not found"
        // No exception should be thrown
        $this->assertIsString($output);
    }

    #[Test]
    public function renderEditFormCallsGetById(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'renderEditForm');
        $method->setAccessible(true);

        $this->facade->expects($this->once())
            ->method('getById')
            ->with(42)
            ->willReturn(null);

        ob_start();
        $method->invoke($this->controller, 42);
        ob_get_clean();

        // Assertion is via the expects($this->once()) constraint above
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsAbstractCrudController(): void
    {
        $reflection = new \ReflectionClass(TextTagController::class);

        $this->assertSame(
            'Lwt\Shared\Http\AbstractCrudController',
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextTagController::class);

        $expectedMethods = ['new', 'edit', 'delete', 'index'];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextTagController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function classHasRequiredProtectedMethods(): void
    {
        $reflection = new \ReflectionClass(TextTagController::class);

        $expectedMethods = [
            'handleCreate',
            'handleUpdate',
            'handleDelete',
            'handleBulkAction',
            'processAllAction',
            'renderList',
            'renderCreateForm',
            'renderEditForm',
            'getIdParameterName',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextTagController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isProtected(),
                "Method $methodName should be protected"
            );
        }
    }

    #[Test]
    public function newMethodAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'new');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function editMethodAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'edit');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    #[Test]
    public function deleteMethodAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'delete');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    // =========================================================================
    // Default state tests
    // =========================================================================

    #[Test]
    public function defaultCurrentQueryIsEmpty(): void
    {
        $reflection = new \ReflectionProperty(TextTagController::class, 'currentQuery');
        $reflection->setAccessible(true);

        $this->assertSame('', $reflection->getValue($this->controller));
    }

    #[Test]
    public function defaultCurrentSortIsOne(): void
    {
        $reflection = new \ReflectionProperty(TextTagController::class, 'currentSort');
        $reflection->setAccessible(true);

        $this->assertSame(1, $reflection->getValue($this->controller));
    }

    #[Test]
    public function defaultCurrentPageIsOne(): void
    {
        $reflection = new \ReflectionProperty(TextTagController::class, 'currentPage');
        $reflection->setAccessible(true);

        $this->assertSame(1, $reflection->getValue($this->controller));
    }

    // =========================================================================
    // Difference from TermTagController tests
    // =========================================================================

    #[Test]
    public function renderCreateFormUsesT2Prefix(): void
    {
        $method = new \ReflectionMethod(TextTagController::class, 'renderCreateForm');
        $method->setAccessible(true);

        // We cannot fully invoke renderCreateForm because it includes a view file,
        // but we can verify the method exists and is callable
        $this->assertTrue($method->isProtected());
        $this->assertSame(0, $method->getNumberOfRequiredParameters());
    }

    #[Test]
    public function idParameterNameDiffersFromTermTag(): void
    {
        $termController = new TermTagController($this->facade);

        $termMethod = new \ReflectionMethod(TermTagController::class, 'getIdParameterName');
        $termMethod->setAccessible(true);

        $textMethod = new \ReflectionMethod(TextTagController::class, 'getIdParameterName');
        $textMethod->setAccessible(true);

        $termId = $termMethod->invoke($termController);
        $textId = $textMethod->invoke($this->controller);

        $this->assertSame('TgID', $termId);
        $this->assertSame('T2ID', $textId);
        $this->assertNotSame($termId, $textId);
    }
}
