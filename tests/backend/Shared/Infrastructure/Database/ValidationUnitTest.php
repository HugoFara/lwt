<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Database;

use Lwt\Shared\Infrastructure\Database\Validation;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for Validation class input-validation paths.
 *
 * These tests exercise only the early-return code paths that never
 * reach the database: empty strings, '-1' passthrough, non-numeric
 * inputs, and SQL injection attempts.
 */
class ValidationUnitTest extends TestCase
{
    // ===== tag() early-return paths =====

    public function testTagEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('', ''));
    }

    public function testTagEmptyStringWithLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('', '5'));
    }

    public function testTagMinusOnePassesThrough(): void
    {
        $this->assertSame('-1', Validation::tag('-1', ''));
    }

    public function testTagMinusOneWithLanguagePassesThrough(): void
    {
        $this->assertSame('-1', Validation::tag('-1', '42'));
    }

    public function testTagNonNumericReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('abc', ''));
    }

    public function testTagSqlInjectionOrReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('1 OR 1=1', ''));
    }

    public function testTagSqlInjectionDropReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('1; DROP TABLE tags; --', ''));
    }

    public function testTagSqlInjectionQuotesReturnsEmpty(): void
    {
        $this->assertSame("", Validation::tag("1' OR '1'='1", ''));
    }

    public function testTagNumericTagNonNumericLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('5', 'abc'));
    }

    public function testTagNumericTagSqlInjectionLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('5', '1; DROP TABLE languages; --'));
    }

    public function testTagNumericTagUnionLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::tag('5', "1' UNION SELECT * FROM users --"));
    }

    // ===== archTextTag() early-return paths =====

    public function testArchTextTagEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', Validation::archTextTag('', ''));
    }

    public function testArchTextTagMinusOnePassesThrough(): void
    {
        $this->assertSame('-1', Validation::archTextTag('-1', ''));
    }

    public function testArchTextTagMinusOneWithLanguagePassesThrough(): void
    {
        $this->assertSame('-1', Validation::archTextTag('-1', '10'));
    }

    public function testArchTextTagNonNumericReturnsEmpty(): void
    {
        $this->assertSame('', Validation::archTextTag('not-a-number', ''));
    }

    public function testArchTextTagSqlInjectionReturnsEmpty(): void
    {
        $this->assertSame('', Validation::archTextTag('1 OR 1=1', ''));
    }

    public function testArchTextTagNumericTagNonNumericLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::archTextTag('5', 'abc'));
    }

    public function testArchTextTagNumericTagSqlInjectionLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::archTextTag('5', '1; DROP TABLE texts; --'));
    }

    // ===== textTag() early-return paths =====

    public function testTextTagEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', Validation::textTag('', ''));
    }

    public function testTextTagMinusOnePassesThrough(): void
    {
        $this->assertSame('-1', Validation::textTag('-1', ''));
    }

    public function testTextTagMinusOneWithLanguagePassesThrough(): void
    {
        $this->assertSame('-1', Validation::textTag('-1', '7'));
    }

    public function testTextTagNonNumericReturnsEmpty(): void
    {
        $this->assertSame('', Validation::textTag('hello', ''));
    }

    public function testTextTagSqlInjectionReturnsEmpty(): void
    {
        $this->assertSame('', Validation::textTag("1'; DROP TABLE text_tags; --", ''));
    }

    public function testTextTagNumericTagNonNumericLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::textTag('5', 'invalid'));
    }

    public function testTextTagNumericTagSqlInjectionLanguageReturnsEmpty(): void
    {
        $this->assertSame('', Validation::textTag('5', '1 UNION SELECT * FROM users'));
    }

    // ===== Method signatures =====

    public function testMethodSignaturesReturnString(): void
    {
        $reflection = new \ReflectionClass(Validation::class);

        $tagMethod = $reflection->getMethod('tag');
        $this->assertTrue($tagMethod->isStatic());
        $this->assertSame('string', $tagMethod->getReturnType()->getName());
        $this->assertCount(2, $tagMethod->getParameters());

        $archMethod = $reflection->getMethod('archTextTag');
        $this->assertTrue($archMethod->isStatic());
        $this->assertSame('string', $archMethod->getReturnType()->getName());
        $this->assertCount(2, $archMethod->getParameters());

        $textMethod = $reflection->getMethod('textTag');
        $this->assertTrue($textMethod->isStatic());
        $this->assertSame('string', $textMethod->getReturnType()->getName());
        $this->assertCount(2, $textMethod->getParameters());
    }
}
