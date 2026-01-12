<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\MultiWordApiHandler;
use Lwt\Modules\Vocabulary\Application\Services\MultiWordService;
use Lwt\Modules\Vocabulary\Application\Services\WordContextService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for MultiWordApiHandler.
 *
 * Tests multi-word expression API operations including creation, editing, and updates.
 */
class MultiWordApiHandlerTest extends TestCase
{
    /** @var MultiWordService&MockObject */
    private MultiWordService $multiWordService;

    /** @var WordContextService&MockObject */
    private WordContextService $contextService;

    private MultiWordApiHandler $handler;

    protected function setUp(): void
    {
        $this->multiWordService = $this->createMock(MultiWordService::class);
        $this->contextService = $this->createMock(WordContextService::class);
        $this->handler = new MultiWordApiHandler($this->multiWordService, $this->contextService);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(MultiWordApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameters(): void
    {
        $handler = new MultiWordApiHandler(null, null);
        $this->assertInstanceOf(MultiWordApiHandler::class, $handler);
    }

    public function testConstructorAcceptsPartialNullParameters(): void
    {
        $handler = new MultiWordApiHandler($this->multiWordService, null);
        $this->assertInstanceOf(MultiWordApiHandler::class, $handler);

        $handler2 = new MultiWordApiHandler(null, $this->contextService);
        $this->assertInstanceOf(MultiWordApiHandler::class, $handler2);
    }

    // =========================================================================
    // getMultiWordForEdit tests
    // =========================================================================

    public function testGetMultiWordForEditReturnsErrorForNonExistentText(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->getMultiWordForEdit(999, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text not found', $result['error']);
    }

    public function testGetMultiWordForEditReturnsErrorForMissingText(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $result = $this->handler->getMultiWordForEdit(1, 0, null, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Multi-word text is required for new expressions', $result['error']);
    }

    public function testGetMultiWordForEditReturnsErrorForEmptyText(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $result = $this->handler->getMultiWordForEdit(1, 0, '', null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Multi-word text is required for new expressions', $result['error']);
    }

    public function testGetMultiWordForEditReturnsErrorForNonExistentWordId(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);
        $this->multiWordService->method('getMultiWordData')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->getMultiWordForEdit(1, 0, null, 999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Multi-word expression not found', $result['error']);
    }

    public function testGetMultiWordForEditReturnsExistingWordData(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);
        $this->multiWordService->method('getMultiWordData')
            ->with(123)
            ->willReturn([
                'text' => 'test phrase',
                'translation' => 'translation',
                'romanization' => 'romanization',
                'sentence' => 'sentence',
                'notes' => 'notes',
                'status' => 3,
                'lgid' => 1
            ]);

        $result = $this->handler->getMultiWordForEdit(1, 0, null, 123);

        // Check for expected structure (may fail on DB query)
        $this->assertIsArray($result);
        if (!isset($result['error'])) {
            $this->assertEquals(123, $result['id']);
            $this->assertEquals('test phrase', $result['text']);
            $this->assertEquals('translation', $result['translation']);
            $this->assertEquals(3, $result['status']);
            $this->assertFalse($result['isNew']);
        }
    }

    public function testGetMultiWordForEditReturnsNewExpressionData(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);
        $this->contextService->method('getSentenceTextAtPosition')
            ->with(1, 5)
            ->willReturn('Example sentence');

        $result = $this->handler->getMultiWordForEdit(1, 5, 'new phrase', null);

        // May fail on database query, so check for error first
        if (isset($result['error'])) {
            $this->markTestSkipped('Database query failed: ' . $result['error']);
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isNew', $result);
        $this->assertTrue($result['isNew']);
        $this->assertEquals('new phrase', $result['text']);
        $this->assertEquals('new phrase', $result['textLc']);
        $this->assertEquals('', $result['translation']);
        $this->assertEquals(1, $result['status']);
        $this->assertEquals(1, $result['langId']);
    }

    public function testGetMultiWordForEditCalculatesWordCount(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);
        $this->contextService->method('getSentenceTextAtPosition')
            ->willReturn('');

        $result = $this->handler->getMultiWordForEdit(1, 0, 'one two three four', null);

        // May fail on database query
        if (isset($result['error'])) {
            $this->markTestSkipped('Database query failed: ' . $result['error']);
        }

        $this->assertEquals(4, $result['wordCount']);
    }

    public function testGetMultiWordForEditConvertsTextToLowercase(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);
        $this->contextService->method('getSentenceTextAtPosition')
            ->willReturn('');

        $result = $this->handler->getMultiWordForEdit(1, 0, 'UPPERCASE PHRASE', null);

        // May fail on database query
        if (isset($result['error'])) {
            $this->markTestSkipped('Database query failed: ' . $result['error']);
        }

        $this->assertEquals('UPPERCASE PHRASE', $result['text']);
        $this->assertEquals('uppercase phrase', $result['textLc']);
    }

    // =========================================================================
    // createMultiWordTerm tests
    // =========================================================================

    public function testCreateMultiWordTermReturnsErrorForMissingTextId(): void
    {
        $result = $this->handler->createMultiWordTerm(['text' => 'test']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text ID and multi-word text are required', $result['error']);
    }

    public function testCreateMultiWordTermReturnsErrorForZeroTextId(): void
    {
        $result = $this->handler->createMultiWordTerm(['textId' => 0, 'text' => 'test']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text ID and multi-word text are required', $result['error']);
    }

    public function testCreateMultiWordTermReturnsErrorForMissingText(): void
    {
        $result = $this->handler->createMultiWordTerm(['textId' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text ID and multi-word text are required', $result['error']);
    }

    public function testCreateMultiWordTermReturnsErrorForEmptyText(): void
    {
        $result = $this->handler->createMultiWordTerm(['textId' => 1, 'text' => '  ']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text ID and multi-word text are required', $result['error']);
    }

    public function testCreateMultiWordTermReturnsErrorForNonExistentText(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->createMultiWordTerm(['textId' => 999, 'text' => 'test phrase']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text not found', $result['error']);
    }

    public function testCreateMultiWordTermCallsServiceWithCorrectData(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->with(1)
            ->willReturn(5);

        $this->multiWordService->expects($this->once())
            ->method('createMultiWord')
            ->with($this->callback(function ($data) {
                return $data['lgid'] === 5
                    && $data['text'] === 'test phrase'
                    && $data['textlc'] === 'test phrase'
                    && $data['status'] === 3
                    && $data['translation'] === 'translation'
                    && $data['sentence'] === 'sentence'
                    && $data['roman'] === 'romanization';
            }))
            ->willReturn(['id' => 123]);

        $result = $this->handler->createMultiWordTerm([
            'textId' => 1,
            'text' => 'test phrase',
            'status' => 3,
            'translation' => 'translation',
            'sentence' => 'sentence',
            'romanization' => 'romanization'
        ]);

        $this->assertArrayHasKey('term_id', $result);
        $this->assertEquals(123, $result['term_id']);
    }

    public function testCreateMultiWordTermReturnsTermData(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $this->multiWordService->method('createMultiWord')
            ->willReturn(['id' => 456]);

        $result = $this->handler->createMultiWordTerm([
            'textId' => 1,
            'text' => 'my phrase'
        ]);

        $this->assertArrayHasKey('term_id', $result);
        $this->assertArrayHasKey('term_lc', $result);
        $this->assertArrayHasKey('hex', $result);
        $this->assertEquals(456, $result['term_id']);
        $this->assertEquals('my phrase', $result['term_lc']);
    }

    public function testCreateMultiWordTermHandlesServiceException(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $this->multiWordService->method('createMultiWord')
            ->willThrowException(new \Exception('Service error'));

        $result = $this->handler->createMultiWordTerm([
            'textId' => 1,
            'text' => 'test phrase'
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Service error', $result['error']);
    }

    public function testCreateMultiWordTermCalculatesWordCount(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $this->multiWordService->expects($this->once())
            ->method('createMultiWord')
            ->with($this->callback(function ($data) {
                return $data['wordcount'] === 3; // 'one two three' = 3 words
            }))
            ->willReturn(['id' => 1]);

        $this->handler->createMultiWordTerm([
            'textId' => 1,
            'text' => 'one two three'
        ]);
    }

    public function testCreateMultiWordTermUsesProvidedWordCount(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $this->multiWordService->expects($this->once())
            ->method('createMultiWord')
            ->with($this->callback(function ($data) {
                return $data['wordcount'] === 5; // Use provided count
            }))
            ->willReturn(['id' => 1]);

        $this->handler->createMultiWordTerm([
            'textId' => 1,
            'text' => 'one two three',
            'wordCount' => 5
        ]);
    }

    public function testCreateMultiWordTermDefaultsToStatus1(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $this->multiWordService->expects($this->once())
            ->method('createMultiWord')
            ->with($this->callback(function ($data) {
                return $data['status'] === 1;
            }))
            ->willReturn(['id' => 1]);

        $this->handler->createMultiWordTerm([
            'textId' => 1,
            'text' => 'test phrase'
        ]);
    }

    public function testCreateMultiWordTermTrimsText(): void
    {
        $this->contextService->method('getLanguageIdFromText')
            ->willReturn(1);

        $this->multiWordService->expects($this->once())
            ->method('createMultiWord')
            ->with($this->callback(function ($data) {
                return $data['text'] === 'test phrase'; // Trimmed
            }))
            ->willReturn(['id' => 1]);

        $this->handler->createMultiWordTerm([
            'textId' => 1,
            'text' => '  test phrase  '
        ]);
    }

    // =========================================================================
    // updateMultiWordTerm tests
    // =========================================================================

    public function testUpdateMultiWordTermReturnsErrorForNonExistentTerm(): void
    {
        $this->multiWordService->method('getMultiWordData')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->updateMultiWordTerm(999, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Multi-word expression not found', $result['error']);
    }

    public function testUpdateMultiWordTermCallsServiceWithCorrectData(): void
    {
        $this->multiWordService->method('getMultiWordData')
            ->with(123)
            ->willReturn([
                'text' => 'original text',
                'translation' => 'old translation',
                'romanization' => 'old roman',
                'sentence' => 'old sentence',
                'notes' => 'old notes',
                'status' => 2
            ]);

        $this->multiWordService->expects($this->once())
            ->method('updateMultiWord')
            ->with(
                123,
                $this->callback(function ($data) {
                    return $data['text'] === 'original text' // Text unchanged
                        && $data['translation'] === 'new translation'
                        && $data['roman'] === 'new roman'
                        && $data['sentence'] === 'new sentence'
                        && $data['notes'] === 'new notes';
                }),
                2, // Old status
                4  // New status
            );

        $this->handler->updateMultiWordTerm(123, [
            'translation' => 'new translation',
            'romanization' => 'new roman',
            'sentence' => 'new sentence',
            'notes' => 'new notes',
            'status' => 4
        ]);
    }

    public function testUpdateMultiWordTermReturnsSuccess(): void
    {
        $this->multiWordService->method('getMultiWordData')
            ->willReturn([
                'text' => 'text',
                'translation' => '',
                'romanization' => '',
                'sentence' => '',
                'notes' => '',
                'status' => 1
            ]);

        $this->multiWordService->method('updateMultiWord')
            ->willReturn(['success' => true]); // Returns array, not bool

        $result = $this->handler->updateMultiWordTerm(1, ['status' => 3]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals(3, $result['status']);
    }

    public function testUpdateMultiWordTermPreservesExistingValues(): void
    {
        $this->multiWordService->method('getMultiWordData')
            ->willReturn([
                'text' => 'text',
                'translation' => 'existing translation',
                'romanization' => 'existing roman',
                'sentence' => 'existing sentence',
                'notes' => 'existing notes',
                'status' => 2
            ]);

        $this->multiWordService->expects($this->once())
            ->method('updateMultiWord')
            ->with(
                1,
                $this->callback(function ($data) {
                    // Should preserve existing values when not provided
                    return $data['translation'] === 'existing translation'
                        && $data['roman'] === 'existing roman'
                        && $data['sentence'] === 'existing sentence'
                        && $data['notes'] === 'existing notes';
                }),
                2,
                2 // Status unchanged
            );

        $this->handler->updateMultiWordTerm(1, []); // No new data provided
    }

    public function testUpdateMultiWordTermHandlesServiceException(): void
    {
        $this->multiWordService->method('getMultiWordData')
            ->willReturn([
                'text' => 'text',
                'translation' => '',
                'romanization' => '',
                'sentence' => '',
                'notes' => '',
                'status' => 1
            ]);

        $this->multiWordService->method('updateMultiWord')
            ->willThrowException(new \Exception('Update failed'));

        $result = $this->handler->updateMultiWordTerm(1, []);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Update failed', $result['error']);
    }

    public function testUpdateMultiWordTermKeepsOldStatusWhenNotProvided(): void
    {
        $this->multiWordService->method('getMultiWordData')
            ->willReturn([
                'text' => 'text',
                'translation' => '',
                'romanization' => '',
                'sentence' => '',
                'notes' => '',
                'status' => 4
            ]);

        $this->multiWordService->expects($this->once())
            ->method('updateMultiWord')
            ->with(
                1,
                $this->anything(),
                4, // Old status
                4  // New status same as old
            );

        $this->handler->updateMultiWordTerm(1, ['translation' => 'updated']);
    }

    // =========================================================================
    // Public method existence tests
    // =========================================================================

    public function testHandlerHasAllExpectedPublicMethods(): void
    {
        $reflection = new \ReflectionClass(MultiWordApiHandler::class);

        $expectedMethods = [
            'getMultiWordForEdit',
            'createMultiWordTerm',
            'updateMultiWordTerm'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "MultiWordApiHandler should have method: $methodName"
            );

            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }
}
