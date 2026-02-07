<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\UseCases;

use DateTimeImmutable;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Vocabulary\Application\UseCases\UpdateTerm;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the UpdateTerm use case.
 */
class UpdateTermTest extends TestCase
{
    /** @var TermRepositoryInterface&MockObject */
    private TermRepositoryInterface $repository;
    private UpdateTerm $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
        $this->repository = $this->createMock(TermRepositoryInterface::class);
        $this->useCase = new UpdateTerm($this->repository);
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    private function createTestTerm(int $id = 42): Term
    {
        return Term::reconstitute(
            $id,
            1,
            'test',
            'test',
            null,
            null,
            1,
            '*',
            '',
            '',
            '',
            1,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            0.0,
            0.0,
            0.5
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteUpdatesStatusSuccessfully(): void
    {
        $term = $this->createTestTerm();

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($term);

        $this->repository->expects($this->once())
            ->method('save');

        $result = $this->useCase->execute(42, status: 3);

        $this->assertInstanceOf(Term::class, $result);
        $this->assertEquals(3, $result->status()->toInt());
    }

    public function testExecuteUpdatesTranslationSuccessfully(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())->method('save');

        $result = $this->useCase->execute(42, translation: 'Bonjour');

        $this->assertEquals('Bonjour', $result->translation());
    }

    public function testExecuteUpdatesSentenceSuccessfully(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())->method('save');

        $result = $this->useCase->execute(42, sentence: 'Example sentence');

        $this->assertEquals('Example sentence', $result->sentence());
    }

    public function testExecuteUpdatesNotesSuccessfully(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())->method('save');

        $result = $this->useCase->execute(42, notes: 'Personal note');

        $this->assertEquals('Personal note', $result->notes());
    }

    public function testExecuteUpdatesRomanizationSuccessfully(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())->method('save');

        $result = $this->useCase->execute(42, romanization: 'pinyin');

        $this->assertEquals('pinyin', $result->romanization());
    }

    public function testExecuteThrowsExceptionForNonExistentTerm(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Term not found: 999');

        $this->useCase->execute(999, status: 2);
    }

    public function testExecuteNormalizesEmptyTranslation(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->method('save');

        $result = $this->useCase->execute(42, translation: '');

        $this->assertEquals('*', $result->translation());
    }

    public function testExecuteNormalizesAsteriskTranslation(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->method('save');

        $result = $this->useCase->execute(42, translation: '*');

        $this->assertEquals('*', $result->translation());
    }

    public function testExecuteReplacesTabsAndNewlinesInSentence(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->method('save');

        $result = $this->useCase->execute(42, sentence: "Line1\tTab\nLine2");

        $this->assertEquals('Line1 Tab Line2', $result->sentence());
    }

    public function testExecuteReplacesTabsAndNewlinesInNotes(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->method('save');

        $result = $this->useCase->execute(42, notes: "Note1\r\nNote2");

        $this->assertEquals('Note1 Note2', $result->notes());
    }

    public function testExecuteDoesNotUpdateWhenNoParametersProvided(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())->method('save');

        $result = $this->useCase->execute(42);

        // Original values should be preserved
        $this->assertEquals(1, $result->status()->toInt());
        $this->assertEquals('*', $result->translation());
    }

    public function testExecuteUpdatesMultipleFieldsAtOnce(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())->method('save');

        $result = $this->useCase->execute(
            42,
            status: 5,
            translation: 'New translation',
            sentence: 'New sentence',
            notes: 'New notes',
            romanization: 'New roman'
        );

        $this->assertEquals(5, $result->status()->toInt());
        $this->assertEquals('New translation', $result->translation());
        $this->assertEquals('New sentence', $result->sentence());
        $this->assertEquals('New notes', $result->notes());
        $this->assertEquals('New roman', $result->romanization());
    }

    // =========================================================================
    // executeFromArray() Tests
    // =========================================================================

    public function testExecuteFromArrayReturnsSuccessOnUpdate(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->method('save');

        $result = $this->useCase->executeFromArray([
            'WoID' => 42,
            'WoStatus' => 3,
            'WoTranslation' => 'Updated'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Term updated', $result['message']);
        $this->assertEquals(42, $result['id']);
    }

    public function testExecuteFromArrayReturnsFailureOnNotFound(): void
    {
        $this->repository->method('find')->willReturn(null);

        $result = $this->useCase->executeFromArray([
            'WoID' => 999,
            'WoStatus' => 2
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Term not found', $result['message']);
        $this->assertEquals(0, $result['id']);
    }

    public function testExecuteFromArrayHandlesMissingWoID(): void
    {
        $this->repository->method('find')->willReturn(null);

        $result = $this->useCase->executeFromArray([
            'WoStatus' => 2
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Term not found: 0', $result['message']);
    }

    public function testExecuteFromArrayHandlesException(): void
    {
        $this->repository->method('find')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->useCase->executeFromArray([
            'WoID' => 42,
            'WoStatus' => 2
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Database error', $result['message']);
    }

    public function testExecuteFromArrayHandlesPartialData(): void
    {
        $term = $this->createTestTerm();

        $this->repository->method('find')->willReturn($term);
        $this->repository->method('save');

        // Only update translation, other fields not provided
        $result = $this->useCase->executeFromArray([
            'WoID' => 42,
            'WoTranslation' => 'Only translation updated'
        ]);

        $this->assertTrue($result['success']);
    }
}
