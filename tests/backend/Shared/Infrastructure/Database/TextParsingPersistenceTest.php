<?php

/**
 * Unit tests for TextParsingPersistence.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests\Shared\Infrastructure\Database
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Tests\Backend\Shared\Infrastructure\Database;

use Lwt\Shared\Infrastructure\Database\TextParsingPersistence;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TextParsingPersistence static methods.
 *
 * @covers \Lwt\Shared\Infrastructure\Database\TextParsingPersistence
 * @since  3.0.0
 */
class TextParsingPersistenceTest extends TestCase
{
    // =========================================================================
    // saveWithSql - signature and visibility
    // =========================================================================

    #[Test]
    public function saveWithSqlIsPublicAndStatic(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'saveWithSql'
        );

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    #[Test]
    public function saveWithSqlAcceptsStringTextAndIntId(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'saveWithSql'
        );
        $params = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('text', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
        $this->assertSame('id', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
    }

    #[Test]
    public function saveWithSqlReturnsVoid(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'saveWithSql'
        );

        $this->assertSame('void', $reflection->getReturnType()->getName());
    }

    #[Test]
    public function saveWithSqlRequiresDatabase(): void
    {
        $this->markTestSkipped('Database connection required');
    }

    // =========================================================================
    // saveWithSqlFallback - signature and visibility
    // =========================================================================

    #[Test]
    public function saveWithSqlFallbackIsPublicAndStatic(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'saveWithSqlFallback'
        );

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    #[Test]
    public function saveWithSqlFallbackAcceptsStringTextAndIntId(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'saveWithSqlFallback'
        );
        $params = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('text', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
        $this->assertSame('id', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
    }

    #[Test]
    public function saveWithSqlFallbackReturnsVoid(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'saveWithSqlFallback'
        );

        $this->assertSame('void', $reflection->getReturnType()->getName());
    }

    #[Test]
    public function saveWithSqlFallbackRequiresDatabase(): void
    {
        $this->markTestSkipped('Database connection required');
    }

    // =========================================================================
    // checkValid - signature and visibility
    // =========================================================================

    #[Test]
    public function checkValidIsPublicAndStatic(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'checkValid'
        );

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    #[Test]
    public function checkValidAcceptsSingleIntParameter(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'checkValid'
        );
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('lid', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    #[Test]
    public function checkValidReturnsVoid(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'checkValid'
        );

        $this->assertSame('void', $reflection->getReturnType()->getName());
    }

    #[Test]
    public function checkValidRequiresDatabase(): void
    {
        $this->markTestSkipped('Database connection required');
    }

    // =========================================================================
    // registerSentencesTextItems - signature and visibility
    // =========================================================================

    #[Test]
    public function registerSentencesTextItemsIsPublicAndStatic(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'registerSentencesTextItems'
        );

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    #[Test]
    public function registerSentencesTextItemsAcceptsThreeParameters(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'registerSentencesTextItems'
        );
        $params = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('tid', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
        $this->assertSame('lid', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
        $this->assertSame('hasmultiword', $params[2]->getName());
        $this->assertSame('bool', $params[2]->getType()->getName());
    }

    #[Test]
    public function registerSentencesTextItemsRequiresDatabase(): void
    {
        $this->markTestSkipped('Database connection required');
    }

    // =========================================================================
    // getMultiWordLengths - signature and visibility
    // =========================================================================

    #[Test]
    public function getMultiWordLengthsIsPublicAndStatic(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'getMultiWordLengths'
        );

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    #[Test]
    public function getMultiWordLengthsAcceptsSingleIntParameter(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'getMultiWordLengths'
        );
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('lid', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    #[Test]
    public function getMultiWordLengthsReturnsArray(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'getMultiWordLengths'
        );

        $this->assertSame('array', $reflection->getReturnType()->getName());
    }

    #[Test]
    public function getMultiWordLengthsRequiresDatabase(): void
    {
        $this->markTestSkipped('Database connection required');
    }

    // =========================================================================
    // displayStatistics - signature and visibility
    // =========================================================================

    #[Test]
    public function displayStatisticsIsPublicAndStatic(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'displayStatistics'
        );

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    #[Test]
    public function displayStatisticsAcceptsThreeParameters(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'displayStatistics'
        );
        $params = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('lid', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
        $this->assertSame('rtlScript', $params[1]->getName());
        $this->assertSame('bool', $params[1]->getType()->getName());
        $this->assertSame('multiwords', $params[2]->getName());
        $this->assertSame('bool', $params[2]->getType()->getName());
    }

    #[Test]
    public function displayStatisticsRequiresDatabase(): void
    {
        $this->markTestSkipped('Database connection required');
    }

    // =========================================================================
    // checkExpressions - signature and visibility
    // =========================================================================

    #[Test]
    public function checkExpressionsIsPublicAndStatic(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'checkExpressions'
        );

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    #[Test]
    public function checkExpressionsAcceptsSingleArrayParameter(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'checkExpressions'
        );
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('wl', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    #[Test]
    public function checkExpressionsReturnsVoid(): void
    {
        $reflection = new \ReflectionMethod(
            TextParsingPersistence::class,
            'checkExpressions'
        );

        $this->assertSame('void', $reflection->getReturnType()->getName());
    }

    #[Test]
    public function checkExpressionsRequiresDatabase(): void
    {
        $this->markTestSkipped('Database connection required');
    }

    // =========================================================================
    // Class-level checks
    // =========================================================================

    #[Test]
    public function allPublicMethodsAreStatic(): void
    {
        $reflection = new \ReflectionClass(TextParsingPersistence::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $this->assertTrue(
                $method->isStatic(),
                "Method {$method->getName()} should be static"
            );
        }
    }

    #[Test]
    public function classHasExpectedNumberOfPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextParsingPersistence::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        // saveWithSql, saveWithSqlFallback, checkValid,
        // registerSentencesTextItems, displayStatistics,
        // getMultiWordLengths, checkExpressions
        $this->assertCount(7, $methods);
    }
}
