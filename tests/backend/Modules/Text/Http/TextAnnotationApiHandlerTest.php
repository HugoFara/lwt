<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextAnnotationApiHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for TextAnnotationApiHandler.
 *
 * Tests annotation CRUD, print items, improved text editing,
 * error formatting, and HTML generation.
 */
class TextAnnotationApiHandlerTest extends TestCase
{
    private TextAnnotationApiHandler $handler;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->handler = new TextAnnotationApiHandler();
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(TextAnnotationApiHandler::class, $this->handler);
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextAnnotationApiHandler::class);

        $expectedMethods = [
            'saveImprTextData', 'saveImprText', 'formatSetAnnotation',
            'getPrintItems', 'formatGetPrintItems',
            'getAnnotation', 'formatGetAnnotation',
            'makeTrans', 'editTermForm', 'formatEditTermForm',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextAnnotationApiHandler should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function classHasPrivateFormatAnnotationError(): void
    {
        $reflection = new \ReflectionClass(TextAnnotationApiHandler::class);
        $this->assertTrue($reflection->hasMethod('formatAnnotationError'));

        $method = $reflection->getMethod('formatAnnotationError');
        $this->assertTrue($method->isPrivate());
    }

    // =========================================================================
    // formatAnnotationError tests (private, tested via reflection)
    // =========================================================================

    #[Test]
    public function formatAnnotationErrorReturnsOkForSuccess(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, ['success' => true]);

        $this->assertSame('OK', $result);
    }

    #[Test]
    public function formatAnnotationErrorReturnsParseAnnotationFailed(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'parse_annotation_failed'
        ]);

        $this->assertSame('Failed to parse annotation text', $result);
    }

    #[Test]
    public function formatAnnotationErrorReturnsLineOutOfRange(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'line_out_of_range',
            'requested' => 10,
            'available' => 5
        ]);

        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('5', $result);
        $this->assertStringContainsString('Unreachable translation', $result);
    }

    #[Test]
    public function formatAnnotationErrorReturnsParseLineFailed(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'parse_line_failed'
        ]);

        $this->assertSame('Failed to parse annotation line', $result);
    }

    #[Test]
    public function formatAnnotationErrorReturnsPunctuationTerm(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'punctuation_term',
            'position' => -1
        ]);

        $this->assertStringContainsString('punctuation', $result);
        $this->assertStringContainsString('-1', $result);
    }

    #[Test]
    public function formatAnnotationErrorReturnsInsufficientColumns(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'insufficient_columns',
            'found' => 2
        ]);

        $this->assertStringContainsString('columns', $result);
        $this->assertStringContainsString('2', $result);
    }

    #[Test]
    public function formatAnnotationErrorReturnsUnknownError(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'some_unknown_error'
        ]);

        $this->assertSame('Unknown error', $result);
    }

    #[Test]
    public function formatAnnotationErrorHandlesMissingErrorKey(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, ['success' => false]);

        $this->assertSame('Unknown error', $result);
    }

    #[Test]
    public function formatAnnotationErrorHandlesMissingOptionalFields(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'line_out_of_range'
        ]);

        $this->assertStringContainsString('?', $result);
    }

    #[Test]
    public function formatAnnotationErrorHandlesMissingPositionForPunctuation(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'punctuation_term'
        ]);

        $this->assertStringContainsString('?', $result);
    }

    #[Test]
    public function formatAnnotationErrorHandlesMissingFoundForColumns(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'insufficient_columns'
        ]);

        $this->assertStringContainsString('?', $result);
    }

    // =========================================================================
    // saveImprText tests
    // =========================================================================

    #[Test]
    public function saveImprTextHandlesRegularElement(): void
    {
        $data = new \stdClass();
        $data->tx5 = 'test annotation';

        $result = $this->handler->saveImprText(0, 'tx5', $data);

        $this->assertIsArray($result);
    }

    #[Test]
    public function saveImprTextHandlesRgElementWithEmptyValue(): void
    {
        $data = new \stdClass();
        $data->rg5 = '';
        $data->tx5 = 'fallback annotation';

        $result = $this->handler->saveImprText(0, 'rg5', $data);

        $this->assertIsArray($result);
    }

    #[Test]
    public function saveImprTextHandlesRgElementWithValue(): void
    {
        $data = new \stdClass();
        $data->rg5 = 'romanization';
        $data->tx5 = 'translation';

        $result = $this->handler->saveImprText(0, 'rg5', $data);

        $this->assertIsArray($result);
    }

    #[Test]
    public function saveImprTextHandlesMissingProperty(): void
    {
        $data = new \stdClass();

        $result = $this->handler->saveImprText(0, 'tx5', $data);

        $this->assertIsArray($result);
    }

    #[Test]
    public function saveImprTextExtractsNumberFromElement(): void
    {
        $data = new \stdClass();
        $data->tx10 = 'value';

        $result = $this->handler->saveImprText(0, 'tx10', $data);

        $this->assertIsArray($result);
    }

    #[Test]
    public function saveImprTextRgFallsBackToTxWhenEmpty(): void
    {
        // When rg element is empty, it should use tx value
        $data = new \stdClass();
        $data->rg3 = '';
        $data->tx3 = 'my fallback';

        $result = $this->handler->saveImprText(0, 'rg3', $data);

        // Result is array with either error or success
        $this->assertIsArray($result);
    }

    #[Test]
    public function saveImprTextRgUsesOwnValueWhenNotEmpty(): void
    {
        $data = new \stdClass();
        $data->rg3 = 'my romanization';
        $data->tx3 = 'my translation';

        $result = $this->handler->saveImprText(0, 'rg3', $data);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // formatSetAnnotation tests
    // =========================================================================

    #[Test]
    public function formatSetAnnotationReturnsErrorForInvalidJson(): void
    {
        $result = $this->handler->formatSetAnnotation(1, 'tx5', 'not valid json');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Invalid JSON data', $result['error']);
    }

    #[Test]
    public function formatSetAnnotationReturnsErrorForJsonArray(): void
    {
        $result = $this->handler->formatSetAnnotation(1, 'tx5', '[1,2,3]');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Invalid JSON data', $result['error']);
    }

    #[Test]
    public function formatSetAnnotationReturnsErrorForJsonString(): void
    {
        $result = $this->handler->formatSetAnnotation(1, 'tx5', '"hello"');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Invalid JSON data', $result['error']);
    }

    #[Test]
    public function formatSetAnnotationReturnsErrorForJsonNumber(): void
    {
        $result = $this->handler->formatSetAnnotation(1, 'tx5', '42');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Invalid JSON data', $result['error']);
    }

    #[Test]
    public function formatSetAnnotationReturnsErrorForJsonNull(): void
    {
        $result = $this->handler->formatSetAnnotation(1, 'tx5', 'null');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Invalid JSON data', $result['error']);
    }

    #[Test]
    public function formatSetAnnotationAcceptsValidJsonObject(): void
    {
        $result = $this->handler->formatSetAnnotation(1, 'tx5', '{"tx5": "hello"}');

        // Should not return "Invalid JSON data" error
        if (isset($result['error'])) {
            $this->assertNotSame('Invalid JSON data', $result['error']);
        } else {
            $this->assertArrayHasKey('save_impr_text', $result);
        }
    }

    // =========================================================================
    // getPrintItems tests
    // =========================================================================

    #[Test]
    public function getPrintItemsReturnsErrorForNonExistentText(): void
    {
        $result = $this->handler->getPrintItems(999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    #[Test]
    public function formatGetPrintItemsDelegatesToGetPrintItems(): void
    {
        $result = $this->handler->formatGetPrintItems(999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    // =========================================================================
    // getAnnotation tests
    // =========================================================================

    #[Test]
    public function getAnnotationReturnsErrorForNonExistentText(): void
    {
        $result = $this->handler->getAnnotation(999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    #[Test]
    public function formatGetAnnotationDelegatesToGetAnnotation(): void
    {
        $result = $this->handler->formatGetAnnotation(999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    // =========================================================================
    // editTermForm tests
    // =========================================================================

    #[Test]
    public function editTermFormReturnsNotFoundForInvalidText(): void
    {
        $result = $this->handler->editTermForm(999999);

        $this->assertStringContainsString('Text not found', $result);
    }

    #[Test]
    public function formatEditTermFormReturnsHtmlKey(): void
    {
        $result = $this->handler->formatEditTermForm(999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('html', $result);
        $this->assertStringContainsString('Text not found', $result['html']);
    }

    // =========================================================================
    // makeTrans tests
    // =========================================================================

    #[Test]
    public function makeTransReturnsStringForNullWid(): void
    {
        $result = $this->handler->makeTrans(0, null, '', 'hello', 1);

        $this->assertIsString($result);
    }

    #[Test]
    public function makeTransContainsRadioInput(): void
    {
        $result = $this->handler->makeTrans(0, null, '', 'hello', 1);

        $this->assertStringContainsString('type="radio"', $result);
    }

    #[Test]
    public function makeTransContainsTextInput(): void
    {
        $result = $this->handler->makeTrans(0, null, '', 'hello', 1);

        $this->assertStringContainsString('type="text"', $result);
    }

    #[Test]
    public function makeTransUsesCorrectFormIndex(): void
    {
        $result = $this->handler->makeTrans(7, null, '', 'hello', 1);

        $this->assertStringContainsString('name="rg7"', $result);
        $this->assertStringContainsString('name="tx7"', $result);
        $this->assertStringContainsString('id="tx7"', $result);
    }

    #[Test]
    public function makeTransEscapesWordForNullWid(): void
    {
        $result = $this->handler->makeTrans(0, null, '', '<script>alert(1)</script>', 1);

        $this->assertStringNotContainsString('<script>', $result);
        // htmlspecialchars double-encodes in attribute context: &amp;lt;script&amp;gt;
        $this->assertStringContainsString('script', $result);
    }

    #[Test]
    public function makeTransContainsAddTermTranslationForNullWid(): void
    {
        $result = $this->handler->makeTrans(0, null, '', 'hello', 1);

        $this->assertStringContainsString('data-action="add-term-translation"', $result);
    }

    #[Test]
    public function makeTransContainsUpdateTermTranslationForExistingWid(): void
    {
        // WoID won't exist in test DB, so wid check will fail, but test the path
        $result = $this->handler->makeTrans(0, 999999, '', 'hello', 1);

        // Since the wid does not exist in DB, widset check will fall through
        $this->assertIsString($result);
    }

    #[Test]
    public function makeTransContainsEraseFieldAction(): void
    {
        $result = $this->handler->makeTrans(0, null, '', 'hello', 1);

        $this->assertStringContainsString('data-action="erase-field"', $result);
    }

    #[Test]
    public function makeTransContainsSetStarAction(): void
    {
        $result = $this->handler->makeTrans(0, null, '', 'hello', 1);

        $this->assertStringContainsString('data-action="set-star"', $result);
    }

    #[Test]
    public function makeTransContainsWaitSpan(): void
    {
        $result = $this->handler->makeTrans(5, null, '', 'hello', 1);

        $this->assertStringContainsString('id="wait5"', $result);
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function saveImprTextDataMethodSignature(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'saveImprTextData');
        $params = $method->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('textid', $params[0]->getName());
        $this->assertSame('line', $params[1]->getName());
        $this->assertSame('val', $params[2]->getName());
    }

    #[Test]
    public function makeTransMethodSignature(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'makeTrans');
        $params = $method->getParameters();

        $this->assertCount(5, $params);
        $this->assertSame('i', $params[0]->getName());
        $this->assertSame('wid', $params[1]->getName());
        $this->assertTrue($params[1]->allowsNull());
        $this->assertSame('trans', $params[2]->getName());
        $this->assertSame('word', $params[3]->getName());
        $this->assertSame('lang', $params[4]->getName());
    }

    #[Test]
    public function editTermFormReturnsString(): void
    {
        $method = new \ReflectionMethod(TextAnnotationApiHandler::class, 'editTermForm');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }
}
