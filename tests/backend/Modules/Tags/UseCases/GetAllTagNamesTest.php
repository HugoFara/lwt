<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\UseCases;

use Lwt\Modules\Tags\Application\UseCases\GetAllTagNames;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetAllTagNames use case.
 */
class GetAllTagNamesTest extends TestCase
{
    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $termRepository;

    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $textRepository;

    private GetAllTagNames $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear session for each test
        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/tags';

        $this->termRepository = $this->createMock(TagRepositoryInterface::class);
        $this->textRepository = $this->createMock(TagRepositoryInterface::class);
        $this->useCase = new GetAllTagNames($this->termRepository, $this->textRepository);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    // =========================================================================
    // getTermTags() Tests
    // =========================================================================

    public function testGetTermTagsReturnsTagsFromRepository(): void
    {
        $expectedTags = ['verbs', 'nouns', 'adjectives'];

        $this->termRepository->expects($this->once())
            ->method('getAllTexts')
            ->willReturn($expectedTags);

        $tags = $this->useCase->getTermTags();

        $this->assertEquals($expectedTags, $tags);
    }

    public function testGetTermTagsCachesResult(): void
    {
        $expectedTags = ['cached', 'tags'];

        $this->termRepository->expects($this->once())
            ->method('getAllTexts')
            ->willReturn($expectedTags);

        // First call - should hit repository
        $tags1 = $this->useCase->getTermTags();
        // Second call - should use cache
        $tags2 = $this->useCase->getTermTags();

        $this->assertEquals($expectedTags, $tags1);
        $this->assertEquals($expectedTags, $tags2);
    }

    public function testGetTermTagsRefreshBypassesCache(): void
    {
        $initialTags = ['initial'];
        $refreshedTags = ['refreshed'];

        $this->termRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturnOnConsecutiveCalls($initialTags, $refreshedTags);

        // First call - cache result
        $this->useCase->getTermTags();
        // Second call with refresh - should bypass cache
        $tags = $this->useCase->getTermTags(true);

        $this->assertEquals($refreshedTags, $tags);
    }

    public function testGetTermTagsInvalidatesCacheOnUrlChange(): void
    {
        $initialTags = ['initial'];
        $newTags = ['new'];

        $this->termRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturnOnConsecutiveCalls($initialTags, $newTags);

        // First call with one URL
        $_SERVER['REQUEST_URI'] = '/project1/tags';
        $tags1 = $this->useCase->getTermTags();

        // Change URL base
        $_SERVER['REQUEST_URI'] = '/project2/tags';
        $tags2 = $this->useCase->getTermTags();

        $this->assertEquals($initialTags, $tags1);
        $this->assertEquals($newTags, $tags2);
    }

    public function testGetTermTagsReturnsEmptyArray(): void
    {
        $this->termRepository->method('getAllTexts')
            ->willReturn([]);

        $tags = $this->useCase->getTermTags();

        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }

    public function testGetTermTagsStoresInSession(): void
    {
        $expectedTags = ['session', 'stored'];

        $this->termRepository->method('getAllTexts')
            ->willReturn($expectedTags);

        $this->useCase->getTermTags();

        $this->assertArrayHasKey('TAGS', $_SESSION);
        $this->assertEquals($expectedTags, $_SESSION['TAGS']);
    }

    // =========================================================================
    // getTextTags() Tests
    // =========================================================================

    public function testGetTextTagsReturnsTagsFromRepository(): void
    {
        $expectedTags = ['news', 'articles', 'stories'];

        $this->textRepository->expects($this->once())
            ->method('getAllTexts')
            ->willReturn($expectedTags);

        $tags = $this->useCase->getTextTags();

        $this->assertEquals($expectedTags, $tags);
    }

    public function testGetTextTagsCachesResult(): void
    {
        $expectedTags = ['cached', 'text', 'tags'];

        $this->textRepository->expects($this->once())
            ->method('getAllTexts')
            ->willReturn($expectedTags);

        // First call - should hit repository
        $tags1 = $this->useCase->getTextTags();
        // Second call - should use cache
        $tags2 = $this->useCase->getTextTags();

        $this->assertEquals($expectedTags, $tags1);
        $this->assertEquals($expectedTags, $tags2);
    }

    public function testGetTextTagsRefreshBypassesCache(): void
    {
        $initialTags = ['initial'];
        $refreshedTags = ['refreshed'];

        $this->textRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturnOnConsecutiveCalls($initialTags, $refreshedTags);

        // First call - cache result
        $this->useCase->getTextTags();
        // Second call with refresh - should bypass cache
        $tags = $this->useCase->getTextTags(true);

        $this->assertEquals($refreshedTags, $tags);
    }

    public function testGetTextTagsInvalidatesCacheOnUrlChange(): void
    {
        $initialTags = ['initial'];
        $newTags = ['new'];

        $this->textRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturnOnConsecutiveCalls($initialTags, $newTags);

        // First call with one URL
        $_SERVER['REQUEST_URI'] = '/project1/tags/text';
        $tags1 = $this->useCase->getTextTags();

        // Change URL base
        $_SERVER['REQUEST_URI'] = '/project2/tags/text';
        $tags2 = $this->useCase->getTextTags();

        $this->assertEquals($initialTags, $tags1);
        $this->assertEquals($newTags, $tags2);
    }

