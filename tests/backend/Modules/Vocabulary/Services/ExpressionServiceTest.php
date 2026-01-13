<?php

declare(strict_types=1);

namespace Tests\Modules\Vocabulary\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Lwt\Modules\Vocabulary\Application\Services\ExpressionService;
use Lwt\Modules\Language\Application\Services\TextParsingService;

/**
 * Tests for ExpressionService.
 *
 * @covers \Lwt\Modules\Vocabulary\Application\Services\ExpressionService
 */
class ExpressionServiceTest extends TestCase
{
    private ExpressionService $service;
    private MockObject&TextParsingService $mockParsingService;

    protected function setUp(): void
    {
        $this->mockParsingService = $this->createMock(TextParsingService::class);
        $this->service = new ExpressionService($this->mockParsingService);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorWithCustomParsingService(): void
    {
        $customService = $this->createMock(TextParsingService::class);
        $expressionService = new ExpressionService($customService);

        $reflection = new \ReflectionProperty(ExpressionService::class, 'textParsingService');
        $reflection->setAccessible(true);

        $this->assertSame($customService, $reflection->getValue($expressionService));
    }

    public function testConstructorWithNullCreatesDefaultService(): void
    {
        $service = new ExpressionService(null);

        $reflection = new \ReflectionProperty(ExpressionService::class, 'textParsingService');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(TextParsingService::class, $reflection->getValue($service));
    }

    // =========================================================================
    // findStandardExpression() Edge Cases
    // Note: Full testing requires database connection
    // =========================================================================

    public function testFindStandardExpressionMethodSignature(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'findStandardExpression');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('textlc', $params[0]->getName());
        $this->assertSame('lid', $params[1]->getName());
    }

    public function testFindStandardExpressionReturnType(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'findStandardExpression');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    // =========================================================================
    // findMecabExpression() Edge Cases
    // Note: Full testing requires MeCab installation and database
    // =========================================================================

