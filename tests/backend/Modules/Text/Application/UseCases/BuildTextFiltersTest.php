<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Application\UseCases;

use Lwt\Modules\Text\Application\UseCases\BuildTextFilters;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BuildTextFilters use case.
 *
 * @covers \Lwt\Modules\Text\Application\UseCases\BuildTextFilters
 */
class BuildTextFiltersTest extends TestCase
{
    private BuildTextFilters $filters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filters = new BuildTextFilters();
    }

    // =========================================================================
    // buildLangWhereClause Tests
    // =========================================================================

    public function testBuildLangWhereClauseWithEmptyString(): void
    {
        $result = $this->filters->buildLangWhereClause('');
        $this->assertSame('', $result['clause']);
        $this->assertSame([], $result['params']);
    }

    public function testBuildLangWhereClauseWithZero(): void
    {
        $result = $this->filters->buildLangWhereClause(0);
        $this->assertSame('', $result['clause']);
        $this->assertSame([], $result['params']);
    }

    public function testBuildLangWhereClauseWithValidId(): void
    {
        $result = $this->filters->buildLangWhereClause(5);
        $this->assertSame(' AND TxLgID = ?', $result['clause']);
        $this->assertSame([5], $result['params']);
    }

    public function testBuildLangWhereClauseWithStringId(): void
    {
        $result = $this->filters->buildLangWhereClause('3');
        $this->assertSame(' AND TxLgID = ?', $result['clause']);
        $this->assertSame([3], $result['params']);
    }

    public function testBuildLangWhereClauseCastsToInt(): void
    {
        $result = $this->filters->buildLangWhereClause('42');
        $this->assertIsInt($result['params'][0]);
    }

    // =========================================================================
    // buildQueryWhereClause Tests
    // =========================================================================

    public function testBuildQueryWhereClauseEmptyQuery(): void
    {
        $result = $this->filters->buildQueryWhereClause('', 'title,text', '');
        $this->assertSame('', $result['clause']);
        $this->assertSame([], $result['params']);
    }

    public function testBuildQueryWhereClauseTitleAndText(): void
    {
        $result = $this->filters->buildQueryWhereClause('hello', 'title,text', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertStringContainsString('LIKE', $result['clause']);
        $this->assertCount(2, $result['params']);
        $this->assertSame('hello', $result['params'][0]);
    }

    public function testBuildQueryWhereClauseTitleOnly(): void
    {
        $result = $this->filters->buildQueryWhereClause('test', 'title', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringNotContainsString('TxText', $result['clause']);
        $this->assertCount(1, $result['params']);
    }

    public function testBuildQueryWhereClauseTextOnly(): void
    {
        $result = $this->filters->buildQueryWhereClause('test', 'text', '');
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertStringNotContainsString('TxTitle', $result['clause']);
        $this->assertCount(1, $result['params']);
    }

    public function testBuildQueryWhereClauseRegexMode(): void
    {
        $result = $this->filters->buildQueryWhereClause('test.*', 'title', 'r');
        $this->assertStringContainsString('rLIKE', $result['clause']);
        $this->assertSame('test.*', $result['params'][0]);
    }

    public function testBuildQueryWhereClauseWildcardReplacement(): void
    {
        $result = $this->filters->buildQueryWhereClause('he*lo', 'title', '');
        $this->assertSame('he%lo', $result['params'][0]);
    }

    public function testBuildQueryWhereClauseLowercases(): void
    {
        $result = $this->filters->buildQueryWhereClause('HELLO', 'title', '');
        $this->assertSame('hello', $result['params'][0]);
    }

    public function testBuildQueryWhereClauseDefaultMode(): void
    {
        $result = $this->filters->buildQueryWhereClause('test', 'unknown_mode', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertCount(2, $result['params']);
    }

    public function testBuildQueryWhereClauseStartsWithAnd(): void
    {
        $result = $this->filters->buildQueryWhereClause('test', 'title', '');
        $this->assertStringStartsWith(' AND', $result['clause']);
    }

    public function testBuildQueryWhereClauseRegexPreservesCase(): void
    {
        $result = $this->filters->buildQueryWhereClause('UPPER', 'title', 'r');
        $this->assertSame('UPPER', $result['params'][0]);
    }

    // =========================================================================
    // buildArchivedQueryWhereClause Tests
    // =========================================================================

    public function testBuildArchivedQueryWhereClauseDelegatesToBuildQueryWhereClause(): void
    {
        $result = $this->filters->buildArchivedQueryWhereClause('test', 'title', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertCount(1, $result['params']);
    }

    public function testBuildArchivedQueryWhereClauseEmptyQuery(): void
    {
        $result = $this->filters->buildArchivedQueryWhereClause('', 'title', '');
        $this->assertSame('', $result['clause']);
    }

    // =========================================================================
    // buildTagHavingClause Tests
    // =========================================================================

    public function testBuildTagHavingClauseBothEmpty(): void
    {
        $result = $this->filters->buildTagHavingClause('', '', '');
        $this->assertSame('', $result);
    }

    public function testBuildTagHavingClauseSingleTag(): void
    {
        $result = $this->filters->buildTagHavingClause('5', '', '');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('5', $result);
    }

    public function testBuildTagHavingClauseUntagged(): void
    {
        $result = $this->filters->buildTagHavingClause('-1', '', '');
        $this->assertStringContainsString('IS NULL', $result);
    }

    public function testBuildTagHavingClauseTwoTagsAnd(): void
    {
        $result = $this->filters->buildTagHavingClause('1', '2', '1');
        $this->assertStringContainsString('AND', $result);
    }

    public function testBuildTagHavingClauseTwoTagsOr(): void
    {
        $result = $this->filters->buildTagHavingClause('1', '2', '');
        $this->assertStringContainsString('OR', $result);
    }

    public function testBuildTagHavingClauseSecondTagOnly(): void
    {
        $result = $this->filters->buildTagHavingClause('', '3', '');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('3', $result);
    }

    public function testBuildTagHavingClauseNonNumericIgnored(): void
    {
        $result = $this->filters->buildTagHavingClause('abc', '', '');
        $this->assertSame('', $result);
    }

    public function testBuildTagHavingClauseCustomTagIdCol(): void
    {
        $result = $this->filters->buildTagHavingClause('5', '', '', 'AgT2ID');
        $this->assertStringContainsString('AgT2ID', $result);
    }

    // =========================================================================
    // buildTextTagHavingClause / buildArchivedTagHavingClause Tests
    // =========================================================================

    public function testBuildTextTagHavingClauseDelegates(): void
    {
        $result = $this->filters->buildTextTagHavingClause('5', '', '');
        $this->assertStringContainsString('TtT2ID', $result);
    }

    public function testBuildArchivedTagHavingClauseDelegates(): void
    {
        $result = $this->filters->buildArchivedTagHavingClause('5', '', '');
        $this->assertStringContainsString('TtT2ID', $result);
    }

    // =========================================================================
    // Structure Tests
    // =========================================================================

    public function testReturnArrayStructureForQuery(): void
    {
        $result = $this->filters->buildQueryWhereClause('test', 'title', '');
        $this->assertArrayHasKey('clause', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertIsString($result['clause']);
        $this->assertIsArray($result['params']);
    }

    public function testReturnArrayStructureForLang(): void
    {
        $result = $this->filters->buildLangWhereClause(1);
        $this->assertArrayHasKey('clause', $result);
        $this->assertArrayHasKey('params', $result);
    }

    /**
     * @dataProvider queryModeProvider
     */
    public function testAllQueryModesReturnValidStructure(string $mode): void
    {
        $result = $this->filters->buildQueryWhereClause('test', $mode, '');
        $this->assertArrayHasKey('clause', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertNotEmpty($result['clause']);
        $this->assertNotEmpty($result['params']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function queryModeProvider(): array
    {
        return [
            'title,text' => ['title,text'],
            'title' => ['title'],
            'text' => ['text'],
        ];
    }

    // =========================================================================
    // buildTagHavingClausePrepared Tests (TASK-007)
    // =========================================================================

    public function testBuildTagHavingClausePreparedBothEmpty(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('', '', '');
        $this->assertSame('', $result['clause']);
        $this->assertSame([], $result['params']);
    }

    public function testBuildTagHavingClausePreparedSingleTag(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('5', '', '');
        $this->assertStringContainsString('HAVING', $result['clause']);
        $this->assertStringContainsString('CONCAT', $result['clause']);
        $this->assertStringContainsString('?', $result['clause']);
        $this->assertSame([5], $result['params']);
    }

    public function testBuildTagHavingClausePreparedUntagged(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('-1', '', '');
        $this->assertStringContainsString('IS NULL', $result['clause']);
        $this->assertSame([], $result['params']);
    }

    public function testBuildTagHavingClausePreparedTwoTagsAnd(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('1', '2', '1');
        $this->assertStringContainsString('AND', $result['clause']);
        $this->assertSame([1, 2], $result['params']);
    }

    public function testBuildTagHavingClausePreparedTwoTagsOr(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('1', '2', '');
        $this->assertStringContainsString('OR', $result['clause']);
        $this->assertSame([1, 2], $result['params']);
    }

    public function testBuildTagHavingClausePreparedSecondTagOnly(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('', '3', '');
        $this->assertStringContainsString('HAVING', $result['clause']);
        $this->assertSame([3], $result['params']);
    }

    public function testBuildTagHavingClausePreparedNonNumericIgnored(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('abc', '', '');
        $this->assertSame('', $result['clause']);
        $this->assertSame([], $result['params']);
    }

    public function testBuildTagHavingClausePreparedUntaggedAndTag(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('-1', '5', '1');
        $this->assertStringContainsString('IS NULL', $result['clause']);
        $this->assertStringContainsString('AND', $result['clause']);
        $this->assertSame([5], $result['params']); // only tag2 has a param, tag1 is IS NULL
    }

    public function testBuildTagHavingClausePreparedReturnStructure(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('1', '', '');
        $this->assertArrayHasKey('clause', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertIsString($result['clause']);
        $this->assertIsArray($result['params']);
    }

    public function testBuildTagHavingClausePreparedCustomTagIdCol(): void
    {
        $result = $this->filters->buildTagHavingClausePrepared('5', '', '', 'AgT2ID');
        $this->assertStringContainsString('AgT2ID', $result['clause']);
    }

    // =========================================================================
    // Data Provider Tests
    // =========================================================================

    /**
     * @dataProvider tagCombinationProvider
     */
    public function testTagHavingClauseVariousCombinations(
        string|int $tag1,
        string|int $tag2,
        string $tag12,
        bool $expectEmpty
    ): void {
        $result = $this->filters->buildTagHavingClause($tag1, $tag2, $tag12);
        if ($expectEmpty) {
            $this->assertSame('', $result);
        } else {
            $this->assertStringContainsString('HAVING', $result);
        }
    }

    /**
     * @return array<string, array{string|int, string|int, string, bool}>
     */
    public static function tagCombinationProvider(): array
    {
        return [
            'both empty' => ['', '', '', true],
            'first only' => ['1', '', '', false],
            'second only' => ['', '2', '', false],
            'both with AND' => ['1', '2', '1', false],
            'both with OR' => ['1', '2', '', false],
            'untagged first' => ['-1', '', '', false],
            'untagged second' => ['', '-1', '', false],
            'both untagged' => ['-1', '-1', '1', false],
            'non-numeric first' => ['abc', '', '', true],
            'non-numeric both' => ['abc', 'def', '', true],
        ];
    }
}
