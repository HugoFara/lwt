<?php declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\FlashMessageService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FlashMessageService.
 *
 * Tests flash message storage, retrieval, and clearing functionality.
 *
 * @covers \Lwt\Shared\Infrastructure\Http\FlashMessageService
 */
class FlashMessageServiceTest extends TestCase
{
    private FlashMessageService $service;

    protected function setUp(): void
    {
        // Clear any existing session data
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        $this->service = new FlashMessageService();
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // ===================================
    // BASIC MESSAGE OPERATIONS
    // ===================================

    public function testHasReturnsFalseWhenNoMessages(): void
    {
        $this->assertFalse($this->service->has());
    }

    public function testHasReturnsTrueAfterAddingMessage(): void
    {
        $this->service->add('Test message');
        $this->assertTrue($this->service->has());
    }

    public function testAddCreatesMessageWithDefaultType(): void
    {
        $this->service->add('Test message');

        $messages = $this->service->getAndClear();

        $this->assertCount(1, $messages);
        $this->assertEquals('Test message', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_INFO, $messages[0]['type']);
    }

    public function testAddCreatesMessageWithSpecifiedType(): void
    {
        $this->service->add('Error occurred', FlashMessageService::TYPE_ERROR);

        $messages = $this->service->getAndClear();

        $this->assertCount(1, $messages);
        $this->assertEquals('Error occurred', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_ERROR, $messages[0]['type']);
    }

    // ===================================
    // CONVENIENCE METHOD TESTS
    // ===================================

    public function testInfoAddsInfoMessage(): void
    {
        $this->service->info('Info message');

        $messages = $this->service->getAndClear();

        $this->assertCount(1, $messages);
        $this->assertEquals('Info message', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_INFO, $messages[0]['type']);
    }

    public function testSuccessAddsSuccessMessage(): void
    {
        $this->service->success('Operation successful');

        $messages = $this->service->getAndClear();

        $this->assertCount(1, $messages);
        $this->assertEquals('Operation successful', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_SUCCESS, $messages[0]['type']);
    }

    public function testWarningAddsWarningMessage(): void
    {
        $this->service->warning('Warning message');

        $messages = $this->service->getAndClear();

        $this->assertCount(1, $messages);
        $this->assertEquals('Warning message', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_WARNING, $messages[0]['type']);
    }

    public function testErrorAddsErrorMessage(): void
    {
        $this->service->error('Error message');

        $messages = $this->service->getAndClear();

        $this->assertCount(1, $messages);
        $this->assertEquals('Error message', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_ERROR, $messages[0]['type']);
    }

    // ===================================
    // ADD MANY TESTS
    // ===================================

    public function testAddManyAddsMultipleMessages(): void
    {
        $messages = ['Message 1', 'Message 2', 'Message 3'];
        $this->service->addMany($messages);

        $result = $this->service->getAndClear();

        $this->assertCount(3, $result);
        $this->assertEquals('Message 1', $result[0]['message']);
        $this->assertEquals('Message 2', $result[1]['message']);
        $this->assertEquals('Message 3', $result[2]['message']);
    }

    public function testAddManyUsesSpecifiedType(): void
    {
        $messages = ['Error 1', 'Error 2'];
        $this->service->addMany($messages, FlashMessageService::TYPE_ERROR);

        $result = $this->service->getAndClear();

        $this->assertCount(2, $result);
        $this->assertEquals(FlashMessageService::TYPE_ERROR, $result[0]['type']);
        $this->assertEquals(FlashMessageService::TYPE_ERROR, $result[1]['type']);
    }

    // ===================================
    // HAS WITH TYPE FILTER TESTS
    // ===================================

    public function testHasWithTypeReturnsFalseWhenTypeNotPresent(): void
    {
        $this->service->info('Info message');

        $this->assertTrue($this->service->has(FlashMessageService::TYPE_INFO));
        $this->assertFalse($this->service->has(FlashMessageService::TYPE_ERROR));
    }

    public function testHasWithTypeReturnsTrueWhenTypePresent(): void
    {
        $this->service->error('Error message');
        $this->service->info('Info message');

        $this->assertTrue($this->service->has(FlashMessageService::TYPE_ERROR));
        $this->assertTrue($this->service->has(FlashMessageService::TYPE_INFO));
        $this->assertFalse($this->service->has(FlashMessageService::TYPE_WARNING));
    }

    // ===================================
    // GET AND CLEAR TESTS
    // ===================================

    public function testGetAndClearReturnsEmptyArrayWhenNoMessages(): void
    {
        $messages = $this->service->getAndClear();

        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    public function testGetAndClearReturnsAllMessages(): void
    {
        $this->service->info('Message 1');
        $this->service->error('Message 2');
        $this->service->warning('Message 3');

        $messages = $this->service->getAndClear();

        $this->assertCount(3, $messages);
    }

    public function testGetAndClearRemovesMessages(): void
    {
        $this->service->info('Test message');

        $this->service->getAndClear();

        $this->assertFalse($this->service->has());
        $this->assertEmpty($this->service->getAndClear());
    }

    public function testGetAndClearPreservesMessageOrder(): void
    {
        $this->service->add('First');
        $this->service->add('Second');
        $this->service->add('Third');

        $messages = $this->service->getAndClear();

        $this->assertEquals('First', $messages[0]['message']);
        $this->assertEquals('Second', $messages[1]['message']);
        $this->assertEquals('Third', $messages[2]['message']);
    }

    // ===================================
    // GET BY TYPE AND CLEAR TESTS
    // ===================================

    public function testGetByTypeAndClearReturnsOnlyMatchingType(): void
    {
        $this->service->info('Info 1');
        $this->service->error('Error 1');
        $this->service->info('Info 2');
        $this->service->error('Error 2');

        $errors = $this->service->getByTypeAndClear(FlashMessageService::TYPE_ERROR);

        $this->assertCount(2, $errors);
        $this->assertEquals('Error 1', $errors[0]['message']);
        $this->assertEquals('Error 2', $errors[1]['message']);
    }

    public function testGetByTypeAndClearLeavesOtherTypes(): void
    {
        $this->service->info('Info message');
        $this->service->error('Error message');

        $this->service->getByTypeAndClear(FlashMessageService::TYPE_ERROR);

        // Info message should remain
        $this->assertTrue($this->service->has(FlashMessageService::TYPE_INFO));
        $this->assertFalse($this->service->has(FlashMessageService::TYPE_ERROR));
    }

    public function testGetByTypeAndClearReturnsEmptyArrayWhenTypeNotPresent(): void
    {
        $this->service->info('Info message');

        $errors = $this->service->getByTypeAndClear(FlashMessageService::TYPE_ERROR);

        $this->assertEmpty($errors);
    }

    // ===================================
    // GET MESSAGES AND CLEAR TESTS
    // ===================================

    public function testGetMessagesAndClearReturnsOnlyMessageStrings(): void
    {
        $this->service->info('Message 1');
        $this->service->error('Message 2');

        $messages = $this->service->getMessagesAndClear();

        $this->assertCount(2, $messages);
        $this->assertEquals('Message 1', $messages[0]);
        $this->assertEquals('Message 2', $messages[1]);
        $this->assertIsString($messages[0]);
        $this->assertIsString($messages[1]);
    }

    public function testGetMessagesAndClearRemovesMessages(): void
    {
        $this->service->info('Test');

        $this->service->getMessagesAndClear();

        $this->assertFalse($this->service->has());
    }

    // ===================================
    // CLEAR TESTS
    // ===================================

    public function testClearRemovesAllMessages(): void
    {
        $this->service->info('Message 1');
        $this->service->error('Message 2');
        $this->service->warning('Message 3');

        $this->service->clear();

        $this->assertFalse($this->service->has());
        $this->assertEmpty($this->service->getAndClear());
    }

    public function testClearDoesNotErrorWhenNoMessages(): void
    {
        // Should not throw an exception
        $this->service->clear();
        $this->assertFalse($this->service->has());
    }

    // ===================================
    // CSS CLASS TESTS
    // ===================================

    public function testGetCssClassReturnsCorrectClassForInfo(): void
    {
        $class = FlashMessageService::getCssClass(FlashMessageService::TYPE_INFO);
        $this->assertEquals('is-info', $class);
    }

    public function testGetCssClassReturnsCorrectClassForSuccess(): void
    {
        $class = FlashMessageService::getCssClass(FlashMessageService::TYPE_SUCCESS);
        $this->assertEquals('is-success', $class);
    }

    public function testGetCssClassReturnsCorrectClassForWarning(): void
    {
        $class = FlashMessageService::getCssClass(FlashMessageService::TYPE_WARNING);
        $this->assertEquals('is-warning', $class);
    }

    public function testGetCssClassReturnsCorrectClassForError(): void
    {
        $class = FlashMessageService::getCssClass(FlashMessageService::TYPE_ERROR);
        $this->assertEquals('is-danger', $class);
    }

    public function testGetCssClassReturnsDefaultForUnknownType(): void
    {
        $class = FlashMessageService::getCssClass('unknown_type');
        $this->assertEquals('is-info', $class);
    }

    // ===================================
    // IS ERROR TESTS
    // ===================================

    public function testIsErrorReturnsTrueForErrorType(): void
    {
        $this->assertTrue(FlashMessageService::isError(FlashMessageService::TYPE_ERROR));
    }

    public function testIsErrorReturnsFalseForOtherTypes(): void
    {
        $this->assertFalse(FlashMessageService::isError(FlashMessageService::TYPE_INFO));
        $this->assertFalse(FlashMessageService::isError(FlashMessageService::TYPE_SUCCESS));
        $this->assertFalse(FlashMessageService::isError(FlashMessageService::TYPE_WARNING));
    }

    // ===================================
    // TYPE CONSTANT TESTS
    // ===================================

    public function testTypeConstantsAreDefined(): void
    {
        $this->assertEquals('info', FlashMessageService::TYPE_INFO);
        $this->assertEquals('success', FlashMessageService::TYPE_SUCCESS);
        $this->assertEquals('warning', FlashMessageService::TYPE_WARNING);
        $this->assertEquals('error', FlashMessageService::TYPE_ERROR);
    }

    // ===================================
    // SESSION PERSISTENCE TESTS
    // ===================================

    public function testMessagesPersistAcrossServiceInstances(): void
    {
        // Add message with first service instance
        $this->service->info('Persisted message');

        // Create new service instance (simulates new request)
        $newService = new FlashMessageService();

        // Message should be available
        $this->assertTrue($newService->has());
        $messages = $newService->getAndClear();
        $this->assertEquals('Persisted message', $messages[0]['message']);
    }

    // ===================================
    // INTEGRATION TESTS
    // ===================================

    public function testTypicalFlashMessageWorkflow(): void
    {
        // Simulate controller action that redirects
        $this->service->success('Item created successfully');

        // Simulate view rendering after redirect
        $messages = $this->service->getAndClear();

        // Display messages
        $this->assertCount(1, $messages);
        $this->assertEquals('Item created successfully', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_SUCCESS, $messages[0]['type']);
        $this->assertEquals('is-success', FlashMessageService::getCssClass($messages[0]['type']));

        // Messages should be cleared after display
        $this->assertFalse($this->service->has());
    }

    public function testMultipleMessagesFromDifferentActions(): void
    {
        // First action adds success
        $this->service->success('Feed loaded');

        // Second action adds warning
        $this->service->warning('Some articles could not be parsed');

        // Third action adds info
        $this->service->info('5 new articles found');

        // View displays all messages
        $messages = $this->service->getAndClear();

        $this->assertCount(3, $messages);

        // Messages should be in order
        $this->assertEquals('Feed loaded', $messages[0]['message']);
        $this->assertEquals(FlashMessageService::TYPE_SUCCESS, $messages[0]['type']);

        $this->assertEquals('Some articles could not be parsed', $messages[1]['message']);
        $this->assertEquals(FlashMessageService::TYPE_WARNING, $messages[1]['type']);

        $this->assertEquals('5 new articles found', $messages[2]['message']);
        $this->assertEquals(FlashMessageService::TYPE_INFO, $messages[2]['type']);
    }

    public function testErrorMessagesDisplayedDifferently(): void
    {
        $this->service->error('Error loading feed');

        $messages = $this->service->getAndClear();

        // Error messages should have different CSS class
        $this->assertEquals('is-danger', FlashMessageService::getCssClass($messages[0]['type']));
        $this->assertTrue(FlashMessageService::isError($messages[0]['type']));
    }
}
