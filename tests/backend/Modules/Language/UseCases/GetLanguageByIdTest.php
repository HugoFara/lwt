<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\UseCases;

use Lwt\Modules\Language\Application\UseCases\GetLanguageById;
use Lwt\Modules\Language\Domain\Language;
use Lwt\Modules\Language\Domain\LanguageRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetLanguageById use case.
 */
class GetLanguageByIdTest extends TestCase
{
    /** @var LanguageRepositoryInterface&MockObject */
    private LanguageRepositoryInterface $repository;
    private GetLanguageById $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(LanguageRepositoryInterface::class);
        $this->useCase = new GetLanguageById($this->repository);
    }

    private function createLanguage(int $id, string $name): Language
    {
        return Language::reconstitute(
            id: $id,
            name: $name,
            dict1Uri: 'https://dict1.example.com/###',
            dict2Uri: '',
            translatorUri: 'https://translate.example.com/###',
            dict1PopUp: true,
            dict2PopUp: false,
            translatorPopUp: true,
            sourceLang: 'en',
            targetLang: 'es',
            exportTemplate: '',
            textSize: 100,
            characterSubstitutions: '',
            regexpSplitSentences: '.!?',
            exceptionsSplitSentences: 'Mr. Mrs.',
            regexpWordCharacters: 'a-zA-Z',
            removeSpaces: false,
            splitEachChar: false,
            rightToLeft: false,
            ttsVoiceApi: '',
            showRomanization: false,
            parserType: null,
            localDictMode: 0
        );
    }

    /**
     * Create a new Language using the factory method (for empty languages).
     */
    private function createNewLanguage(string $name = 'New Language'): Language
    {
        return Language::create($name, '', '.!?', 'a-zA-Z');
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsLanguageWhenFound(): void
    {
        $language = $this->createLanguage(1, 'English');

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($language);

        $result = $this->useCase->execute(1);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertEquals(1, $result->id()->toInt());
        $this->assertEquals('English', $result->name());
    }

    public function testExecuteReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->useCase->execute(999);

        $this->assertNull($result);
    }

    public function testExecuteReturnsEmptyLanguageForZeroId(): void
    {
        $emptyLanguage = $this->createNewLanguage();

        $this->repository->expects($this->once())
            ->method('createEmpty')
            ->willReturn($emptyLanguage);

        $this->repository->expects($this->never())
            ->method('find');

        $result = $this->useCase->execute(0);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertTrue($result->id()->isNew());
    }

    public function testExecuteReturnsEmptyLanguageForNegativeId(): void
    {
        $emptyLanguage = $this->createNewLanguage();

        $this->repository->expects($this->once())
            ->method('createEmpty')
            ->willReturn($emptyLanguage);

        $result = $this->useCase->execute(-5);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertTrue($result->id()->isNew());
    }

    // =========================================================================
    // createEmpty() Tests
    // =========================================================================

    public function testCreateEmptyReturnsNewLanguage(): void
    {
        $emptyLanguage = $this->createNewLanguage();

        $this->repository->expects($this->once())
            ->method('createEmpty')
            ->willReturn($emptyLanguage);

        $result = $this->useCase->createEmpty();

        $this->assertInstanceOf(Language::class, $result);
        $this->assertTrue($result->id()->isNew());
    }

    // =========================================================================
    // exists() Tests
    // =========================================================================

    public function testExistsReturnsTrueWhenFound(): void
    {
        $this->repository->expects($this->once())
            ->method('exists')
            ->with(1)
            ->willReturn(true);

        $result = $this->useCase->exists(1);

        $this->assertTrue($result);
    }

    public function testExistsReturnsFalseWhenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('exists')
            ->with(999)
            ->willReturn(false);

        $result = $this->useCase->exists(999);

        $this->assertFalse($result);
    }

    // =========================================================================
    // toViewObject() Tests
    // =========================================================================

    public function testToViewObjectConvertsLanguageToStdClass(): void
    {
        $language = $this->createLanguage(42, 'Spanish');

        $view = $this->useCase->toViewObject($language);

        $this->assertInstanceOf(\stdClass::class, $view);
        $this->assertEquals(42, $view->id);
        $this->assertEquals('Spanish', $view->name);
        $this->assertEquals('https://dict1.example.com/###', $view->dict1uri);
        $this->assertEquals('', $view->dict2uri);
        $this->assertEquals('https://translate.example.com/###', $view->translator);
        $this->assertTrue($view->dict1popup);
        $this->assertFalse($view->dict2popup);
        $this->assertTrue($view->translatorpopup);
        $this->assertEquals('en', $view->sourcelang);
        $this->assertEquals('es', $view->targetlang);
        $this->assertEquals(100, $view->textsize);
        $this->assertFalse($view->removespaces);
        $this->assertFalse($view->spliteachchar);
        $this->assertFalse($view->rightoleft);
        $this->assertFalse($view->showromanization);
    }

    public function testToViewObjectHandlesAllProperties(): void
    {
        $language = Language::reconstitute(
            id: 1,
            name: 'Japanese',
            dict1Uri: 'https://jisho.org/###',
            dict2Uri: 'https://dict2.example.com/###',
            translatorUri: 'https://deepl.com/###',
            dict1PopUp: false,
            dict2PopUp: true,
            translatorPopUp: false,
            sourceLang: 'ja',
            targetLang: 'en',
            exportTemplate: '$w - $t',
            textSize: 150,
            characterSubstitutions: 'ー=-',
            regexpSplitSentences: '。！？',
            exceptionsSplitSentences: '',
            regexpWordCharacters: '\p{Han}\p{Hiragana}\p{Katakana}',
            removeSpaces: true,
            splitEachChar: true,
            rightToLeft: false,
            ttsVoiceApi: 'ja-JP',
            showRomanization: true,
            parserType: 'mecab',
            localDictMode: 1
        );

        $view = $this->useCase->toViewObject($language);

        $this->assertEquals('Japanese', $view->name);
        $this->assertEquals(150, $view->textsize);
        $this->assertTrue($view->removespaces);
        $this->assertTrue($view->spliteachchar);
        $this->assertTrue($view->showromanization);
        $this->assertEquals('mecab', $view->parsertype);
        $this->assertEquals(1, $view->localdictmode);
    }

    // =========================================================================
    // isDuplicateName() Tests
    // =========================================================================

    public function testIsDuplicateNameReturnsTrueWhenExists(): void
    {
        $this->repository->expects($this->once())
            ->method('nameExists')
            ->with('English', null)
            ->willReturn(true);

        $result = $this->useCase->isDuplicateName('English');

        $this->assertTrue($result);
    }

    public function testIsDuplicateNameReturnsFalseWhenNotExists(): void
    {
        $this->repository->expects($this->once())
            ->method('nameExists')
            ->with('NewLanguage', null)
            ->willReturn(false);

        $result = $this->useCase->isDuplicateName('NewLanguage');

        $this->assertFalse($result);
    }

    public function testIsDuplicateNameExcludesCurrentLanguage(): void
    {
        $this->repository->expects($this->once())
            ->method('nameExists')
            ->with('English', 5)
            ->willReturn(false);

        $result = $this->useCase->isDuplicateName('English', 5);

        $this->assertFalse($result);
    }

    public function testIsDuplicateNameReturnsFalseForEmptyName(): void
    {
        $this->repository->expects($this->never())
            ->method('nameExists');

        $result = $this->useCase->isDuplicateName('');

        $this->assertFalse($result);
    }

    public function testIsDuplicateNameReturnsFalseForWhitespaceOnlyName(): void
    {
        $this->repository->expects($this->never())
            ->method('nameExists');

        $result = $this->useCase->isDuplicateName('   ');

        $this->assertFalse($result);
    }

    public function testIsDuplicateNameTrimsWhitespace(): void
    {
        $this->repository->expects($this->once())
            ->method('nameExists')
            ->with('English', null)
            ->willReturn(true);

        $result = $this->useCase->isDuplicateName('  English  ');

        $this->assertTrue($result);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteWithLargeId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(PHP_INT_MAX)
            ->willReturn(null);

        $result = $this->useCase->execute(PHP_INT_MAX);

        $this->assertNull($result);
    }

    public function testExecuteWithRTLLanguage(): void
    {
        $rtlLanguage = Language::reconstitute(
            id: 1,
            name: 'Arabic',
            dict1Uri: '',
            dict2Uri: '',
            translatorUri: '',
            dict1PopUp: false,
            dict2PopUp: false,
            translatorPopUp: false,
            sourceLang: 'ar',
            targetLang: 'en',
            exportTemplate: '',
            textSize: 100,
            characterSubstitutions: '',
            regexpSplitSentences: '.!?',
            exceptionsSplitSentences: '',
            regexpWordCharacters: '\p{Arabic}',
            removeSpaces: false,
            splitEachChar: false,
            rightToLeft: true,
            ttsVoiceApi: '',
            showRomanization: false,
            parserType: null,
            localDictMode: 0
        );

        $this->repository->method('find')
            ->willReturn($rtlLanguage);

        $result = $this->useCase->execute(1);

        $this->assertTrue($result->rightToLeft());
    }
}
