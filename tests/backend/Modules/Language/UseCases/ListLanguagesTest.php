<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\UseCases;

use Lwt\Modules\Language\Application\UseCases\ListLanguages;
use Lwt\Modules\Language\Domain\LanguageRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ListLanguages use case.
 *
 * Note: Some methods in ListLanguages use QueryBuilder directly
 * and require integration tests. This file tests the repository-based methods.
 */
class ListLanguagesTest extends TestCase
{
    /** @var LanguageRepositoryInterface&MockObject */
    private LanguageRepositoryInterface $repository;
    private ListLanguages $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(LanguageRepositoryInterface::class);
        $this->useCase = new ListLanguages($this->repository);
    }

    // =========================================================================
    // getAllLanguages() Tests
    // =========================================================================

    public function testGetAllLanguagesReturnsDict(): void
    {
        $expectedDict = [
            'English' => 1,
            'Spanish' => 2,
            'French' => 3,
        ];

        $this->repository->expects($this->once())
            ->method('getAllAsDict')
            ->willReturn($expectedDict);

        $result = $this->useCase->getAllLanguages();

        $this->assertEquals($expectedDict, $result);
    }

    public function testGetAllLanguagesReturnsEmptyArray(): void
    {
        $this->repository->method('getAllAsDict')
            ->willReturn([]);

        $result = $this->useCase->getAllLanguages();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllLanguagesPreservesOrder(): void
    {
        $expectedDict = [
            'Arabic' => 3,
            'Chinese' => 1,
            'German' => 2,
        ];

        $this->repository->method('getAllAsDict')
            ->willReturn($expectedDict);

        $result = $this->useCase->getAllLanguages();

        $this->assertEquals(array_keys($expectedDict), array_keys($result));
    }

    // =========================================================================
    // getLanguagesForSelect() Tests
    // =========================================================================

    public function testGetLanguagesForSelectReturnsFormattedArray(): void
    {
        $expected = [
            ['id' => 1, 'name' => 'English'],
            ['id' => 2, 'name' => 'Spanish'],
            ['id' => 3, 'name' => 'French'],
        ];

        $this->repository->expects($this->once())
            ->method('getForSelect')
            ->with(30)
            ->willReturn($expected);

        $result = $this->useCase->getLanguagesForSelect();

        $this->assertEquals($expected, $result);
    }

    public function testGetLanguagesForSelectWithCustomMaxLength(): void
    {
        $expected = [
            ['id' => 1, 'name' => 'Eng...'],
        ];

        $this->repository->expects($this->once())
            ->method('getForSelect')
            ->with(6)
            ->willReturn($expected);

        $result = $this->useCase->getLanguagesForSelect(6);

        $this->assertEquals($expected, $result);
    }

    public function testGetLanguagesForSelectReturnsEmptyArray(): void
    {
        $this->repository->method('getForSelect')
            ->willReturn([]);

        $result = $this->useCase->getLanguagesForSelect();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetLanguagesForSelectContainsCorrectKeys(): void
    {
        $expected = [
            ['id' => 1, 'name' => 'Test Language'],
        ];

        $this->repository->method('getForSelect')
            ->willReturn($expected);

        $result = $this->useCase->getLanguagesForSelect();

        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testGetAllLanguagesHandlesUnicodeNames(): void
    {
        $expectedDict = [
            '日本語' => 1,
            'العربية' => 2,
            'Português' => 3,
        ];

        $this->repository->method('getAllAsDict')
            ->willReturn($expectedDict);

        $result = $this->useCase->getAllLanguages();

        $this->assertEquals('日本語', array_keys($result)[0]);
        $this->assertEquals('العربية', array_keys($result)[1]);
        $this->assertEquals('Português', array_keys($result)[2]);
    }

    public function testGetLanguagesForSelectHandlesLongNames(): void
    {
        $expected = [
            ['id' => 1, 'name' => 'A Very Long Language Name That...'],
        ];

        $this->repository->method('getForSelect')
            ->with(35)
            ->willReturn($expected);

        $result = $this->useCase->getLanguagesForSelect(35);

        $this->assertCount(1, $result);
    }

    public function testGetAllLanguagesHandlesManyLanguages(): void
    {
        $largeDict = [];
        for ($i = 1; $i <= 100; $i++) {
            $largeDict["Language$i"] = $i;
        }

        $this->repository->method('getAllAsDict')
            ->willReturn($largeDict);

        $result = $this->useCase->getAllLanguages();

        $this->assertCount(100, $result);
    }
}
