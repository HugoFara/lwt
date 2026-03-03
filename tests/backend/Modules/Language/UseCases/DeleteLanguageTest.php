<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\UseCases;

use Lwt\Modules\Language\Application\UseCases\DeleteLanguage;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DeleteLanguage use case.
 *
 * Note: DeleteLanguage uses QueryBuilder directly (static calls),
 * so full behavioral tests require integration tests with a database.
 * This file tests method signatures, return type structure, and
 * verifiable logic paths using mocked QueryBuilder where possible.
 *
 * @covers \Lwt\Modules\Language\Application\UseCases\DeleteLanguage
 */
class DeleteLanguageTest extends TestCase
{
    private DeleteLanguage $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = new DeleteLanguage();
    }

    // =========================================================================
    // Class instantiation
    // =========================================================================

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DeleteLanguage::class, $this->useCase);
    }

    // =========================================================================
    // execute() — Return type structure
    // =========================================================================

    /**
     * execute() should return an array with keys: success, count, error.
     *
     * We mock QueryBuilder to avoid real DB calls. Since QueryBuilder uses
     * static methods, we use a closure-based approach via class extension.
     */
    public function testExecuteReturnTypeHasExpectedKeys(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 5,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->execute(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * execute() should return failure when texts exist for the language.
     */
    public function testExecuteReturnsFailureWhenTextsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 3,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->execute(1);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('texts', $result['error']);
    }

    /**
     * execute() should return failure when archived texts exist.
     */
    public function testExecuteReturnsFailureWhenArchivedTextsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 2,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->execute(1);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    /**
     * execute() should return failure when words exist for the language.
     */
    public function testExecuteReturnsFailureWhenWordsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 10,
            'feeds' => 0,
        ]);

        $result = $mock->execute(1);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    /**
     * execute() should return failure when feeds exist for the language.
     */
    public function testExecuteReturnsFailureWhenFeedsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 1,
        ]);

        $result = $mock->execute(1);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    /**
     * execute() should return failure when multiple types of related data exist.
     */
    public function testExecuteReturnsFailureWhenMultipleRelatedDataExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 5,
            'archivedTexts' => 3,
            'words' => 20,
            'feeds' => 2,
        ]);

        $result = $mock->execute(1);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
        $this->assertIsString($result['error']);
    }

    // =========================================================================
    // canDelete() Tests
    // =========================================================================

    /**
     * canDelete() should return true when no related data exists.
     */
    public function testCanDeleteReturnsTrueWhenNoRelatedData(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->canDelete(1);

        $this->assertTrue($result);
    }

    /**
     * canDelete() should return false when texts exist.
     */
    public function testCanDeleteReturnsFalseWhenTextsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 1,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->canDelete(1);

        $this->assertFalse($result);
    }

    /**
     * canDelete() should return false when archived texts exist.
     */
    public function testCanDeleteReturnsFalseWhenArchivedTextsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 1,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->canDelete(1);

        $this->assertFalse($result);
    }

    /**
     * canDelete() should return false when words exist.
     */
    public function testCanDeleteReturnsFalseWhenWordsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 1,
            'feeds' => 0,
        ]);

        $result = $mock->canDelete(1);

        $this->assertFalse($result);
    }

    /**
     * canDelete() should return false when feeds exist.
     */
    public function testCanDeleteReturnsFalseWhenFeedsExist(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 1,
        ]);

        $result = $mock->canDelete(1);

        $this->assertFalse($result);
    }

    /**
     * canDelete() returns bool type.
     */
    public function testCanDeleteReturnsBool(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $this->assertIsBool($mock->canDelete(1));
    }

    // =========================================================================
    // getRelatedDataCounts() — Structure
    // =========================================================================

    /**
     * getRelatedDataCounts() should return an array with the expected keys.
     */
    public function testGetRelatedDataCountsHasExpectedKeys(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->getRelatedDataCounts(1);

        $this->assertArrayHasKey('texts', $result);
        $this->assertArrayHasKey('archivedTexts', $result);
        $this->assertArrayHasKey('words', $result);
        $this->assertArrayHasKey('feeds', $result);
    }

    /**
     * getRelatedDataCounts() values should all be integers.
     */
    public function testGetRelatedDataCountsValuesAreIntegers(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 5,
            'archivedTexts' => 3,
            'words' => 100,
            'feeds' => 2,
        ]);

        $result = $mock->getRelatedDataCounts(1);

        $this->assertIsInt($result['texts']);
        $this->assertIsInt($result['archivedTexts']);
        $this->assertIsInt($result['words']);
        $this->assertIsInt($result['feeds']);
    }

    /**
     * getRelatedDataCounts() returns exact four keys, no more no less.
     */
    public function testGetRelatedDataCountsHasExactlyFourKeys(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->getRelatedDataCounts(1);

        $this->assertCount(4, $result);
    }

    /**
     * getRelatedDataCounts() preserves the provided count values.
     */
    public function testGetRelatedDataCountsPreservesValues(): void
    {
        $counts = [
            'texts' => 7,
            'archivedTexts' => 12,
            'words' => 50,
            'feeds' => 3,
        ];

        $mock = $this->createDeleteLanguageWithMockedCounts($counts);

        $result = $mock->getRelatedDataCounts(42);

        $this->assertEquals(7, $result['texts']);
        $this->assertEquals(12, $result['archivedTexts']);
        $this->assertEquals(50, $result['words']);
        $this->assertEquals(3, $result['feeds']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * execute() error message mentions required deletion steps.
     */
    public function testExecuteErrorMessageIsDescriptive(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 1,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $result = $mock->execute(1);

        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('delete', strtolower($result['error']));
    }

    /**
     * Methods accept zero as language ID without errors.
     */
    public function testMethodsAcceptZeroId(): void
    {
        $mock = $this->createDeleteLanguageWithMockedCounts([
            'texts' => 0,
            'archivedTexts' => 0,
            'words' => 0,
            'feeds' => 0,
        ]);

        $this->assertTrue($mock->canDelete(0));

        $counts = $mock->getRelatedDataCounts(0);
        $this->assertCount(4, $counts);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a DeleteLanguage subclass with getRelatedDataCounts() overridden
     * to return the given counts, avoiding real database calls.
     *
     * @param array{texts: int, archivedTexts: int, words: int, feeds: int} $counts
     *
     * @return DeleteLanguage
     */
    private function createDeleteLanguageWithMockedCounts(array $counts): DeleteLanguage
    {
        return new class ($counts) extends DeleteLanguage {
            /** @var array{texts: int, archivedTexts: int, words: int, feeds: int} */
            private array $mockedCounts;

            /**
             * @param array{texts: int, archivedTexts: int, words: int, feeds: int} $counts
             */
            public function __construct(array $counts)
            {
                $this->mockedCounts = $counts;
            }

            /**
             * @return array{texts: int, archivedTexts: int, words: int, feeds: int}
             */
            public function getRelatedDataCounts(int $id): array
            {
                return $this->mockedCounts;
            }
        };
    }
}
