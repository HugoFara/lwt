<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\WordListFilterBuilder;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * Unit tests for WordListFilterBuilder.
 *
 * Tests filter condition building for language, status, query text,
 * regex validation, and tag filters.
 */
class WordListFilterBuilderTest extends TestCase
{
    private WordListFilterBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new WordListFilterBuilder();
    }

    // =========================================================================
    // buildLangCondition()
    // =========================================================================

    #[Test]
    public function buildLangConditionReturnsEmptyForEmptyString(): void
    {
        $this->assertSame('', $this->builder->buildLangCondition(''));
    }

    #[Test]
    public function buildLangConditionReturnsConditionWithoutParams(): void
    {
        $result = $this->builder->buildLangCondition('5');
        $this->assertSame(' and WoLgID=5', $result);
    }

    #[Test]
    public function buildLangConditionCastsToInt(): void
    {
        $result = $this->builder->buildLangCondition('abc');
        $this->assertSame(' and WoLgID=0', $result);
    }

    #[Test]
    public function buildLangConditionWithParamsUsesPlaceholder(): void
    {
        $params = [];
        $result = $this->builder->buildLangCondition('7', $params);
        $this->assertSame(' and WoLgID = ?', $result);
        $this->assertSame([7], $params);
    }

    #[Test]
    public function buildLangConditionWithParamsEmptyStringReturnsEmpty(): void
    {
        $params = [];
        $result = $this->builder->buildLangCondition('', $params);
        $this->assertSame('', $result);
        $this->assertSame([], $params);
    }

    #[Test]
    public function buildLangConditionWithParamsCastsToInt(): void
    {
        $params = [];
        $result = $this->builder->buildLangCondition('abc', $params);
        $this->assertSame(' and WoLgID = ?', $result);
        $this->assertSame([0], $params);
    }

    #[Test]
    public function buildLangConditionWithParamsAppendsToExisting(): void
    {
        $params = [42];
        $this->builder->buildLangCondition('3', $params);
        $this->assertSame([42, 3], $params);
    }

    #[Test]
    public function buildLangConditionLargeId(): void
    {
        $result = $this->builder->buildLangCondition('999999');
        $this->assertSame(' and WoLgID=999999', $result);
    }

    // =========================================================================
    // buildStatusCondition()
    // =========================================================================

    #[Test]
    public function buildStatusConditionReturnsEmptyForEmptyString(): void
    {
        $this->assertSame('', $this->builder->buildStatusCondition(''));
    }

    #[Test]
    public function buildStatusConditionSingleStatus(): void
    {
        $result = $this->builder->buildStatusCondition('1');
        $this->assertSame(' and WoStatus = 1', $result);
    }

    #[Test]
    public function buildStatusConditionRange12(): void
    {
        $result = $this->builder->buildStatusCondition('12');
        $this->assertSame(' and (WoStatus between 1 and 2)', $result);
    }

    #[Test]
    public function buildStatusConditionRange15(): void
    {
        $result = $this->builder->buildStatusCondition('15');
        $this->assertSame(' and (WoStatus between 1 and 5)', $result);
    }

    #[Test]
    public function buildStatusConditionRange25(): void
    {
        $result = $this->builder->buildStatusCondition('25');
        $this->assertSame(' and (WoStatus between 2 and 5)', $result);
    }

    #[Test]
    public function buildStatusConditionRange45(): void
    {
        $result = $this->builder->buildStatusCondition('45');
        $this->assertSame(' and (WoStatus between 4 and 5)', $result);
    }

    #[Test]
    public function buildStatusCondition599(): void
    {
        $result = $this->builder->buildStatusCondition('599');
        $this->assertSame(' and WoStatus in (5,99)', $result);
    }

    #[Test]
    public function buildStatusConditionIgnored(): void
    {
        $result = $this->builder->buildStatusCondition('98');
        $this->assertSame(' and WoStatus = 98', $result);
    }

    #[Test]
    public function buildStatusConditionWellKnown(): void
    {
        $result = $this->builder->buildStatusCondition('99');
        $this->assertSame(' and WoStatus = 99', $result);
    }

    #[Test]
    public function buildStatusConditionStartsWithAndKeyword(): void
    {
        $result = $this->builder->buildStatusCondition('3');
        $this->assertStringStartsWith(' and ', $result);
    }

    // =========================================================================
    // buildQueryCondition() -- with $params (prepared statement path)
    // =========================================================================

    #[Test]
    public function buildQueryConditionReturnsEmptyForEmptyQuery(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('', 'term', '', $params);
        $this->assertSame('', $result);
        $this->assertSame([], $params);
    }

    #[Test]
    public function buildQueryConditionTermMode(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('hello', 'term', '', $params);
        $this->assertSame(' and (WoText like ?)', $result);
        $this->assertSame(['hello'], $params);
    }

    #[Test]
    public function buildQueryConditionRomMode(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('test', 'rom', '', $params);
        $this->assertSame(" and (IFNULL(WoRomanization,'*') like ?)", $result);
        $this->assertSame(['test'], $params);
    }

    #[Test]
    public function buildQueryConditionTranslMode(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('meaning', 'transl', '', $params);
        $this->assertSame(' and (WoTranslation like ?)', $result);
        $this->assertSame(['meaning'], $params);
    }

    #[Test]
    public function buildQueryConditionTermRomMode(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('test', 'term,rom', '', $params);
        $this->assertSame(" and (WoText like ? or IFNULL(WoRomanization,'*') like ?)", $result);
        $this->assertSame(['test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionRomTranslMode(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('test', 'rom,transl', '', $params);
        $this->assertSame(" and (IFNULL(WoRomanization,'*') like ? or WoTranslation like ?)", $result);
        $this->assertSame(['test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionTermTranslMode(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('test', 'term,transl', '', $params);
        $this->assertSame(' and (WoText like ? or WoTranslation like ?)', $result);
        $this->assertSame(['test', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionTermRomTranslMode(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('test', 'term,rom,transl', '', $params);
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
        $result = $this->builder->buildQueryCondition('test', 'unknown', '', $params);
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
        $this->builder->buildQueryCondition('hel*o', 'term', '', $params);
        $this->assertSame(['hel%o'], $params);
    }

    #[Test]
    public function buildQueryConditionMultipleWildcards(): void
    {
        $params = [];
        $this->builder->buildQueryCondition('*hello*', 'term', '', $params);
        $this->assertSame(['%hello%'], $params);
    }

    #[Test]
    public function buildQueryConditionLowercasesQuery(): void
    {
        $params = [];
        $this->builder->buildQueryCondition('HELLO', 'term', '', $params);
        $this->assertSame(['hello'], $params);
    }

    #[Test]
    public function buildQueryConditionRegexModeUsesRlike(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('^test$', 'term', 'r', $params);
        $this->assertSame(' and (WoText rlike ?)', $result);
        $this->assertSame(['^test$'], $params);
    }

    #[Test]
    public function buildQueryConditionRegexDoesNotLowercase(): void
    {
        $params = [];
        $this->builder->buildQueryCondition('^TEST$', 'term', 'r', $params);
        $this->assertSame(['^TEST$'], $params);
    }

    #[Test]
    public function buildQueryConditionRegexDoesNotReplaceWildcards(): void
    {
        $params = [];
        $this->builder->buildQueryCondition('te*st', 'term', 'r', $params);
        $this->assertSame(['te*st'], $params);
    }

    #[Test]
    public function buildQueryConditionAppendsToExistingParams(): void
    {
        $params = [42, 'existing'];
        $this->builder->buildQueryCondition('test', 'term', '', $params);
        $this->assertSame([42, 'existing', 'test'], $params);
    }

    #[Test]
    public function buildQueryConditionUtf8Lowercase(): void
    {
        $params = [];
        $this->builder->buildQueryCondition("\xC3\x9Cber", 'term', '', $params);
        $this->assertSame(["\xC3\xBCber"], $params);
    }

    #[Test]
    public function buildQueryConditionRegexAllFields(): void
    {
        $params = [];
        $result = $this->builder->buildQueryCondition('^a', 'term,rom,transl', 'r', $params);
        $this->assertStringContainsString('rlike', $result);
        $this->assertCount(3, $params);
    }

    // =========================================================================
    // validateRegexPattern() -- signature tests only (DB required for actual)
    // =========================================================================

    #[Test]
    public function validateRegexPatternIsPublic(): void
    {
        $method = new ReflectionMethod(WordListFilterBuilder::class, 'validateRegexPattern');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function validateRegexPatternAcceptsStringReturnsBool(): void
    {
        $method = new ReflectionMethod(WordListFilterBuilder::class, 'validateRegexPattern');
        $this->assertSame('bool', $method->getReturnType()?->getName());
        $this->assertSame('string', $method->getParameters()[0]->getType()?->getName());
    }

    // =========================================================================
    // buildTagCondition()
    // =========================================================================

    #[Test]
    public function buildTagConditionBothEmptyReturnsEmpty(): void
    {
        $this->assertSame('', $this->builder->buildTagCondition('', '', '0'));
    }

    #[Test]
    public function buildTagConditionTag1OnlyWithoutParams(): void
    {
        $result = $this->builder->buildTagCondition('5', '', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString("'%/5/%'", $result);
    }

    #[Test]
    public function buildTagConditionTag2OnlyWithoutParams(): void
    {
        $result = $this->builder->buildTagCondition('', '3', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString("'%/3/%'", $result);
    }

    #[Test]
    public function buildTagConditionBothTagsWithOr(): void
    {
        $result = $this->builder->buildTagCondition('5', '3', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString(') OR (', $result);
    }

    #[Test]
    public function buildTagConditionBothTagsWithAnd(): void
    {
        $result = $this->builder->buildTagCondition('5', '3', '1');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString(') AND (', $result);
    }

    #[Test]
    public function buildTagConditionUntaggedTag1(): void
    {
        $result = $this->builder->buildTagCondition('-1', '', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
    }

    #[Test]
    public function buildTagConditionUntaggedTag2(): void
    {
        $result = $this->builder->buildTagCondition('', '-1', '0');
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
    }

    #[Test]
    public function buildTagConditionTag1WithParams(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('5', '', '0', $params);
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('?', $result);
        $this->assertSame([5], $params);
    }

    #[Test]
    public function buildTagConditionTag2WithParams(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('', '8', '0', $params);
        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('?', $result);
        $this->assertSame([8], $params);
    }

    #[Test]
    public function buildTagConditionBothWithParamsOr(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('5', '3', '0', $params);
        $this->assertStringContainsString(') OR (', $result);
        $this->assertSame([5, 3], $params);
    }

    #[Test]
    public function buildTagConditionBothWithParamsAnd(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('5', '3', '1', $params);
        $this->assertStringContainsString(') AND (', $result);
        $this->assertSame([5, 3], $params);
    }

    #[Test]
    public function buildTagConditionUntaggedTag1WithParams(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('-1', '', '0', $params);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
        $this->assertSame([], $params);
    }

    #[Test]
    public function buildTagConditionNonNumericTag1Ignored(): void
    {
        $result = $this->builder->buildTagCondition('abc', '', '0');
        $this->assertSame('', $result);
    }

    #[Test]
    public function buildTagConditionNonNumericTag2Ignored(): void
    {
        $result = $this->builder->buildTagCondition('', 'xyz', '0');
        $this->assertSame('', $result);
    }

    #[Test]
    public function buildTagConditionUntaggedBothWithAnd(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('-1', '-1', '1', $params);
        $this->assertStringContainsString(') AND (', $result);
        $this->assertSame([], $params);
    }

    #[Test]
    public function buildTagConditionMixedUntaggedAndTagWithParams(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('-1', '5', '0', $params);
        $this->assertStringContainsString('group_concat(WtTgID) IS NULL', $result);
        $this->assertStringContainsString(') OR (', $result);
        $this->assertSame([5], $params);
    }

    #[Test]
    public function buildTagConditionTag1WithParamsUsesConcat(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('10', '', '0', $params);
        $this->assertStringContainsString("concat('%/', ?, '/%')", $result);
    }

    #[Test]
    public function buildTagConditionTag2WithParamsUsesConcat(): void
    {
        $params = [];
        $result = $this->builder->buildTagCondition('', '10', '0', $params);
        $this->assertStringContainsString("concat('%/', ?, '/%')", $result);
    }

    #[Test]
    public function buildTagConditionNonNumericBothReturnsEmpty(): void
    {
        $result = $this->builder->buildTagCondition('abc', 'xyz', '1');
        $this->assertSame('', $result);
    }

    #[Test]
    public function buildTagConditionWithParamsAppendsToExisting(): void
    {
        $params = [99];
        $this->builder->buildTagCondition('5', '3', '0', $params);
        $this->assertSame([99, 5, 3], $params);
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function allPublicMethodsExist(): void
    {
        $expectedMethods = [
            'buildLangCondition',
            'buildStatusCondition',
            'buildQueryCondition',
            'validateRegexPattern',
            'buildTagCondition',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                method_exists(WordListFilterBuilder::class, $method),
                "Method $method should exist on WordListFilterBuilder"
            );
            $ref = new ReflectionMethod(WordListFilterBuilder::class, $method);
            $this->assertTrue($ref->isPublic(), "Method $method should be public");
        }
    }

    #[Test]
    public function buildLangConditionReturnTypeIsString(): void
    {
        $method = new ReflectionMethod(WordListFilterBuilder::class, 'buildLangCondition');
        $this->assertSame('string', $method->getReturnType()?->getName());
    }

    #[Test]
    public function buildStatusConditionReturnTypeIsString(): void
    {
        $method = new ReflectionMethod(WordListFilterBuilder::class, 'buildStatusCondition');
        $this->assertSame('string', $method->getReturnType()?->getName());
    }

    #[Test]
    public function buildQueryConditionReturnTypeIsString(): void
    {
        $method = new ReflectionMethod(WordListFilterBuilder::class, 'buildQueryCondition');
        $this->assertSame('string', $method->getReturnType()?->getName());
    }

    #[Test]
    public function buildTagConditionReturnTypeIsString(): void
    {
        $method = new ReflectionMethod(WordListFilterBuilder::class, 'buildTagCondition');
        $this->assertSame('string', $method->getReturnType()?->getName());
    }
}
