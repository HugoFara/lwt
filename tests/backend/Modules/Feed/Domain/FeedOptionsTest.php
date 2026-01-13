<?php

declare(strict_types=1);

namespace Tests\Modules\Feed\Domain;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Feed\Domain\FeedOptions;

/**
 * Tests for FeedOptions value object.
 *
 * @covers \Lwt\Modules\Feed\Domain\FeedOptions
 */
class FeedOptionsTest extends TestCase
{
    // =========================================================================
    // Factory Method Tests - fromString()
    // =========================================================================

    public function testFromStringWithEmptyString(): void
    {
        $options = FeedOptions::fromString('');

        $this->assertSame([], $options->all());
    }

    public function testFromStringWithWhitespaceOnly(): void
    {
        $options = FeedOptions::fromString('   ');

        $this->assertSame([], $options->all());
    }

    public function testFromStringSingleOption(): void
    {
        $options = FeedOptions::fromString('edit_text=1');

        $this->assertSame('1', $options->get('edit_text'));
    }

    public function testFromStringMultipleOptions(): void
    {
        $options = FeedOptions::fromString('edit_text=1,autoupdate=2h,max_links=100');

        $this->assertSame('1', $options->get('edit_text'));
        $this->assertSame('2h', $options->get('autoupdate'));
        $this->assertSame('100', $options->get('max_links'));
    }

    public function testFromStringTrimsValues(): void
    {
        $options = FeedOptions::fromString('  edit_text = 1 , autoupdate = 2h  ');

        $this->assertSame('1', $options->get('edit_text'));
        $this->assertSame('2h', $options->get('autoupdate'));
    }

    public function testFromStringIgnoresEmptyKeys(): void
    {
        $options = FeedOptions::fromString('=value,edit_text=1');

        $this->assertFalse($options->has(''));
        $this->assertSame('1', $options->get('edit_text'));
    }

    public function testFromStringHandlesValueWithEquals(): void
    {
        $options = FeedOptions::fromString('template=a=b');

        $this->assertSame('a=b', $options->get('template'));
    }

    public function testFromStringHandlesEmptyValue(): void
    {
        $options = FeedOptions::fromString('charset=');

        $this->assertSame('', $options->get('charset'));
    }

    public function testFromStringHandlesNoEqualsSign(): void
    {
        $options = FeedOptions::fromString('edit_text,autoupdate=2h');

        $this->assertSame('', $options->get('edit_text'));
        $this->assertSame('2h', $options->get('autoupdate'));
    }

    // =========================================================================
    // Factory Method Tests - fromArray()
    // =========================================================================

    public function testFromArrayWithEmptyArray(): void
    {
        $options = FeedOptions::fromArray([]);

        $this->assertSame([], $options->all());
    }

    public function testFromArrayWithStringValues(): void
    {
        $options = FeedOptions::fromArray([
            'edit_text' => '1',
            'autoupdate' => '2h',
        ]);

        $this->assertSame('1', $options->get('edit_text'));
        $this->assertSame('2h', $options->get('autoupdate'));
    }

    public function testFromArrayWithIntValues(): void
    {
        $options = FeedOptions::fromArray([
            'max_links' => 100,
            'max_texts' => 50,
        ]);

        $this->assertSame('100', $options->get('max_links'));
        $this->assertSame('50', $options->get('max_texts'));
    }

    public function testFromArrayWithBoolValues(): void
    {
        $options = FeedOptions::fromArray([
            'edit_text' => true,
            'disabled' => false,
        ]);

        $this->assertSame('1', $options->get('edit_text'));
        $this->assertSame('0', $options->get('disabled'));
    }

    public function testFromArraySkipsNullValues(): void
    {
        $options = FeedOptions::fromArray([
            'edit_text' => '1',
            'charset' => null,
        ]);

        $this->assertSame('1', $options->get('edit_text'));
        $this->assertFalse($options->has('charset'));
    }