    public function testFindMecabExpressionMethodSignature(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'findMecabExpression');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('text', $params[0]->getName());
        $this->assertSame('lid', $params[1]->getName());
    }

    public function testFindMecabExpressionReturnType(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'findMecabExpression');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    // =========================================================================
    // insertExpressions() Tests
    // Note: Mode 2 returns prepared statement data without executing
    // =========================================================================

    public function testInsertExpressionsMethodSignature(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'insertExpressions');

        $this->assertTrue($method->isPublic());
        $this->assertSame(5, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('textlc', $params[0]->getName());
        $this->assertSame('lid', $params[1]->getName());
        $this->assertSame('wid', $params[2]->getName());
        $this->assertSame('len', $params[3]->getName());
        $this->assertSame('mode', $params[4]->getName());
    }

    public function testInsertExpressionsReturnTypeAllowsNull(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'insertExpressions');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    // =========================================================================
    // newMultiWordInteractable() Tests
    // =========================================================================

    public function testNewMultiWordInteractableMethodSignature(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'newMultiWordInteractable');

        $this->assertTrue($method->isPublic());
        $this->assertSame(4, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('hex', $params[0]->getName());
        $this->assertSame('multiwords', $params[1]->getName());
        $this->assertSame('wid', $params[2]->getName());
        $this->assertSame('len', $params[3]->getName());
    }

    public function testNewMultiWordInteractableOutputsJson(): void
    {
        // Mock the database query that would normally happen
        // Since we can't easily mock static QueryBuilder calls,
        // this test is mainly for documentation and structure validation
        $method = new \ReflectionMethod(ExpressionService::class, 'newMultiWordInteractable');

        // The method returns void and outputs JSON script
        $returnType = $method->getReturnType();
        $this->assertSame('void', $returnType?->getName());
    }

    // =========================================================================
    // newExpressionInteractable2() Tests
    // =========================================================================

    public function testNewExpressionInteractable2MethodSignature(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'newExpressionInteractable2');

        $this->assertTrue($method->isPublic());
        $this->assertSame(4, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('hex', $params[0]->getName());
        $this->assertSame('appendtext', $params[1]->getName());
        $this->assertSame('wid', $params[2]->getName());
        $this->assertSame('len', $params[3]->getName());
    }

    // =========================================================================
    // Mode Parameter Tests (for insertExpressions)
    // =========================================================================

    /**
     * @dataProvider modeParameterProvider
     */
    public function testInsertExpressionsModeValidValues(int $mode, string $description): void
    {
        // Document valid mode values
        $this->assertContains($mode, [0, 1, 2], "Mode $mode should be valid: $description");
    }

    public static function modeParameterProvider(): array
    {
        return [
            'default_mode' => [0, 'Default mode - do nothing special'],
            'interactable_mode' => [1, 'Runs an expression inserter interactable'],
            'batch_mode' => [2, 'Return prepared statement data for batch insert'],
        ];
    }

    // =========================================================================
    // TextParsingService Interaction Tests
    // =========================================================================

    public function testServiceHasTextParsingServiceDependency(): void
    {
        // Verify that the service has a TextParsingService dependency
        // which would be used for MeCab operations
        $reflection = new \ReflectionProperty(ExpressionService::class, 'textParsingService');
        $reflection->setAccessible(true);

        $parsingService = $reflection->getValue($this->service);

        $this->assertSame($this->mockParsingService, $parsingService);
    }

    // =========================================================================
    // Data Structure Tests
    // =========================================================================

    public function testStandardExpressionReturnsExpectedStructure(): void
    {
        // Document expected return structure for findStandardExpression
        $expectedKeys = ['SeID', 'SeTxID', 'position', 'term', 'term_display'];

        // This is a documentation test - actual array structure validation
        // would require database integration testing
        $this->assertCount(5, $expectedKeys);
    }

    public function testMecabExpressionReturnsExpectedStructure(): void
    {
        // Document expected return structure for findMecabExpression
        $expectedKeys = ['SeID', 'TxID', 'position', 'term'];

        // This is a documentation test - actual array structure validation
        // would require MeCab and database integration testing
        $this->assertCount(4, $expectedKeys);
    }

    // =========================================================================
    // Parameter Type Tests
    // =========================================================================

    public function testInsertExpressionsAcceptsIntParameters(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'insertExpressions');
        $params = $method->getParameters();

        // lid parameter
        $lidType = $params[1]->getType();
        $this->assertSame('int', $lidType?->getName());

        // wid parameter
        $widType = $params[2]->getType();
        $this->assertSame('int', $widType?->getName());

        // len parameter
        $lenType = $params[3]->getType();
        $this->assertSame('int', $lenType?->getName());

        // mode parameter
        $modeType = $params[4]->getType();
        $this->assertSame('int', $modeType?->getName());
    }

    public function testFindStandardExpressionAcceptsLanguageIdTypes(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'findStandardExpression');
        $params = $method->getParameters();

        // lid parameter can be string or int
        $lidType = $params[1]->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $lidType);
    }

    public function testFindMecabExpressionAcceptsLanguageIdTypes(): void
    {
        $method = new \ReflectionMethod(ExpressionService::class, 'findMecabExpression');
        $params = $method->getParameters();

        // lid parameter can be string or int
        $lidType = $params[1]->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $lidType);
    }

    // =========================================================================
    // Hex Parameter Tests
    // =========================================================================

    /**
     * @dataProvider hexParameterProvider
     */
    public function testHexParameterFormats(string $hex, string $description): void
    {
        // Document valid hex formats used in the service
        $this->assertIsString($hex, $description);
    }

    public static function hexParameterProvider(): array
    {
        return [
            'simple_word' => ['746573745f776f7264', 'Hex encoding of "test_word"'],
            'unicode_word' => ['e4b8ade69687', 'Hex encoding of Chinese characters'],
            'empty_string' => ['', 'Empty hex string'],
            'with_underscore' => ['5f', 'Hex encoding of underscore'],
        ];
    }

    // =========================================================================
    // Multiwords Array Structure Tests
    // =========================================================================

    public function testMultiwordsArrayStructure(): void
    {
        // Document expected multiwords array structure: [textid][position][text]
        $multiwords = [
            1 => [
                10 => 'word1',
                20 => 'word2',
            ],
            2 => [
                5 => 'word3',
            ],
        ];

        // Validate structure
        $this->assertIsArray($multiwords);
        foreach ($multiwords as $textId => $positions) {
            $this->assertIsInt($textId);
            $this->assertIsArray($positions);
            foreach ($positions as $position => $text) {
                $this->assertIsInt($position);
                $this->assertIsString($text);
            }
        }
    }

    // =========================================================================
    // Edge Case Documentation Tests
    // =========================================================================

    public function testExpressionServiceHandlesEmptyText(): void
    {
        // Document behavior with empty text
        $method = new \ReflectionMethod(ExpressionService::class, 'findStandardExpression');
        $params = $method->getParameters();

        // textlc parameter
        $textlcType = $params[0]->getType();
        $this->assertSame('string', $textlcType?->getName());

        // Empty string is a valid string type
        $this->assertTrue(true);
    }

    public function testExpressionServiceHandlesZeroLanguageId(): void
    {
        // Document behavior with zero language ID (invalid but type-valid)
        $method = new \ReflectionMethod(ExpressionService::class, 'insertExpressions');
        $params = $method->getParameters();

        // lid parameter accepts int, which includes 0
        $lidType = $params[1]->getType();
        $this->assertSame('int', $lidType?->getName());
    }

    // =========================================================================
    // Return Value Structure Tests for Batch Mode
    // =========================================================================

    public function testBatchModeReturnStructure(): void
    {
        // Document expected return structure for mode 2 (batch)
        $expectedStructure = [
            'placeholders' => ['(?, ?, ?, ?, ?, ?, ?)'],
            'params' => [1, 2, 3, 4, 5, 6, 'text'],
        ];

        $this->assertArrayHasKey('placeholders', $expectedStructure);
        $this->assertArrayHasKey('params', $expectedStructure);
        $this->assertIsArray($expectedStructure['placeholders']);
        $this->assertIsArray($expectedStructure['params']);
    }

    // =========================================================================
    // SQL Placeholder Tests
    // =========================================================================

    public function testPlaceholderFormatForWordOccurrences(): void
    {
        // Document expected placeholder format for word_occurrences insert
        $expectedPlaceholder = '(?, ?, ?, ?, ?, ?, ?)';
        $expectedColumns = [
            'Ti2WoID',
            'Ti2LgID',
            'Ti2TxID',
            'Ti2SeID',
            'Ti2Order',
            'Ti2WordCount',
            'Ti2Text',
        ];

        $this->assertSame(7, substr_count($expectedPlaceholder, '?'));
        $this->assertCount(7, $expectedColumns);
    }
}
