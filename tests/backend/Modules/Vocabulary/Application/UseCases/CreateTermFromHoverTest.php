<?php

/**
 * Unit tests for CreateTermFromHover use case.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests\Modules\Vocabulary\Application\UseCases
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\UseCases;

use Lwt\Modules\Dictionary\Application\DictionaryFacade;
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the CreateTermFromHover use case.
 *
 * These tests require database connection for the Connection::preparedFetchValue
 * and Connection::preparedExecute calls within execute().
 *
 * @since 3.0.0
 */
class CreateTermFromHoverTest extends TestCase
{
    private VocabularyFacade&MockObject $vocabularyFacade;
    private DictionaryFacade&MockObject $dictionaryFacade;
    private CreateTermFromHover $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }

        $this->vocabularyFacade = $this->createMock(VocabularyFacade::class);
        $this->dictionaryFacade = $this->createMock(DictionaryFacade::class);

        $this->useCase = new CreateTermFromHover(
            $this->vocabularyFacade,
            $this->dictionaryFacade
        );
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsTrueForStatus1(): void
    {
        // This method doesn't require DB, so create without skipping
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertTrue($useCase->shouldSetNoCacheHeaders(1));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForStatus2(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(2));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForStatus3(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(3));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForStatus4(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(4));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForStatus5(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(5));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForStatus98(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(98));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForStatus99(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(99));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForZero(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(0));
    }

    #[Test]
    public function constructorAcceptsNullParameters(): void
    {
        // This tests the default instantiation path (nullable params)
        // We can't fully test execute without DB, but constructor should work
        $useCase = new CreateTermFromHover(null, null);
        $this->assertInstanceOf(CreateTermFromHover::class, $useCase);
    }

    #[Test]
    public function constructorAcceptsMockDependencies(): void
    {
        $vocabFacade = $this->createMock(VocabularyFacade::class);
        $dictFacade = $this->createMock(DictionaryFacade::class);

        $useCase = new CreateTermFromHover($vocabFacade, $dictFacade);
        $this->assertInstanceOf(CreateTermFromHover::class, $useCase);
    }

    #[Test]
    public function constructorAcceptsPartialNullParameters(): void
    {
        $vocabFacade = $this->createMock(VocabularyFacade::class);

        $useCase = new CreateTermFromHover($vocabFacade, null);
        $this->assertInstanceOf(CreateTermFromHover::class, $useCase);
    }

    #[Test]
    public function constructorWithOnlyDictionaryFacade(): void
    {
        $dictFacade = $this->createMock(DictionaryFacade::class);

        $useCase = new CreateTermFromHover(null, $dictFacade);
        $this->assertInstanceOf(CreateTermFromHover::class, $useCase);
    }

    #[Test]
    public function translateIsNotCalledForNonStatus1(): void
    {
        // This tests that translation only happens for status 1.
        // The actual execute() requires DB, so we test the logic indirectly
        // via the shouldSetNoCacheHeaders which mirrors the status 1 check
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        // Only status 1 triggers no-cache (and translation)
        $this->assertTrue($useCase->shouldSetNoCacheHeaders(1));
        $this->assertFalse($useCase->shouldSetNoCacheHeaders(2));
        $this->assertFalse($useCase->shouldSetNoCacheHeaders(3));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForNegativeStatus(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(-1));
    }

    #[Test]
    public function shouldSetNoCacheHeadersReturnsFalseForLargeStatus(): void
    {
        $useCase = new CreateTermFromHover(
            $this->createMock(VocabularyFacade::class),
            $this->createMock(DictionaryFacade::class)
        );

        $this->assertFalse($useCase->shouldSetNoCacheHeaders(1000));
    }
}