    public function testGetTextTagsReturnsEmptyArray(): void
    {
        $this->textRepository->method('getAllTexts')
            ->willReturn([]);

        $tags = $this->useCase->getTextTags();

        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }

    public function testGetTextTagsStoresInSession(): void
    {
        $expectedTags = ['text', 'session'];

        $this->textRepository->method('getAllTexts')
            ->willReturn($expectedTags);

        $this->useCase->getTextTags();

        $this->assertArrayHasKey('TEXTTAGS', $_SESSION);
        $this->assertEquals($expectedTags, $_SESSION['TEXTTAGS']);
    }

    // =========================================================================
    // refreshTermTags() Tests
    // =========================================================================

    public function testRefreshTermTagsForcesFetch(): void
    {
        $refreshedTags = ['fresh', 'tags'];

        $this->termRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturn($refreshedTags);

        // First call to populate cache
        $this->useCase->getTermTags();
        // Refresh should always call repository
        $tags = $this->useCase->refreshTermTags();

        $this->assertEquals($refreshedTags, $tags);
    }

    public function testRefreshTermTagsUpdatesSessionCache(): void
    {
        $initialTags = ['old'];
        $refreshedTags = ['new'];

        $this->termRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturnOnConsecutiveCalls($initialTags, $refreshedTags);

        $this->useCase->getTermTags();
        $this->assertEquals($initialTags, $_SESSION['TAGS']);

        $this->useCase->refreshTermTags();
        $this->assertEquals($refreshedTags, $_SESSION['TAGS']);
    }

    // =========================================================================
    // refreshTextTags() Tests
    // =========================================================================

    public function testRefreshTextTagsForcesFetch(): void
    {
        $refreshedTags = ['fresh', 'text', 'tags'];

        $this->textRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturn($refreshedTags);

        // First call to populate cache
        $this->useCase->getTextTags();
        // Refresh should always call repository
        $tags = $this->useCase->refreshTextTags();

        $this->assertEquals($refreshedTags, $tags);
    }

    public function testRefreshTextTagsUpdatesSessionCache(): void
    {
        $initialTags = ['old'];
        $refreshedTags = ['new'];

        $this->textRepository->expects($this->exactly(2))
            ->method('getAllTexts')
            ->willReturnOnConsecutiveCalls($initialTags, $refreshedTags);

        $this->useCase->getTextTags();
        $this->assertEquals($initialTags, $_SESSION['TEXTTAGS']);

        $this->useCase->refreshTextTags();
        $this->assertEquals($refreshedTags, $_SESSION['TEXTTAGS']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testGetTermTagsHandlesUnicodeNames(): void
    {
        $unicodeTags = ['日本語', 'español', 'français'];

        $this->termRepository->method('getAllTexts')
            ->willReturn($unicodeTags);

        $tags = $this->useCase->getTermTags();

        $this->assertEquals($unicodeTags, $tags);
    }

    public function testGetTextTagsHandlesUnicodeNames(): void
    {
        $unicodeTags = ['新聞', 'noticias', 'actualités'];

        $this->textRepository->method('getAllTexts')
            ->willReturn($unicodeTags);

        $tags = $this->useCase->getTextTags();

        $this->assertEquals($unicodeTags, $tags);
    }

    public function testTermAndTextCachesAreIndependent(): void
    {
        $termTags = ['term1', 'term2'];
        $textTags = ['text1', 'text2'];

        $this->termRepository->method('getAllTexts')
            ->willReturn($termTags);
        $this->textRepository->method('getAllTexts')
            ->willReturn($textTags);

        $terms = $this->useCase->getTermTags();
        $texts = $this->useCase->getTextTags();

        $this->assertEquals($termTags, $terms);
        $this->assertEquals($textTags, $texts);
        $this->assertNotEquals($terms, $texts);
    }

    public function testCacheHandlesNonArraySessionValue(): void
    {
        // Simulate corrupted session data
        $_SESSION['TAGS'] = 'not an array';
        $_SESSION['TAGS_URL_BASE'] = '/tags';

        $expectedTags = ['fresh'];
        $this->termRepository->expects($this->once())
            ->method('getAllTexts')
            ->willReturn($expectedTags);

        $tags = $this->useCase->getTermTags();

        $this->assertEquals($expectedTags, $tags);
    }

    public function testCacheHandlesMissingUrlBase(): void
    {
        $_SESSION['TAGS'] = ['cached'];
        // Intentionally missing TAGS_URL_BASE

        $expectedTags = ['fresh'];
        $this->termRepository->expects($this->once())
            ->method('getAllTexts')
            ->willReturn($expectedTags);

        $tags = $this->useCase->getTermTags();

        $this->assertEquals($expectedTags, $tags);
    }

    public function testUrlBaseHandlesEmptyRequestUri(): void
    {
        unset($_SERVER['REQUEST_URI']);

        $expectedTags = ['tags'];
        $this->termRepository->method('getAllTexts')
            ->willReturn($expectedTags);

        $tags = $this->useCase->getTermTags();

        $this->assertEquals($expectedTags, $tags);
    }

    public function testUrlBaseHandlesRootPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $expectedTags = ['root'];
        $this->termRepository->method('getAllTexts')
            ->willReturn($expectedTags);

        $tags = $this->useCase->getTermTags();

        $this->assertEquals($expectedTags, $tags);
        $this->assertEquals('', $_SESSION['TAGS_URL_BASE']);
    }
}
