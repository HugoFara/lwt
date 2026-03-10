<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\WordListService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class WordListServiceTest extends TestCase
{
    private WordListService $service;

    protected function setUp(): void
    {
        $this->service = new WordListService();
    }

    // =========================================================================
    // buildLangCondition()
    // =========================================================================

    #[Test]
    public function buildLangConditionReturnsEmptyForEmptyString(): void
    {
        $this->assertSame('', $this->service->buildLangCondition(''));
    }

    #[Test]
    public function buildLangConditionReturnsConditionWithoutParams(): void
    {
        $result = $this->service->buildLangCondition('5');
        $this->assertSame(' and WoLgID=5', $result);
    }

    #[Test]
    public function buildLangConditionCastsToInt(): void
    {
        $result = $this->service->buildLangCondition('abc');
        $this->assertSame(' and WoLgID=0', $result);
    }

    #[Test]
    public function buildLangConditionWithParamsUsesPlaceholder(): void
    {
        $params = [];
        $result = $this->service->buildLangCondition('7', $params);
        $this->assertSame(' and WoLgID = ?', $result);
        $this->assertSame([7], $params);
    }

    #[Test]
    public function buildLangConditionWithParamsEmptyStringReturnsEmpty(): void
    {
        $params = [];
        $result = $this->service->buildLangCondition('', $params);
        $this->assertSame('', $result);
        $this->assertSame([], $params);
    }

    #[Test]
    public function buildLangConditionWithParamsCastsToInt(): void
    {
        $params = [];
        $result = $this->service->buildLangCondition('abc', $params);
        $this->assertSame(' and WoLgID = ?', $result);
        $this->assertSame([0], $params);
    }

    // =========================================================================
    // buildStatusCondition()
    // =========================================================================

    #[Test]
    public function buildStatusConditionReturnsEmptyForEmptyString(): void
    {
        $this->assertSame('', $this->service->buildStatusCondition(''));
    }

    #[Test]
    public function buildStatusConditionSingleStatus(): void
    {
        $result = $this->service->buildStatusCondition('1');
        $this->assertSame(' and WoStatus = 1', $result);
    }

    #[Test]
    public function buildStatusConditionRange12(): void
    {
        $result = $this->service->buildStatusCondition('12');
        $this->assertSame(' and (WoStatus between 1 and 2)', $result);
    }

    #[Test]
    public function buildStatusConditionRange15(): void
    {
        $result = $this->service->buildStatusCondition('15');
        $this->assertSame(' and (WoStatus between 1 and 5)', $result);
    }

    #[Test]
    public function buildStatusConditionRange25(): void
    {
        $result = $this->service->buildStatusCondition('25');
        $this->assertSame(' and (WoStatus between 2 and 5)', $result);
    }

    #[Test]
    public function buildStatusConditionRange45(): void
    {
        $result = $this->service->buildStatusCondition('45');
        $this->assertSame(' and (WoStatus between 4 and 5)', $result);
    }

    #[Test]
    public function buildStatusCondition599(): void
    {
        $result = $this->service->buildStatusCondition('599');
        $this->assertSame(' and WoStatus in (5,99)', $result);
    }

    #[Test]
    public function buildStatusConditionIgnored(): void
    {
        $result = $this->service->buildStatusCondition('98');
        $this->assertSame(' and WoStatus = 98', $result);
    }

    #[Test]
    public function buildStatusConditionWellKnown(): void
    {
        $result = $this->service->buildStatusCondition('99');
        $this->assertSame(' and WoStatus = 99', $result);
    }

    // =========================================================================
    // buildQueryCondition() — with $params (prepared statement path)
    // =========================================================================

    #[Test]
    public function buildQueryConditionReturnsEmptyForEmptyQuery(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('', 'term', '', $params);
        $this->assertSame('', $result);
        $this->assertSame([], $params);
    }

    #[Test]
    public function buildQueryConditionTermMode(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('hello', 'term', '', $params);
        $this->assertSame(' and (WoText like ?)', $result);
        $this->assertSame(['hello'], $params);
    }

    #[Test]
    public function buildQueryConditionRomMode(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('test', 'rom', '', $params);
        $this->assertSame(" and (IFNULL(WoRomanization,'*') like ?)", $result);
        $this->assertSame(['test'], $params);
    }

    #[Test]
    public function buildQueryConditionTranslMode(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('meaning', 'transl', '', $params);
        $this->assertSame(' and (WoTranslation like ?)', $result);
        $this->assertSame(['meaning'], $params);
    }

    #[Test]
    public function buildQueryConditionTermRomMode(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('test', 'term,rom', '', $params);
        $this->assertSame(" and (WoText like ? or IFNULL(WoRomanization,'*') like ?)", $result);
        $this->assertSame(['test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionRomTranslMode(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('test', 'rom,transl', '', $params);
        $this->assertSame(" and (IFNULL(WoRomanization,'*') like ? or WoTranslation like ?)", $result);
        $this->assertSame(['test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionTermTranslMode(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('test', 'term,transl', '', $params);
        $this->assertSame(' and (WoText like ? or WoTranslation like ?)', $result);
        $this->assertSame(['test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionTermRomTranslMode(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('test', 'term,rom,transl', '', $params);
        $this->assertSame(
            " and (WoText like ? or IFNULL(WoRomanization,'*') like ? or WoTranslation like ?)",
            $result
        );
        $this->assertSame(['test', 'test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionUnknownModeFallsBackToAll(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('test', 'unknown', '', $params);
        $this->assertSame(
            " and (WoText like ? or IFNULL(WoRomanization,'*') like ? or WoTranslation like ?)",
            $result
        );
        $this->assertSame(['test', 'test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionWildcardReplacedWithPercent(): void
    {
        $params = [];
        $this->service->buildQueryCondition('hel*o', 'term', '', $params);
        $this->assertSame(['hel%o'], $params);
    }

    #[Test]
    public function buildQueryConditionMultipleWildcards(): void
    {
        $params = [];
        $this->service->buildQueryCondition('*hello*', 'term', '', $params);
        $this->assertSame(['%hello%'], $params);
    }

    #[Test]
    public function buildQueryConditionLowercasesQuery(): void
    {
        $params = [];
        $this->service->buildQueryCondition('HELLO', 'term', '', $params);
        $this->assertSame(['hello'], $params);
    }

    #[Test]
    public function buildQueryConditionRegexModeUsesRlike(): void
    {
        $params = [];
        $result = $this->service->buildQueryCondition('^test$', 'term', 'r', $params);
        $this->assertSame(' and (WoText rlike ?)', $result);
        $this->assertSame(['^test$'], $params);
    }

    #[Test]
    public function buildQueryConditionRegexDoesNotLowercase(): void
    {
        $params = [];
        $this->service->buildQueryCondition('^TEST$', 'term', 'r', $params);
        // In regex mode, the query is not lowercased (it's used as-is)
        $this->assertSame(['^TEST$'], $params);
    }

    #[Test]
    public function buildQueryConditionRegexDoesNotReplaceWildcards(): void
    {
        $params = [];
        $this->service->buildQueryCondition('te*st', 'term', 'r', $params);
        // In regex mode, * is not replaced with %
        $this->assertSame(['te*st'], $params);
    }

    #[Test]
    public function buildQueryConditionAppendsToExistingParams(): void
    {
        $params = [42, 'existing'];
        $this->service->buildQueryCondition('test', 'term', '', $params);
        $this->assertSame([42, 'existing', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionUtf8Lowercase(): void
    {
        $params = [];
        $this->service->buildQueryCondition("\xC3\x9Cber", 'term', '', $params);
        // U+00DC (Latin Capital Letter U with Diaeresis) -> lowercase
        $this->assertSame(["\xC3\xBCber"], $params);
    }

    // =========================================================================
    // buildTagCondition()
    // =========================================================================

    #[Test]
    public function buildTagConditionBothEmptyReturnsEmpty(): void
    {
        $this->assertSame('', $this->service->buildTagCondition('', '', '0'));
    }

    #[Test]
    public function buildTagConditionTag1OnlyWithoutParams(): void
    {
        $result = $this->service->buildTagCondition('5', '', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString("'%/5/%'", $result);
    }

    #[Test]
    public function buildTagConditionTag2OnlyWithoutParams(): void
    {
        $result = $this->service->buildTagCondition('', '3', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString("'%/3/%'", $result);
    }

    #[Test]
    public function buildTagConditionBothTagsWithOr(): void
    {
        $result = $this->service->buildTagCondition('5', '3', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString(') OR (', $result);
    }

    #[Test]
    public function buildTagConditionBothTagsWithAnd(): void
    {
        $result = $this->service->buildTagCondition('5', '3', '1');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString(') AND (', $result);
    }

    #[Test]
    public function buildTagConditionUntaggedTag1(): void
    {
        $result = $this->service->buildTagCondition('-1', '', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
    }

    #[Test]
    public function buildTagConditionUntaggedTag2(): void
    {
        $result = $this->service->buildTagCondition('', '-1', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
    }

    #[Test]
    public function buildTagConditionTag1WithParams(): void
    {
        $params = [];
        $result = $this->service->buildTagCondition('5', '', '0', $params);
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('?', $result);
        $this->assertSame([5], $params);
    }

    #[Test]
    public function buildTagConditionTag2WithParams(): void
    {
        $params = [];
        $result = $this->service->buildTagCondition('', '8', '0', $params);
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('?', $result);
        $this->assertSame([8], $params);
    }

    #[Test]
    public function buildTagConditionBothWithParamsOr(): void
    {
        $params = [];
        $result = $this->service->buildTagCondition('5', '3', '0', $params);
        $this->assertStringContainsString(') OR (', $result);
        $this->assertSame([5, 3], $params);
    }

    #[Test]
    public function buildTagConditionBothWithParamsAnd(): void
    {
        $params = [];
        $result = $this->service->buildTagCondition('5', '3', '1', $params);
        $this->assertStringContainsString(') AND (', $result);
        $this->assertSame([5, 3], $params);
    }

    #[Test]
    public function buildTagConditionUntaggedTag1WithParams(): void
    {
        $params = [];
        $result = $this->service->buildTagCondition('-1', '', '0', $params);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
        $this->assertSame([], $params); // -1 doesn't add a param
    }

    #[Test]
    public function buildTagConditionNonNumericTag1Ignored(): void
    {
        $result = $this->service->buildTagCondition('abc', '', '0');
        $this->assertSame('', $result);
    }

    #[Test]
    public function buildTagConditionNonNumericTag2Ignored(): void
    {
        $result = $this->service->buildTagCondition('', 'xyz', '0');
        $this->assertSame('', $result);
    }

    #[Test]
    public function buildTagConditionUntaggedBothWithAnd(): void
    {
        $params = [];
        $result = $this->service->buildTagCondition('-1', '-1', '1', $params);
        $this->assertStringContainsString(') AND (', $result);
        // Both use IS NULL, no params added
        $this->assertSame([], $params);
    }

    #[Test]
    public function buildTagConditionMixedUntaggedAndTagWithParams(): void
    {
        $params = [];
        $result = $this->service->buildTagCondition('-1', '5', '0', $params);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
        $this->assertStringContainsString(') OR (', $result);
        $this->assertSame([5], $params);
    }

    // =========================================================================
    // getTestWordIdsSql() — returns ['sql' => ..., 'params' => [...]]
    // =========================================================================

    #[Test]
    public function getTestWordIdsSqlNoTextIdReturnsArray(): void
    {
        $result = $this->service->getTestWordIdsSql('', ' and WoLgID = ?', '', '', '', [1]);
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
        $result = $this->service->getTestWordIdsSql('42', '', '', '', '');
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertStringContainsString('Ti2TxID in', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertSame([42], $result['params']);
    }

    #[Test]
    public function getTestWordIdsSqlMultipleTextIds(): void
    {
        $result = $this->service->getTestWordIdsSql('1,2,3', '', '', '', '');
        $this->assertCount(3, $result['params']);
        $this->assertSame([1, 2, 3], $result['params']);
    }

    #[Test]
    public function getTestWordIdsSqlWithFilterParams(): void
    {
        $result = $this->service->getTestWordIdsSql(
            '5',
            ' and WoLgID = ?',
            ' and WoStatus = 2',
            ' and (WoText like ?)',
            '',
            [7, 'test%']
        );
        // textId params first, then filter params
        $this->assertSame([5, 7, 'test%'], $result['params']);
    }

    #[Test]
    public function getTestWordIdsSqlEmptyFilters(): void
    {
        $result = $this->service->getTestWordIdsSql('', '', '', '', '');
        $this->assertEmpty($result['params']);
        $this->assertStringContainsString('where (1=1)', $result['sql']);
    }

    // =========================================================================
    // Export SQL methods — return ['sql' => ..., 'params' => [...]]
    // =========================================================================

    #[Test]
    public function getAnkiExportSqlWithFiltersReturnsArray(): void
    {
        $result = $this->service->getAnkiExportSql(
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
        $result = $this->service->getAnkiExportSql([10, 20, 30], '', '', '', '', '');
        $this->assertStringContainsString('WoID in', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertSame([10, 20, 30], $result['params']);
        $this->assertStringContainsString('WoTranslation', $result['sql']);
    }

    #[Test]
    public function getAnkiExportSqlWithTextId(): void
    {
        $result = $this->service->getAnkiExportSql([], '5,10', '', '', '', '');
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertStringContainsString('Ti2TxID in', $result['sql']);
        $this->assertSame([5, 10], $result['params']);
    }

    #[Test]
    public function getAnkiExportSqlWithTextIdAndFilters(): void
    {
        $result = $this->service->getAnkiExportSql(
            [],
            '5',
            ' and WoLgID = ?',
            '',
            '',
            '',
            [3]
        );
        // textId param first, then filter params
        $this->assertSame([5, 3], $result['params']);
    }

    #[Test]
    public function getTsvExportSqlWithTextIdReturnsArray(): void
    {
        $result = $this->service->getTsvExportSql([], '10', '', '', '', '');
        $this->assertIsArray($result);
        $this->assertStringContainsString('Ti2TxID in', $result['sql']);
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertSame([10], $result['params']);
    }

    #[Test]
    public function getTsvExportSqlWithIds(): void
    {
        $result = $this->service->getTsvExportSql([1, 2], '', '', '', '', '');
        $this->assertStringContainsString('WoID in', $result['sql']);
        $this->assertSame([1, 2], $result['params']);
        $this->assertStringContainsString('WoStatus', $result['sql']);
    }

    #[Test]
    public function getTsvExportSqlNoTextId(): void
    {
        $result = $this->service->getTsvExportSql(
            [],
            '',
            ' and WoLgID = ?',
            '',
            '',
            '',
            [2]
        );
        $this->assertStringContainsString('WoLgID = ?', $result['sql']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
        $this->assertSame([2], $result['params']);
    }

    #[Test]
    public function getFlexibleExportSqlNoTextIdReturnsArray(): void
    {
        $result = $this->service->getFlexibleExportSql(
            [],
            '',
            ' and WoLgID = ?',
            '',
            '',
            '',
            [3]
        );
        $this->assertIsArray($result);
        $this->assertStringContainsString('LgExportTemplate', $result['sql']);
        $this->assertStringContainsString('WoLgID = ?', $result['sql']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
        $this->assertSame([3], $result['params']);
    }

    #[Test]
    public function getFlexibleExportSqlWithIds(): void
    {
        $result = $this->service->getFlexibleExportSql([5, 6], '', '', '', '', '');
        $this->assertStringContainsString('WoID in', $result['sql']);
        $this->assertSame([5, 6], $result['params']);
        $this->assertStringContainsString('LgExportTemplate', $result['sql']);
    }

    #[Test]
    public function getFlexibleExportSqlWithTextId(): void
    {
        $result = $this->service->getFlexibleExportSql([], '7,8', '', '', '', '');
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertSame([7, 8], $result['params']);
    }

    // =========================================================================
    // Export SQL — IDs take priority over filters
    // =========================================================================

    #[Test]
    public function exportSqlIdsIgnoreFilters(): void
    {
        $result = $this->service->getAnkiExportSql(
            [1],
            '99',
            ' and WoLgID = ?',
            '',
            '',
            '',
            [5]
        );
        // When IDs are provided, textId and filters are ignored
        $this->assertSame([1], $result['params']);
        $this->assertStringNotContainsString('word_occurrences', $result['sql']);
    }

    // =========================================================================
    // Export SQL — no raw concatenation
    // =========================================================================

    #[Test]
    public function exportSqlNoRawTextIdConcatenation(): void
    {
        // Verify textId values are never directly concatenated into SQL
        $methods = ['getAnkiExportSql', 'getTsvExportSql', 'getFlexibleExportSql'];
        foreach ($methods as $method) {
            $result = $this->service->$method([], '99', '', '', '', '');
            $this->assertStringNotContainsString(
                'in (99)',
                $result['sql'],
                "$method should not concatenate textId directly"
            );
            $this->assertStringContainsString('?', $result['sql']);
        }
    }

    #[Test]
    public function getTestWordIdsSqlNoRawTextIdConcatenation(): void
    {
        $result = $this->service->getTestWordIdsSql('99', '', '', '', '');
        $this->assertStringNotContainsString('in (99)', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
    }
}
