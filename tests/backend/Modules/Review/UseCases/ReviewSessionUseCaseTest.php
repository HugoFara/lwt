<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\UseCases;

use Lwt\Modules\Review\Application\UseCases\StartReviewSession;
use Lwt\Modules\Review\Application\UseCases\GetNextTerm;
use Lwt\Modules\Review\Application\UseCases\SubmitAnswer;
use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;
use Lwt\Modules\Review\Domain\ReviewSession;
use Lwt\Modules\Review\Domain\ReviewConfiguration;
use Lwt\Modules\Review\Domain\TestWord;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for Review Session use cases.
 *
 * Tests StartReviewSession, GetNextTerm, and SubmitAnswer use cases
 * which form the core review/testing workflow.
 *
 * @covers \Lwt\Modules\Review\Application\UseCases\StartReviewSession
 * @covers \Lwt\Modules\Review\Application\UseCases\GetNextTerm
 * @covers \Lwt\Modules\Review\Application\UseCases\SubmitAnswer
 */
class ReviewSessionUseCaseTest extends TestCase
{
    /** @var ReviewRepositoryInterface&MockObject */
    private ReviewRepositoryInterface $repository;

    /** @var SessionStateManager&MockObject */
    private SessionStateManager $sessionManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ReviewRepositoryInterface::class);
        $this->sessionManager = $this->createMock(SessionStateManager::class);
    }

    // ===================================
    // START REVIEW SESSION TESTS
    // ===================================

    public function testStartReviewSessionWithValidConfigSucceeds(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository
            ->method('validateSingleLanguage')
            ->willReturn(['valid' => true]);

        $this->repository
            ->method('getLanguageIdFromConfig')
            ->willReturn(1);

        $this->repository
            ->method('getReviewCounts')
            ->willReturn(['due' => 10, 'total' => 50]);

        $this->sessionManager
            ->expects($this->once())
            ->method('saveSession');

        $startSession = new StartReviewSession($this->repository, $this->sessionManager);
        $result = $startSession->execute($config);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(ReviewSession::class, $result['session']);
        $this->assertEquals(10, $result['counts']['due']);
        $this->assertEquals(50, $result['counts']['total']);
        $this->assertEquals(1, $result['langId']);
    }

    public function testStartReviewSessionWithInvalidConfigFails(): void
    {
        $config = new ReviewConfiguration('', [], 1); // Empty test key = invalid

        $startSession = new StartReviewSession($this->repository, $this->sessionManager);
        $result = $startSession->execute($config);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid', $result['error']);
    }

    public function testStartReviewSessionWithMultipleLanguagesFails(): void
    {
        $config = ReviewConfiguration::fromWords([1, 2, 3], 1);

        $this->repository
            ->method('validateSingleLanguage')
            ->willReturn([
                'valid' => false,
                'error' => 'Words belong to multiple languages'
            ]);

        $startSession = new StartReviewSession($this->repository, $this->sessionManager);
        $result = $startSession->execute($config);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('multiple languages', $result['error']);
    }

    public function testStartReviewSessionWithNoWordsFails(): void
    {
        $config = ReviewConfiguration::fromLanguage(999, 1);

        $this->repository
            ->method('validateSingleLanguage')
            ->willReturn(['valid' => true]);

        $this->repository
            ->method('getLanguageIdFromConfig')
            ->willReturn(null);

        $startSession = new StartReviewSession($this->repository, $this->sessionManager);
        $result = $startSession->execute($config);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No words', $result['error']);
    }

    public function testStartReviewSessionFromText(): void
    {
        $config = ReviewConfiguration::fromText(42, 2);

        $this->repository
            ->method('validateSingleLanguage')
            ->willReturn(['valid' => true]);

        $this->repository
            ->method('getLanguageIdFromConfig')
            ->willReturn(1);

        $this->repository
            ->method('getReviewCounts')
            ->willReturn(['due' => 5, 'total' => 20]);

        $startSession = new StartReviewSession($this->repository, $this->sessionManager);
        $result = $startSession->execute($config);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['counts']['due']);
    }

    public function testStartReviewSessionFromWords(): void
    {
        $config = ReviewConfiguration::fromWords([1, 2, 3, 4, 5], 1);

        $this->repository
            ->method('validateSingleLanguage')
            ->willReturn(['valid' => true]);

        $this->repository
            ->method('getLanguageIdFromConfig')
            ->willReturn(1);

        $this->repository
            ->method('getReviewCounts')
            ->willReturn(['due' => 3, 'total' => 5]);

        $startSession = new StartReviewSession($this->repository, $this->sessionManager);
        $result = $startSession->execute($config);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['counts']['due']);
        $this->assertEquals(5, $result['counts']['total']);
    }

    // ===================================
    // GET NEXT TERM TESTS
    // ===================================

    public function testGetNextTermReturnsWordData(): void
    {
        // Use test type 2 which doesn't call TagsFacade::getWordTagList
        $config = ReviewConfiguration::fromLanguage(1, ReviewConfiguration::TYPE_TRANSLATION_TO_TERM);

        $testWord = $this->createTestWord(
            id: 100,
            text: 'hello',
            translation: 'hola',
            sentence: 'Say {hello} to the world.'
        );

        $this->repository
            ->method('findNextWordForReview')
            ->willReturn($testWord);

        $this->repository
            ->method('getSentenceForWord')
            ->willReturn(['sentence' => 'Say {hello} to the world.']);

        $getNextTerm = new GetNextTerm($this->repository);
        $result = $getNextTerm->execute($config);

        $this->assertEquals(100, $result['word_id']);
        $this->assertEquals('hello', $result['word_text']);
        $this->assertNotEmpty($result['solution']);
        $this->assertNotEmpty($result['group']);
    }

    public function testGetNextTermReturnsEmptyWhenNoWordsDue(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository
            ->method('findNextWordForReview')
            ->willReturn(null);

        $getNextTerm = new GetNextTerm($this->repository);
        $result = $getNextTerm->execute($config);

        $this->assertEquals(0, $result['word_id']);
        $this->assertEquals('', $result['word_text']);
        $this->assertEquals('', $result['solution']);
    }

    /**
     * Test type 1 shows term and solution contains translation.
     *
     * Note: This test is skipped because GetNextTerm::getSolution() for type 1
     * calls TagsFacade::getWordTagList() which requires database access.
     * In integration tests, this would be covered properly.
     */
    public function testGetNextTermType1ShowsTermGuessesTranstation(): void
    {
        $this->markTestSkipped(
            'Test type 1 requires database for TagsFacade::getWordTagList(). ' .
            'See ReviewFacadeTest for integration coverage.'
        );
    }

    public function testGetNextTermType2HidesTermGuessesFromTranslation(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, ReviewConfiguration::TYPE_TRANSLATION_TO_TERM);

        $testWord = $this->createTestWord(
            id: 100,
            text: 'book',
            translation: 'libro',
            sentence: 'I read a {book}.'
        );

        $this->repository
            ->method('findNextWordForReview')
            ->willReturn($testWord);

        $this->repository
            ->method('getSentenceForWord')
            ->willReturn(['sentence' => 'I read a {book}.']);

        $getNextTerm = new GetNextTerm($this->repository);
        $result = $getNextTerm->execute($config);

        // Group should contain hidden placeholder
        $this->assertStringContainsString('[...]', $result['group']);
        // Solution should be the term itself
        $this->assertEquals('book', $result['solution']);
    }

    public function testGetNextTermType3UseSentenceContext(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, ReviewConfiguration::TYPE_SENTENCE_TO_TERM);

        $testWord = $this->createTestWord(
            id: 100,
            text: 'running',
            translation: 'corriendo',
            sentence: 'She is {running} in the park.'
        );

        $this->repository
            ->method('findNextWordForReview')
            ->willReturn($testWord);

        $this->repository
            ->method('getSentenceForWord')
            ->willReturn(['sentence' => 'She is {running} in the park.']);

        $getNextTerm = new GetNextTerm($this->repository);
        $result = $getNextTerm->execute($config);

        // Should have sentence context
        $this->assertStringContainsString('park', $result['group']);
    }

    public function testGetNextTermWordModeReturnsOnlyWord(): void
    {
        // Use test type 2 (translation to term) in word mode to avoid TagsFacade call
        $config = ReviewConfiguration::fromLanguage(1, ReviewConfiguration::TYPE_TRANSLATION_TO_TERM, true);

        $testWord = $this->createTestWord(
            id: 100,
            text: 'word',
            translation: 'palabra',
            sentence: null
        );

        $this->repository
            ->method('findNextWordForReview')
            ->willReturn($testWord);

        $getNextTerm = new GetNextTerm($this->repository);
        $result = $getNextTerm->execute($config);

        // Word mode should show just the word without sentence context
        $this->assertEquals('word', $result['word_text']);
    }

    // ===================================
    // SUBMIT ANSWER TESTS
    // ===================================

    public function testSubmitAnswerWithValidStatusSucceeds(): void
    {
        $wordId = 100;
        $newStatus = 3;

        $this->repository
            ->method('updateWordStatus')
            ->with($wordId, $newStatus)
            ->willReturn([
                'oldStatus' => 2,
                'newStatus' => 3,
                'oldScore' => -5,
                'newScore' => 0
            ]);

        $session = ReviewSession::start(10);
        $this->sessionManager
            ->method('getSession')
            ->willReturn($session);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->execute($wordId, $newStatus);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['oldStatus']);
        $this->assertEquals(3, $result['newStatus']);
        $this->assertEquals(1, $result['statusChange']); // Positive change
    }

    public function testSubmitAnswerWithInvalidStatusFails(): void
    {
        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->execute(100, 6); // Invalid status

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid', $result['error']);
    }

    /**
     * @dataProvider validStatusProvider
     */
    public function testSubmitAnswerAcceptsValidStatuses(int $status): void
    {
        $this->repository
            ->method('updateWordStatus')
            ->willReturn([
                'oldStatus' => 1,
                'newStatus' => $status,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->execute(100, $status);

        $this->assertTrue($result['success']);
    }

    public static function validStatusProvider(): array
    {
        return [
            'status_1' => [1],
            'status_2' => [2],
            'status_3' => [3],
            'status_4' => [4],
            'status_5' => [5],
            'ignored_98' => [98],
            'well_known_99' => [99],
        ];
    }

    public function testSubmitAnswerUpdatesSessionProgress(): void
    {
        $this->repository
            ->method('updateWordStatus')
            ->willReturn([
                'oldStatus' => 2,
                'newStatus' => 3,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $session = ReviewSession::start(5);
        $this->sessionManager
            ->method('getSession')
            ->willReturn($session);

        $this->sessionManager
            ->expects($this->once())
            ->method('saveSession');

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->execute(100, 3);

        $this->assertArrayHasKey('progress', $result);
        $this->assertEquals(5, $result['progress']['total']);
    }

    public function testSubmitAnswerCalculatesStatusChangeCorrectly(): void
    {
        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);

        // Test increase
        $this->repository
            ->method('updateWordStatus')
            ->willReturn([
                'oldStatus' => 2,
                'newStatus' => 3,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $result = $submitAnswer->execute(100, 3);
        $this->assertEquals(1, $result['statusChange']);
    }

    public function testSubmitAnswerDetectsStatusDecrease(): void
    {
        $this->repository
            ->method('updateWordStatus')
            ->willReturn([
                'oldStatus' => 4,
                'newStatus' => 3,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->execute(100, 3);

        $this->assertEquals(-1, $result['statusChange']);
    }

    public function testSubmitAnswerWithNoChange(): void
    {
        $this->repository
            ->method('updateWordStatus')
            ->willReturn([
                'oldStatus' => 3,
                'newStatus' => 3,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->execute(100, 3);

        $this->assertEquals(0, $result['statusChange']);
    }

    // ===================================
    // SUBMIT ANSWER WITH CHANGE TESTS
    // ===================================

    public function testSubmitAnswerWithPositiveChange(): void
    {
        $this->repository
            ->method('getWordStatus')
            ->willReturn(2);

        $this->repository
            ->method('updateWordStatus')
            ->with(100, 3) // 2 + 1 = 3
            ->willReturn([
                'oldStatus' => 2,
                'newStatus' => 3,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->executeWithChange(100, 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['newStatus']);
    }

    public function testSubmitAnswerWithNegativeChange(): void
    {
        $this->repository
            ->method('getWordStatus')
            ->willReturn(3);

        $this->repository
            ->method('updateWordStatus')
            ->with(100, 2) // 3 - 1 = 2
            ->willReturn([
                'oldStatus' => 3,
                'newStatus' => 2,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->executeWithChange(100, -1);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['newStatus']);
    }

    public function testSubmitAnswerStatus5ToWellKnown(): void
    {
        $this->repository
            ->method('getWordStatus')
            ->willReturn(5);

        $this->repository
            ->method('updateWordStatus')
            ->with(100, 99) // 5 + 1 = 99 (well-known)
            ->willReturn([
                'oldStatus' => 5,
                'newStatus' => 99,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->executeWithChange(100, 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(99, $result['newStatus']);
    }

    public function testSubmitAnswerStatus1ToIgnored(): void
    {
        $this->repository
            ->method('getWordStatus')
            ->willReturn(1);

        $this->repository
            ->method('updateWordStatus')
            ->with(100, 98) // 1 - 1 = 98 (ignored)
            ->willReturn([
                'oldStatus' => 1,
                'newStatus' => 98,
                'oldScore' => 0,
                'newScore' => 0
            ]);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->executeWithChange(100, -1);

        $this->assertTrue($result['success']);
        $this->assertEquals(98, $result['newStatus']);
    }

    public function testSubmitAnswerWordNotFound(): void
    {
        $this->repository
            ->method('getWordStatus')
            ->willReturn(null);

        $submitAnswer = new SubmitAnswer($this->repository, $this->sessionManager);
        $result = $submitAnswer->executeWithChange(999, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    // ===================================
    // HELPER METHODS
    // ===================================

    /**
     * Create a mock TestWord instance.
     */
    private function createTestWord(
        int $id,
        string $text,
        string $translation,
        ?string $sentence = null,
        int $status = 2,
        int $languageId = 1
    ): TestWord {
        return new TestWord(
            id: $id,
            text: $text,
            textLowercase: strtolower($text),
            translation: $translation,
            romanization: null,
            sentence: $sentence,
            languageId: $languageId,
            status: $status,
            score: 0,
            daysOld: 0
        );
    }
}