    public function testFromArraySkipsEmptyStringValues(): void
    {
        $options = FeedOptions::fromArray([
            'edit_text' => '1',
            'charset' => '',
        ]);

        $this->assertSame('1', $options->get('edit_text'));
        $this->assertFalse($options->has('charset'));
    }

    // =========================================================================
    // Factory Method Tests - empty()
    // =========================================================================

    public function testEmptyReturnsEmptyOptions(): void
    {
        $options = FeedOptions::empty();

        $this->assertSame([], $options->all());
        $this->assertFalse($options->has('edit_text'));
    }

    // =========================================================================
    // Accessor Method Tests - get(), has(), all()
    // =========================================================================

    public function testGetReturnsNullForMissingKey(): void
    {
        $options = FeedOptions::fromString('edit_text=1');

        $this->assertNull($options->get('nonexistent'));
    }

    public function testGetReturnsValueForExistingKey(): void
    {
        $options = FeedOptions::fromString('edit_text=1');

        $this->assertSame('1', $options->get('edit_text'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $options = FeedOptions::fromString('edit_text=1');

        $this->assertTrue($options->has('edit_text'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $options = FeedOptions::fromString('edit_text=1');

        $this->assertFalse($options->has('nonexistent'));
    }

    public function testAllReturnsAllOptions(): void
    {
        $options = FeedOptions::fromString('edit_text=1,autoupdate=2h');

        $all = $options->all();
        $this->assertCount(2, $all);
        $this->assertSame('1', $all['edit_text']);
        $this->assertSame('2h', $all['autoupdate']);
    }

    // =========================================================================
    // Serialization Tests - toString()
    // =========================================================================

    public function testToStringWithEmptyOptions(): void
    {
        $options = FeedOptions::empty();

        $this->assertSame('', $options->toString());
    }

    public function testToStringSingleOption(): void
    {
        $options = FeedOptions::fromString('edit_text=1');

        $this->assertSame('edit_text=1', $options->toString());
    }

    public function testToStringMultipleOptions(): void
    {
        $options = FeedOptions::fromArray([
            'edit_text' => '1',
            'autoupdate' => '2h',
        ]);

        $str = $options->toString();
        $this->assertStringContainsString('edit_text=1', $str);
        $this->assertStringContainsString('autoupdate=2h', $str);
        $this->assertStringContainsString(',', $str);
    }

    public function testToStringRoundtrip(): void
    {
        $original = 'edit_text=1,autoupdate=2h,max_links=100';
        $options = FeedOptions::fromString($original);
        $restored = FeedOptions::fromString($options->toString());

        $this->assertSame($options->all(), $restored->all());
    }

    // =========================================================================
    // Typed Accessor Tests - editText()
    // =========================================================================

    public function testEditTextReturnsFalseWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertFalse($options->editText());
    }

    public function testEditTextReturnsFalseForZero(): void
    {
        $options = FeedOptions::fromString('edit_text=0');

        $this->assertFalse($options->editText());
    }

    public function testEditTextReturnsTrueForOne(): void
    {
        $options = FeedOptions::fromString('edit_text=1');

        $this->assertTrue($options->editText());
    }

    public function testEditTextReturnsFalseForOtherValues(): void
    {
        $options = FeedOptions::fromString('edit_text=yes');

        $this->assertFalse($options->editText());
    }

    // =========================================================================
    // Typed Accessor Tests - autoUpdate()
    // =========================================================================

    public function testAutoUpdateReturnsNullWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertNull($options->autoUpdate());
    }

    public function testAutoUpdateReturnsNullForEmptyString(): void
    {
        $options = FeedOptions::fromString('autoupdate=');

        $this->assertNull($options->autoUpdate());
    }

    public function testAutoUpdateReturnsValue(): void
    {
        $options = FeedOptions::fromString('autoupdate=2h');

        $this->assertSame('2h', $options->autoUpdate());
    }

    // =========================================================================
    // Typed Accessor Tests - autoUpdateSeconds()
    // =========================================================================

    public function testAutoUpdateSecondsReturnsNullWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertNull($options->autoUpdateSeconds());
    }

    public function testAutoUpdateSecondsForHours(): void
    {
        $options = FeedOptions::fromString('autoupdate=2h');

        $this->assertSame(2 * 60 * 60, $options->autoUpdateSeconds());
    }

    public function testAutoUpdateSecondsForDays(): void
    {
        $options = FeedOptions::fromString('autoupdate=3d');

        $this->assertSame(3 * 24 * 60 * 60, $options->autoUpdateSeconds());
    }

    public function testAutoUpdateSecondsForWeeks(): void
    {
        $options = FeedOptions::fromString('autoupdate=1w');

        $this->assertSame(7 * 24 * 60 * 60, $options->autoUpdateSeconds());
    }

    public function testAutoUpdateSecondsReturnsNullForFormatWithoutUnit(): void
    {
        // A string without h/d/w units returns null
        $options = FeedOptions::fromString('autoupdate=abc');
        $this->assertNull($options->autoUpdateSeconds());
    }

    public function testAutoUpdateSecondsReturnsNullForNumericOnly(): void
    {
        // Just a number without unit returns null
        $options = FeedOptions::fromString('autoupdate=123');
        $this->assertNull($options->autoUpdateSeconds());
    }

    public function testAutoUpdateSecondsWithZeroValue(): void
    {
        $options = FeedOptions::fromString('autoupdate=0h');

        $this->assertSame(0, $options->autoUpdateSeconds());
    }

    // =========================================================================
    // Typed Accessor Tests - maxLinks()
    // =========================================================================

    public function testMaxLinksReturnsNullWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertNull($options->maxLinks());
    }

