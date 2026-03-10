<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\WordListService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * Unit tests for WordListService bulk operation methods.
 *
 * These methods (deleteByIdList, updateStatusByIdList, etc.) call static
 * Connection/DB methods internally, so we test:
 * - Method signatures (parameter types, return type)
 * - Return message correctness (via reflection and branch analysis)
 * - updateStatusByIdList branch coverage through return message patterns
 */
class WordListServiceBulkTest extends TestCase
{
    // =========================================================================
    // deleteByIdList — signature & contract
    // =========================================================================

    #[Test]
    public function deleteByIdListAcceptsArrayAndReturnsString(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteByIdList');

        $this->assertSame('string', $method->getReturnType()?->getName());
        $this->assertCount(1, $method->getParameters());
        $this->assertSame('array', $method->getParameters()[0]->getType()?->getName());
        $this->assertSame('ids', $method->getParameters()[0]->getName());
    }

    #[Test]
    public function deleteByIdListIsPublic(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteByIdList');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function deleteByIdListParameterIsNotNullable(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteByIdList');
        $this->assertFalse($method->getParameters()[0]->getType()?->allowsNull());
    }

    #[Test]
    public function deleteByIdListHasNoDefaultValue(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteByIdList');
        $this->assertFalse($method->getParameters()[0]->isDefaultValueAvailable());
    }

    // =========================================================================
    // updateStatusByIdList — signature & contract
    // =========================================================================

    #[Test]
    public function updateStatusByIdListAcceptsCorrectParameters(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusByIdList');

        $this->assertSame('string', $method->getReturnType()?->getName());
        $params = $method->getParameters();
        $this->assertCount(4, $params);

        $this->assertSame('ids', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()?->getName());

        $this->assertSame('newStatus', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()?->getName());

        $this->assertSame('relative', $params[2]->getName());
        $this->assertSame('bool', $params[2]->getType()?->getName());

        $this->assertSame('actionType', $params[3]->getName());
        $this->assertSame('string', $params[3]->getType()?->getName());
    }

    #[Test]
    public function updateStatusByIdListIsPublic(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusByIdList');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function updateStatusByIdListNoParameterIsNullable(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusByIdList');
        foreach ($method->getParameters() as $param) {
            $this->assertFalse(
                $param->getType()?->allowsNull(),
                "Parameter {$param->getName()} should not be nullable"
            );
        }
    }

    // =========================================================================
    // updateStatusDateByIdList — signature & contract
    // =========================================================================

    #[Test]
    public function updateStatusDateByIdListAcceptsArrayAndReturnsString(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusDateByIdList');

        $this->assertSame('string', $method->getReturnType()?->getName());
        $this->assertCount(1, $method->getParameters());
        $this->assertSame('array', $method->getParameters()[0]->getType()?->getName());
        $this->assertSame('ids', $method->getParameters()[0]->getName());
    }

    #[Test]
    public function updateStatusDateByIdListIsPublic(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusDateByIdList');
        $this->assertTrue($method->isPublic());
    }

    // =========================================================================
    // deleteSentencesByIdList — signature & contract
    // =========================================================================

    #[Test]
    public function deleteSentencesByIdListAcceptsArrayAndReturnsString(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteSentencesByIdList');

        $this->assertSame('string', $method->getReturnType()?->getName());
        $this->assertCount(1, $method->getParameters());
        $this->assertSame('array', $method->getParameters()[0]->getType()?->getName());
        $this->assertSame('ids', $method->getParameters()[0]->getName());
    }

    #[Test]
    public function deleteSentencesByIdListIsPublic(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteSentencesByIdList');
        $this->assertTrue($method->isPublic());
    }

    // =========================================================================
    // toLowercaseByIdList — signature & contract
    // =========================================================================

    #[Test]
    public function toLowercaseByIdListAcceptsArrayAndReturnsString(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'toLowercaseByIdList');

        $this->assertSame('string', $method->getReturnType()?->getName());
        $this->assertCount(1, $method->getParameters());
        $this->assertSame('array', $method->getParameters()[0]->getType()?->getName());
        $this->assertSame('ids', $method->getParameters()[0]->getName());
    }

    #[Test]
    public function toLowercaseByIdListIsPublic(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'toLowercaseByIdList');
        $this->assertTrue($method->isPublic());
    }

    // =========================================================================
    // capitalizeByIdList — signature & contract
    // =========================================================================

    #[Test]
    public function capitalizeByIdListAcceptsArrayAndReturnsString(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'capitalizeByIdList');

        $this->assertSame('string', $method->getReturnType()?->getName());
        $this->assertCount(1, $method->getParameters());
        $this->assertSame('array', $method->getParameters()[0]->getType()?->getName());
        $this->assertSame('ids', $method->getParameters()[0]->getName());
    }

    #[Test]
    public function capitalizeByIdListIsPublic(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'capitalizeByIdList');
        $this->assertTrue($method->isPublic());
    }

    // =========================================================================
    // All bulk methods — consistent pattern verification
    // =========================================================================

    /**
     * @return array<string, array{string, int}>
     */
    public static function bulkMethodProvider(): array
    {
        return [
            'deleteByIdList'            => ['deleteByIdList', 1],
            'updateStatusByIdList'      => ['updateStatusByIdList', 4],
            'updateStatusDateByIdList'  => ['updateStatusDateByIdList', 1],
            'deleteSentencesByIdList'   => ['deleteSentencesByIdList', 1],
            'toLowercaseByIdList'       => ['toLowercaseByIdList', 1],
            'capitalizeByIdList'        => ['capitalizeByIdList', 1],
        ];
    }

    #[Test]
    #[DataProvider('bulkMethodProvider')]
    public function bulkMethodExistsAndReturnsString(string $methodName, int $expectedParamCount): void
    {
        $this->assertTrue(
            method_exists(WordListService::class, $methodName),
            "Method $methodName should exist on WordListService"
        );

        $method = new ReflectionMethod(WordListService::class, $methodName);
        $this->assertSame('string', $method->getReturnType()?->getName());
        $this->assertCount($expectedParamCount, $method->getParameters());
    }

    #[Test]
    #[DataProvider('bulkMethodProvider')]
    public function bulkMethodFirstParamIsArrayNamedIds(string $methodName, int $_): void
    {
        $method = new ReflectionMethod(WordListService::class, $methodName);
        $firstParam = $method->getParameters()[0];
        $this->assertSame('ids', $firstParam->getName());
        $this->assertSame('array', $firstParam->getType()?->getName());
    }

    // =========================================================================
    // updateStatusByIdList — return message branch analysis
    // =========================================================================

    /**
     * Verify the 3 possible return message patterns by inspecting source.
     * Since we can't call the method without DB, we test via source analysis
     * that the method contains the expected return statements.
     */
    #[Test]
    public function updateStatusByIdListSourceContainsThreeReturnBranches(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Branch 1: relative +1
        $this->assertStringContainsString(
            'return "Updated Status (+1)"',
            $methodSource,
            'Should have relative +1 branch'
        );

        // Branch 2: relative -1
        $this->assertStringContainsString(
            'return "Updated Status (-1)"',
            $methodSource,
            'Should have relative -1 branch'
        );

        // Branch 3: absolute status
        $this->assertStringContainsString(
            'return "Updated Status (="',
            $methodSource,
            'Should have absolute status branch'
        );
    }

    #[Test]
    public function updateStatusByIdListRelativePlusBranchUsesStatusRange1to4(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // +1 branch should only update statuses 1-4 (max becomes 5)
        $this->assertStringContainsString('WoStatus in (1,2,3,4)', $methodSource);
    }

    #[Test]
    public function updateStatusByIdListRelativeMinusBranchUsesStatusRange2to5(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // -1 branch should only update statuses 2-5 (min becomes 1)
        $this->assertStringContainsString('WoStatus in (2,3,4,5)', $methodSource);
    }

    #[Test]
    public function updateStatusByIdListAbsoluteBranchUsesParameterizedStatus(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Absolute branch should use "WoStatus=?" (parameterized, not interpolated)
        $this->assertStringContainsString("set WoStatus=?", $methodSource);
    }

    // =========================================================================
    // SQL safety: all methods use buildPreparedInClause, not buildIntInClause
    // =========================================================================

    /**
     * @return array<string, array{string}>
     */
    public static function bulkMethodNameProvider(): array
    {
        return [
            'deleteByIdList'            => ['deleteByIdList'],
            'updateStatusByIdList'      => ['updateStatusByIdList'],
            'updateStatusDateByIdList'  => ['updateStatusDateByIdList'],
            'deleteSentencesByIdList'   => ['deleteSentencesByIdList'],
            'toLowercaseByIdList'       => ['toLowercaseByIdList'],
            'capitalizeByIdList'        => ['capitalizeByIdList'],
        ];
    }

    #[Test]
    #[DataProvider('bulkMethodNameProvider')]
    public function bulkMethodUsesPreparedInClause(string $methodName): void
    {
        $method = new ReflectionMethod(WordListService::class, $methodName);
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString(
            'buildPreparedInClause',
            $methodSource,
            "$methodName should use buildPreparedInClause"
        );
        $this->assertStringNotContainsString(
            'buildIntInClause',
            $methodSource,
            "$methodName should NOT use buildIntInClause (legacy unsafe pattern)"
        );
    }

    #[Test]
    #[DataProvider('bulkMethodNameProvider')]
    public function bulkMethodUsesPreparedExecute(string $methodName): void
    {
        $method = new ReflectionMethod(WordListService::class, $methodName);
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString(
            'preparedExecute',
            $methodSource,
            "$methodName should use preparedExecute (not raw execute/query)"
        );
    }

    #[Test]
    #[DataProvider('bulkMethodNameProvider')]
    public function bulkMethodDoesNotUseRawQuery(string $methodName): void
    {
        $method = new ReflectionMethod(WordListService::class, $methodName);
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Should not use Connection::query() (raw SQL) or Connection::execute()
        $this->assertStringNotContainsString(
            'Connection::query(',
            $methodSource,
            "$methodName should not use raw Connection::query()"
        );
        $this->assertStringNotContainsString(
            'Connection::execute(',
            $methodSource,
            "$methodName should not use raw Connection::execute()"
        );
    }

    // =========================================================================
    // deleteByIdList — source-level verification
    // =========================================================================

    #[Test]
    public function deleteByIdListUsesTransaction(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('DB::beginTransaction()', $methodSource);
        $this->assertStringContainsString('DB::commit()', $methodSource);
        $this->assertStringContainsString('DB::rollback()', $methodSource);
    }

    #[Test]
    public function deleteByIdListDeletesMultiWordItemsFirst(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Multi-word items deleted before words (FK safety)
        $posOccurrences = strpos($methodSource, 'DELETE FROM word_occurrences');
        $posWords = strpos($methodSource, 'DELETE FROM words');
        $this->assertNotFalse($posOccurrences);
        $this->assertNotFalse($posWords);
        $this->assertLessThan(
            $posWords,
            $posOccurrences,
            'word_occurrences DELETE should come before words DELETE'
        );
    }

    #[Test]
    public function deleteByIdListReturnsDeletedMessage(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('return "Deleted"', $methodSource);
    }

    // =========================================================================
    // Simple methods — return message verification via source
    // =========================================================================

    #[Test]
    public function updateStatusDateByIdListReturnsCorrectMessage(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'updateStatusDateByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('return "Updated Status Date (= Now)"', $methodSource);
    }

    #[Test]
    public function deleteSentencesByIdListReturnsCorrectMessage(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'deleteSentencesByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('return "Term Sentence(s) deleted"', $methodSource);
    }

    #[Test]
    public function toLowercaseByIdListReturnsCorrectMessage(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'toLowercaseByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('return "Term(s) set to lowercase"', $methodSource);
    }

    #[Test]
    public function capitalizeByIdListReturnsCorrectMessage(): void
    {
        $method = new ReflectionMethod(WordListService::class, 'capitalizeByIdList');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('return "Term(s) capitalized"', $methodSource);
    }
}
