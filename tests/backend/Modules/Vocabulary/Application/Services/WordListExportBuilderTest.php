<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\WordListExportBuilder;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * Unit tests for WordListExportBuilder.
 *
 * Tests SQL export query building for Anki, TSV, flexible, and test exports.
 * Verifies return format {sql, params} and proper parameterization.
 */
class WordListExportBuilderTest extends TestCase
{
    private WordListExportBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new WordListExportBuilder();
    }

    // =========================================================================
    // getAnkiExportSql
    // =========================================================================

    #[Test]
    public function getAnkiExportSqlWithFiltersReturnsArray(): void
    {
        $result = $this->builder->getAnkiExportSql(
            [],
            '',
            ' and WoLgID = ?',
            ' and WoStatus = 2',
            '',
            '',
            [1]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertStringContainsString('WoLgID = ?', $result['sql']);
        $this->assertStringContainsString('WoStatus = 2', $result['sql']);
        $this->assertSame([1], $result['params']);
    }

    #[Test]
    public function getAnkiExportSqlWithIds(): void
    {
        $result = $this->builder->getAnkiExportSql([10, 20, 30], '', '', '', '', '');
        $this->assertStringContainsString('WoID in', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertSame([10, 20, 30], $result['params']);
        $this->assertStringContainsString('WoTranslation', $result['sql']);
    }

    #[Test]
    public function getAnkiExportSqlWithTextId(): void
    {
        $result = $this->builder->getAnkiExportSql([], '5,10', '', '', '', '');
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertStringContainsString('Ti2TxID in', $result['sql']);
        $this->assertSame([5, 10], $result['params']);
    }

    #[Test]
    public function getAnkiExportSqlWithTextIdAndFilters(): void
    {
        $result = $this->builder->getAnkiExportSql([], '5', ' and WoLgID = ?', '', '', '', [3]);
        $this->assertSame([5, 3], $result['params']);
    }

    #[Test]
    public function getAnkiExportSqlIdsIgnoreFilters(): void
    {
        $result = $this->builder->getAnkiExportSql([1], '99', ' and WoLgID = ?', '', '', '', [5]);
        $this->assertSame([1], $result['params']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
    }

    #[Test]
    public function getAnkiExportSqlSelectsRequiredColumns(): void
    {
        $result = $this->builder->getAnkiExportSql([], '', '', '', '', '');
        $this->assertStringContainsString('WoText', $result['sql']);
        $this->assertStringContainsString('WoTranslation', $result['sql']);
        $this->assertStringContainsString('WoRomanization', $result['sql']);
        $this->assertStringContainsString('WoSentence', $result['sql']);
        $this->assertStringContainsString('taglist', $result['sql']);
    }

    #[Test]
    public function getAnkiExportSqlNoTextIdNoIds(): void
    {
        $result = $this->builder->getAnkiExportSql([], '', '', '', '', '');
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
        $this->assertEmpty($result['params']);
    }

    // =========================================================================
    // getTsvExportSql
    // =========================================================================

    #[Test]
    public function getTsvExportSqlWithTextIdReturnsArray(): void
    {
        $result = $this->builder->getTsvExportSql([], '10', '', '', '', '');
        $this->assertIsArray($result);
        $this->assertStringContainsString('Ti2TxID in', $result['sql']);
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertSame([10], $result['params']);
    }

    #[Test]
    public function getTsvExportSqlWithIds(): void
    {
        $result = $this->builder->getTsvExportSql([1, 2], '', '', '', '', '');
        $this->assertStringContainsString('WoID in', $result['sql']);
        $this->assertSame([1, 2], $result['params']);
        $this->assertStringContainsString('WoStatus', $result['sql']);
    }

    #[Test]
    public function getTsvExportSqlNoTextId(): void
    {
        $result = $this->builder->getTsvExportSql([], '', ' and WoLgID = ?', '', '', '', [2]);
        $this->assertStringContainsString('WoLgID = ?', $result['sql']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
        $this->assertSame([2], $result['params']);
    }

    #[Test]
    public function getTsvExportSqlSelectsRequiredColumns(): void
    {
        $result = $this->builder->getTsvExportSql([], '', '', '', '', '');
        $this->assertStringContainsString('WoText', $result['sql']);
        $this->assertStringContainsString('WoStatus', $result['sql']);
        $this->assertStringContainsString('LgName', $result['sql']);
        $this->assertStringContainsString('taglist', $result['sql']);
    }

    #[Test]
    public function getTsvExportSqlIdsIgnoreTextId(): void
    {
        $result = $this->builder->getTsvExportSql([5], '99', '', '', '', '');
        $this->assertSame([5], $result['params']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
    }

    // =========================================================================
    // getFlexibleExportSql
    // =========================================================================

    #[Test]
    public function getFlexibleExportSqlNoTextIdReturnsArray(): void
    {
        $result = $this->builder->getFlexibleExportSql([], '', ' and WoLgID = ?', '', '', '', [3]);
        $this->assertIsArray($result);
        $this->assertStringContainsString('LgExportTemplate', $result['sql']);
        $this->assertStringContainsString('WoLgID = ?', $result['sql']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
        $this->assertSame([3], $result['params']);
    }

    #[Test]
    public function getFlexibleExportSqlWithIds(): void
    {
        $result = $this->builder->getFlexibleExportSql([5, 6], '', '', '', '', '');
        $this->assertStringContainsString('WoID in', $result['sql']);
        $this->assertSame([5, 6], $result['params']);
        $this->assertStringContainsString('LgExportTemplate', $result['sql']);
    }

    #[Test]
    public function getFlexibleExportSqlWithTextId(): void
    {
        $result = $this->builder->getFlexibleExportSql([], '7,8', '', '', '', '');
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertSame([7, 8], $result['params']);
    }

    #[Test]
    public function getFlexibleExportSqlSelectsExportTemplate(): void
    {
        $result = $this->builder->getFlexibleExportSql([], '', '', '', '', '');
        $this->assertStringContainsString('LgExportTemplate', $result['sql']);
        $this->assertStringContainsString('LgRightToLeft', $result['sql']);
        $this->assertStringContainsString('WoTextLC', $result['sql']);
    }

    #[Test]
    public function getFlexibleExportSqlIdsIgnoreFilters(): void
    {
        $result = $this->builder->getFlexibleExportSql([1], '99', ' and WoLgID = ?', '', '', '', [5]);
        $this->assertSame([1], $result['params']);
    }

    // =========================================================================
    // getTestWordIdsSql
    // =========================================================================

    #[Test]
    public function getTestWordIdsSqlNoTextIdReturnsArray(): void
    {
        $result = $this->builder->getTestWordIdsSql('', ' and WoLgID = ?', '', '', '', [1]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertStringContainsString('select distinct WoID', $result['sql']);
        $this->assertStringContainsString('WoLgID = ?', $result['sql']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
        $this->assertSame([1], $result['params']);
    }

    #[Test]
    public function getTestWordIdsSqlWithTextIdUsesInClause(): void
    {
        $result = $this->builder->getTestWordIdsSql('42', '', '', '', '');
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertStringContainsString('Ti2TxID in', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertSame([42], $result['params']);
    }

    #[Test]
    public function getTestWordIdsSqlMultipleTextIds(): void
    {
        $result = $this->builder->getTestWordIdsSql('1,2,3', '', '', '', '');
        $this->assertCount(3, $result['params']);
        $this->assertSame([1, 2, 3], $result['params']);
    }

    #[Test]
    public function getTestWordIdsSqlWithFilterParams(): void
    {
        $result = $this->builder->getTestWordIdsSql(
            '5',
            ' and WoLgID = ?',
            ' and WoStatus = 2',
            ' and (WoText like ?)',
            '',
            [7, 'test%']
        );
        $this->assertSame([5, 7, 'test%'], $result['params']);
    }

    #[Test]
    public function getTestWordIdsSqlEmptyFilters(): void
    {
        $result = $this->builder->getTestWordIdsSql('', '', '', '', '');
        $this->assertEmpty($result['params']);
        $this->assertStringContainsString('where (1=1)', $result['sql']);
    }

    #[Test]
    public function getTestWordIdsSqlNoRawTextIdConcatenation(): void
    {
        $result = $this->builder->getTestWordIdsSql('99', '', '', '', '');
        $this->assertStringNotContainsString('in (99)', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
    }

    // =========================================================================
    // All export methods - no raw concatenation
    // =========================================================================

    #[Test]
    public function exportSqlNoRawTextIdConcatenation(): void
    {
        $methods = ['getAnkiExportSql', 'getTsvExportSql', 'getFlexibleExportSql'];
        foreach ($methods as $method) {
            $result = $this->builder->$method([], '99', '', '', '', '');
            $this->assertStringNotContainsString(
                'in (99)',
                $result['sql'],
                "$method should not concatenate textId directly"
            );
            $this->assertStringContainsString('?', $result['sql']);
        }
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    /**
     * @return array<string, array{string, int}>
     */
    public static function exportMethodProvider(): array
    {
        return [
            'getAnkiExportSql' => ['getAnkiExportSql', 7],
            'getTsvExportSql' => ['getTsvExportSql', 7],
            'getFlexibleExportSql' => ['getFlexibleExportSql', 7],
            'getTestWordIdsSql' => ['getTestWordIdsSql', 6],
        ];
    }

    #[Test]
    #[DataProvider('exportMethodProvider')]
    public function exportMethodIsPublic(string $methodName, int $_): void
    {
        $method = new ReflectionMethod(WordListExportBuilder::class, $methodName);
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    #[DataProvider('exportMethodProvider')]
    public function exportMethodReturnsArray(string $methodName, int $_): void
    {
        $method = new ReflectionMethod(WordListExportBuilder::class, $methodName);
        $this->assertSame('array', $method->getReturnType()?->getName());
    }

    #[Test]
    #[DataProvider('exportMethodProvider')]
    public function exportMethodHasCorrectParamCount(string $methodName, int $expectedParamCount): void
    {
        $method = new ReflectionMethod(WordListExportBuilder::class, $methodName);
        $this->assertCount($expectedParamCount, $method->getParameters());
    }

    #[Test]
    #[DataProvider('exportMethodProvider')]
    public function exportMethodLastParamHasDefaultEmptyArray(string $methodName, int $_): void
    {
        $method = new ReflectionMethod(WordListExportBuilder::class, $methodName);
        $params = $method->getParameters();
        $lastParam = end($params);
        $this->assertTrue($lastParam->isDefaultValueAvailable());
        $this->assertSame([], $lastParam->getDefaultValue());
    }

    // =========================================================================
    // Source-level: no raw SQL patterns
    // =========================================================================

    /**
     * @return array<string, array{string}>
     */
    public static function allMethodProvider(): array
    {
        return [
            'getAnkiExportSql' => ['getAnkiExportSql'],
            'getTsvExportSql' => ['getTsvExportSql'],
            'getFlexibleExportSql' => ['getFlexibleExportSql'],
            'getTestWordIdsSql' => ['getTestWordIdsSql'],
        ];
    }

    #[Test]
    #[DataProvider('allMethodProvider')]
    public function exportMethodUsesBuildPreparedInClause(string $methodName): void
    {
        $method = new ReflectionMethod(WordListExportBuilder::class, $methodName);
        $source = $this->getMethodSource($method);

        $this->assertStringContainsString(
            'buildPreparedInClause',
            $source,
            "$methodName should use buildPreparedInClause"
        );
    }

    #[Test]
    #[DataProvider('allMethodProvider')]
    public function exportMethodDoesNotUseBuildIntInClause(string $methodName): void
    {
        $method = new ReflectionMethod(WordListExportBuilder::class, $methodName);
        $source = $this->getMethodSource($method);

        $this->assertStringNotContainsString(
            'buildIntInClause',
            $source,
            "$methodName should NOT use buildIntInClause"
        );
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function getMethodSource(ReflectionMethod $method): string
    {
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $source = file_get_contents($filename);
        $lines = explode("\n", $source);
        return implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    }
}