    public function testMaxLinksReturnsNullForEmptyString(): void
    {
        $options = FeedOptions::fromString('max_links=');

        $this->assertNull($options->maxLinks());
    }

    public function testMaxLinksReturnsIntValue(): void
    {
        $options = FeedOptions::fromString('max_links=100');

        $this->assertSame(100, $options->maxLinks());
    }

    // =========================================================================
    // Typed Accessor Tests - maxTexts()
    // =========================================================================

    public function testMaxTextsReturnsNullWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertNull($options->maxTexts());
    }

    public function testMaxTextsReturnsNullForEmptyString(): void
    {
        $options = FeedOptions::fromString('max_texts=');

        $this->assertNull($options->maxTexts());
    }

    public function testMaxTextsReturnsIntValue(): void
    {
        $options = FeedOptions::fromString('max_texts=50');

        $this->assertSame(50, $options->maxTexts());
    }

    // =========================================================================
    // Typed Accessor Tests - charset()
    // =========================================================================

    public function testCharsetReturnsNullWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertNull($options->charset());
    }

    public function testCharsetReturnsNullForEmptyString(): void
    {
        $options = FeedOptions::fromString('charset=');

        $this->assertNull($options->charset());
    }

    public function testCharsetReturnsValue(): void
    {
        $options = FeedOptions::fromString('charset=UTF-8');

        $this->assertSame('UTF-8', $options->charset());
    }

    // =========================================================================
    // Typed Accessor Tests - tag()
    // =========================================================================

    public function testTagReturnsNullWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertNull($options->tag());
    }

    public function testTagReturnsNullForEmptyString(): void
    {
        $options = FeedOptions::fromString('tag=');

        $this->assertNull($options->tag());
    }

    public function testTagReturnsValue(): void
    {
        $options = FeedOptions::fromString('tag=news');

        $this->assertSame('news', $options->tag());
    }

    // =========================================================================
    // Typed Accessor Tests - articleSource()
    // =========================================================================

    public function testArticleSourceReturnsNullWhenNotSet(): void
    {
        $options = FeedOptions::empty();

        $this->assertNull($options->articleSource());
    }

    public function testArticleSourceReturnsNullForEmptyString(): void
    {
        $options = FeedOptions::fromString('article_source=');

        $this->assertNull($options->articleSource());
    }

    public function testArticleSourceReturnsValue(): void
    {
        $options = FeedOptions::fromString('article_source=description');

        $this->assertSame('description', $options->articleSource());
    }

    // =========================================================================
    // Builder Method Tests - withEditText()
    // =========================================================================

    public function testWithEditTextTrueAddsOption(): void
    {
        $options = FeedOptions::empty();
        $newOptions = $options->withEditText(true);

        $this->assertTrue($newOptions->editText());
        $this->assertFalse($options->editText()); // Original unchanged
    }

    public function testWithEditTextFalseRemovesOption(): void
    {
        $options = FeedOptions::fromString('edit_text=1');
        $newOptions = $options->withEditText(false);

        $this->assertFalse($newOptions->editText());
        $this->assertFalse($newOptions->has('edit_text'));
    }

    public function testWithEditTextIsImmutable(): void
    {
        $original = FeedOptions::fromString('edit_text=1');
        $modified = $original->withEditText(false);

        $this->assertTrue($original->editText());
        $this->assertFalse($modified->editText());
    }

    // =========================================================================
    // Builder Method Tests - withAutoUpdate()
    // =========================================================================

    public function testWithAutoUpdateSetsValue(): void
    {
        $options = FeedOptions::empty();
        $newOptions = $options->withAutoUpdate('2h');

        $this->assertSame('2h', $newOptions->autoUpdate());
    }

    public function testWithAutoUpdateNullRemovesOption(): void
    {
        $options = FeedOptions::fromString('autoupdate=2h');
        $newOptions = $options->withAutoUpdate(null);

        $this->assertNull($newOptions->autoUpdate());
        $this->assertFalse($newOptions->has('autoupdate'));
    }

    public function testWithAutoUpdateEmptyStringRemovesOption(): void
    {
        $options = FeedOptions::fromString('autoupdate=2h');
        $newOptions = $options->withAutoUpdate('');

        $this->assertNull($newOptions->autoUpdate());
    }

    // =========================================================================
    // Builder Method Tests - withMaxLinks()
    // =========================================================================

    public function testWithMaxLinksSetsValue(): void
    {
        $options = FeedOptions::empty();
        $newOptions = $options->withMaxLinks(100);

        $this->assertSame(100, $newOptions->maxLinks());
    }

    public function testWithMaxLinksNullRemovesOption(): void
    {
        $options = FeedOptions::fromString('max_links=100');
        $newOptions = $options->withMaxLinks(null);

        $this->assertNull($newOptions->maxLinks());
    }

    public function testWithMaxLinksZeroRemovesOption(): void
    {
        $options = FeedOptions::fromString('max_links=100');
        $newOptions = $options->withMaxLinks(0);

        $this->assertNull($newOptions->maxLinks());
    }

    public function testWithMaxLinksNegativeRemovesOption(): void
    {
        $options = FeedOptions::fromString('max_links=100');
        $newOptions = $options->withMaxLinks(-1);

        $this->assertNull($newOptions->maxLinks());
    }

    // =========================================================================
    // Builder Method Tests - withMaxTexts()
    // =========================================================================

    public function testWithMaxTextsSetsValue(): void
    {
        $options = FeedOptions::empty();
        $newOptions = $options->withMaxTexts(50);

        $this->assertSame(50, $newOptions->maxTexts());
    }

    public function testWithMaxTextsNullRemovesOption(): void
    {
        $options = FeedOptions::fromString('max_texts=50');
        $newOptions = $options->withMaxTexts(null);

        $this->assertNull($newOptions->maxTexts());
    }

    public function testWithMaxTextsZeroRemovesOption(): void
    {
        $options = FeedOptions::fromString('max_texts=50');
        $newOptions = $options->withMaxTexts(0);

        $this->assertNull($newOptions->maxTexts());
    }

    // =========================================================================
    // Builder Method Tests - withCharset()
    // =========================================================================

    public function testWithCharsetSetsValue(): void
    {
        $options = FeedOptions::empty();
        $newOptions = $options->withCharset('UTF-8');

        $this->assertSame('UTF-8', $newOptions->charset());
    }

    public function testWithCharsetNullRemovesOption(): void
    {
        $options = FeedOptions::fromString('charset=UTF-8');
        $newOptions = $options->withCharset(null);

        $this->assertNull($newOptions->charset());
    }

    public function testWithCharsetEmptyStringRemovesOption(): void
    {
        $options = FeedOptions::fromString('charset=UTF-8');
        $newOptions = $options->withCharset('');

        $this->assertNull($newOptions->charset());
    }

    // =========================================================================
    // Builder Method Tests - withTag()
    // =========================================================================

    public function testWithTagSetsValue(): void
    {
        $options = FeedOptions::empty();
        $newOptions = $options->withTag('news');

        $this->assertSame('news', $newOptions->tag());
    }

    public function testWithTagNullRemovesOption(): void
    {
        $options = FeedOptions::fromString('tag=news');
        $newOptions = $options->withTag(null);

        $this->assertNull($newOptions->tag());
    }

    public function testWithTagEmptyStringRemovesOption(): void
    {
        $options = FeedOptions::fromString('tag=news');
        $newOptions = $options->withTag('');

        $this->assertNull($newOptions->tag());
    }

    // =========================================================================
    // Builder Method Tests - withArticleSource()
    // =========================================================================

    public function testWithArticleSourceSetsValue(): void
    {
        $options = FeedOptions::empty();
        $newOptions = $options->withArticleSource('description');

        $this->assertSame('description', $newOptions->articleSource());
    }

    public function testWithArticleSourceNullRemovesOption(): void
    {
        $options = FeedOptions::fromString('article_source=description');
        $newOptions = $options->withArticleSource(null);

        $this->assertNull($newOptions->articleSource());
    }

    public function testWithArticleSourceEmptyStringRemovesOption(): void
    {
        $options = FeedOptions::fromString('article_source=description');
        $newOptions = $options->withArticleSource('');

        $this->assertNull($newOptions->articleSource());
    }

    // =========================================================================
    // Builder Method Chaining Tests
    // =========================================================================

    public function testBuilderMethodChaining(): void
    {
        $options = FeedOptions::empty()
            ->withEditText(true)
            ->withAutoUpdate('2h')
            ->withMaxLinks(100)
            ->withMaxTexts(50)
            ->withCharset('UTF-8')
            ->withTag('news')
            ->withArticleSource('description');

        $this->assertTrue($options->editText());
        $this->assertSame('2h', $options->autoUpdate());
        $this->assertSame(100, $options->maxLinks());
        $this->assertSame(50, $options->maxTexts());
        $this->assertSame('UTF-8', $options->charset());
        $this->assertSame('news', $options->tag());
        $this->assertSame('description', $options->articleSource());
    }

    public function testBuilderPreservesOtherOptions(): void
    {
        $options = FeedOptions::fromString('edit_text=1,autoupdate=2h');
        $newOptions = $options->withMaxLinks(100);

        $this->assertTrue($newOptions->editText());
        $this->assertSame('2h', $newOptions->autoUpdate());
        $this->assertSame(100, $newOptions->maxLinks());
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testUnicodeValues(): void
    {
        $options = FeedOptions::fromString('tag=日本語');

        $this->assertSame('日本語', $options->tag());
    }

    public function testSpecialCharactersInValues(): void
    {
        $options = FeedOptions::fromString('charset=ISO-8859-1');

        $this->assertSame('ISO-8859-1', $options->charset());
    }

    public function testValuesWithSpaces(): void
    {
        $options = FeedOptions::fromString('tag=my tag');

        $this->assertSame('my tag', $options->tag());
    }

    public function testMultipleOptionsRoundtrip(): void
    {
        $original = FeedOptions::empty()
            ->withEditText(true)
            ->withAutoUpdate('1d')
            ->withMaxLinks(50)
            ->withTag('test');

        $serialized = $original->toString();
        $restored = FeedOptions::fromString($serialized);

        $this->assertTrue($restored->editText());
        $this->assertSame('1d', $restored->autoUpdate());
        $this->assertSame(50, $restored->maxLinks());
        $this->assertSame('test', $restored->tag());
    }
}
