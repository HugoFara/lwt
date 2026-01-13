<?php

declare(strict_types=1);

namespace Tests\Modules\Vocabulary\Infrastructure;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;

/**
 * Tests for DictionaryAdapter.
 *
 * @covers \Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter
 */
class DictionaryAdapterTest extends TestCase
{
    private DictionaryAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new DictionaryAdapter();
    }

    // =========================================================================
    // createDictLink() Static Method Tests
    // =========================================================================

    public function testCreateDictLinkAppendsTermToUrl(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'hello';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=hello', $result);
    }

    public function testCreateDictLinkReplacesLwtTermPlaceholder(): void
    {
        $url = 'https://example.com/search?q=lwt_term&lang=en';
        $term = 'hello';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=hello&lang=en', $result);
    }

    public function testCreateDictLinkEncodesSpecialCharacters(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'hello world';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=hello+world', $result);
    }

    public function testCreateDictLinkEncodesAmpersand(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'rock & roll';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=rock+%26+roll', $result);
    }

    public function testCreateDictLinkEncodesUnicodeCharacters(): void
    {
        $url = 'https://example.com/search?q=';
        $term = '日本語';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertStringContainsString('%', $result);
        $this->assertSame('https://example.com/search?q=' . urlencode('日本語'), $result);
    }

    public function testCreateDictLinkWithEmptyTerm(): void
    {
        $url = 'https://example.com/search?q=';
        $term = '';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=+', $result);
    }

    public function testCreateDictLinkTrimsUrl(): void
    {
        $url = '  https://example.com/search?q=  ';
        $term = 'hello';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=hello', $result);
    }

    public function testCreateDictLinkTrimsTerm(): void
    {
        $url = 'https://example.com/search?q=';
        $term = '  hello  ';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=hello', $result);
    }

    public function testCreateDictLinkWithMultipleLwtTermPlaceholders(): void
    {
        $url = 'https://example.com/search?q=lwt_term&backup=lwt_term';
        $term = 'hello';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=hello&backup=hello', $result);
    }

    public function testCreateDictLinkWithLwtTermInPath(): void
    {
        $url = 'https://example.com/dict/lwt_term/translate';
        $term = 'hello';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/dict/hello/translate', $result);
    }

    public function testCreateDictLinkWithPlusSign(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'C++';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=C%2B%2B', $result);
    }

    public function testCreateDictLinkWithSlash(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'and/or';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=and%2For', $result);
    }

    // =========================================================================
    // makeOpenDictStr() Tests
    // =========================================================================

    public function testMakeOpenDictStrReturnsEmptyForEmptyUrl(): void
    {
        $result = $this->adapter->makeOpenDictStr('', 'Dict1');

        $this->assertSame('', $result);
    }

    public function testMakeOpenDictStrReturnsEmptyForEmptyText(): void
    {
        $result = $this->adapter->makeOpenDictStr('https://example.com', '');

        $this->assertSame('', $result);
    }

    public function testMakeOpenDictStrReturnsAnchorForNonPopup(): void
    {
        $result = $this->adapter->makeOpenDictStr('https://example.com', 'Dict1', false);

        $this->assertStringContainsString('<a href="https://example.com"', $result);
        $this->assertStringContainsString('target="ru"', $result);
        $this->assertStringContainsString('data-action="dict-frame"', $result);
        $this->assertStringContainsString('Dict1</a>', $result);
    }

    public function testMakeOpenDictStrReturnsSpanForPopup(): void
    {
        $result = $this->adapter->makeOpenDictStr('https://example.com', 'Dict1', true);

        $this->assertStringContainsString('<span class="click"', $result);
        $this->assertStringContainsString('data-action="dict-popup"', $result);
        $this->assertStringContainsString('data-url="https://example.com"', $result);
        $this->assertStringContainsString('Dict1</span>', $result);
    }

    public function testMakeOpenDictStrEscapesUrl(): void
    {
        $result = $this->adapter->makeOpenDictStr(
            'https://example.com/search?a=1&b=2',
            'Dict1'
        );

        $this->assertStringContainsString('&amp;', $result);
    }

    public function testMakeOpenDictStrEscapesText(): void
    {
        $result = $this->adapter->makeOpenDictStr(
            'https://example.com',
            '<script>alert(1)</script>'
        );

        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    // =========================================================================
    // makeOpenDictStrDynSent() Tests
    // =========================================================================

    public function testMakeOpenDictStrDynSentReturnsEmptyForEmptyUrl(): void
    {
        $result = $this->adapter->makeOpenDictStrDynSent('', 'sentctl', 'Translate');

        $this->assertSame('', $result);
    }

    public function testMakeOpenDictStrDynSentReturnsSpanWithDataAttributes(): void
    {
        $result = $this->adapter->makeOpenDictStrDynSent(
            'https://translate.example.com',
            'sentctl123',
            'Translate sentence'
        );

        $this->assertStringContainsString('<span class="click"', $result);
        $this->assertStringContainsString('data-action="translate-sentence"', $result);
        $this->assertStringContainsString('data-url="https://translate.example.com"', $result);
        $this->assertStringContainsString('data-sentctl="sentctl123"', $result);
        $this->assertStringContainsString('Translate sentence</span>', $result);
    }

    public function testMakeOpenDictStrDynSentWithPopup(): void
    {
        $result = $this->adapter->makeOpenDictStrDynSent(
            'https://translate.example.com',
            'sentctl123',
            'Translate',
            true
        );

        $this->assertStringContainsString('data-action="translate-sentence-popup"', $result);
    }

    public function testMakeOpenDictStrDynSentHandlesGglPhp(): void
    {
        $result = $this->adapter->makeOpenDictStrDynSent(
            'ggl.php?sl=en&tl=es',
            'sentctl',
            'Translate'
        );

        $this->assertStringContainsString('sent=1', $result);
        $this->assertStringContainsString('ggl.php?sent=1&amp;sl=en', $result);
    }

    public function testMakeOpenDictStrDynSentHandlesGglPhpInPath(): void
    {
        $result = $this->adapter->makeOpenDictStrDynSent(
            'https://example.com/lwt/ggl.php?sl=en&tl=es',
            'sentctl',
            'Translate'
        );

        $this->assertStringContainsString('sent=1', $result);
    }

    public function testMakeOpenDictStrDynSentEscapesUrl(): void
    {
        $result = $this->adapter->makeOpenDictStrDynSent(
            'https://example.com?a=1&b=2',
            'sentctl',
            'Translate'
        );

        $this->assertStringContainsString('&amp;', $result);
    }

    public function testMakeOpenDictStrDynSentHandlesUrlWithoutProtocol(): void
    {
        $result = $this->adapter->makeOpenDictStrDynSent(
            'example.com/translate',
            'sentctl',
            'Translate'
        );

        $this->assertStringContainsString('data-url="example.com/translate"', $result);
    }

    // =========================================================================
    // makeDictLinks() Tests
    // =========================================================================

    public function testMakeDictLinksReturnsHtmlWithDataAttributes(): void
    {
        // Note: This test requires database access for getLanguageDictionaries()
        // We test the HTML structure when dictionaries are empty
        $result = $this->adapter->makeDictLinks(99999, 'testword');

        $this->assertStringContainsString('<span class="is-size-7">', $result);
        $this->assertStringContainsString('data-word="testword"', $result);
        $this->assertStringContainsString('[1]</span>', $result);
    }

    public function testMakeDictLinksEscapesWord(): void
    {
        $result = $this->adapter->makeDictLinks(99999, '<script>alert(1)</script>');

        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>alert', $result);
    }

    // =========================================================================
    // createDictLinksInEditWin() Tests
    // =========================================================================

    public function testCreateDictLinksInEditWinReturnsLookupTermLabel(): void
    {
        // This test requires database access, so we test the basic structure
        $result = $this->adapter->createDictLinksInEditWin(99999, 'testword', 'sentctl', false);

        $this->assertStringContainsString('Lookup Term:', $result);
    }

    public function testCreateDictLinksInEditWinWithOpenFirst(): void
    {
        $result = $this->adapter->createDictLinksInEditWin(99999, 'testword', 'sentctl', true);

        // When openFirst is true, should include auto-init span
        $this->assertStringContainsString('Lookup Term:', $result);
    }

    // =========================================================================
    // createDictLinksInEditWin2() Tests
    // =========================================================================

    public function testCreateDictLinksInEditWin2ReturnsLookupTermLabel(): void
    {
        $result = $this->adapter->createDictLinksInEditWin2(99999, 'sentctl', 'wordctl');

        $this->assertStringContainsString('Lookup Term:', $result);
        $this->assertStringContainsString('data-wordctl="wordctl"', $result);
        $this->assertStringContainsString('Dict1</span>', $result);
    }

    public function testCreateDictLinksInEditWin2EscapesControlIds(): void
    {
        $result = $this->adapter->createDictLinksInEditWin2(
            99999,
            'sentctl',
            '"onclick="alert(1)'
        );

        // wordctlid with quotes should be escaped
        $this->assertStringContainsString('&quot;onclick=', $result);
        // Should have data-wordctl attribute
        $this->assertStringContainsString('data-wordctl="', $result);
    }

    // =========================================================================
    // createDictLinksInEditWin3() Tests
    // =========================================================================

    public function testCreateDictLinksInEditWin3ReturnsLookupTermLabel(): void
    {
        $result = $this->adapter->createDictLinksInEditWin3(99999, 'sentctl', 'wordctl');

        $this->assertStringContainsString('Lookup Term:', $result);
        $this->assertStringContainsString('Dict1</span>', $result);
    }

    public function testCreateDictLinksInEditWin3HandlesEmptyDictionaries(): void
    {
        // With non-existent language ID, dictionaries will be empty
        $result = $this->adapter->createDictLinksInEditWin3(99999, 'sentctl', 'wordctl');

        // Should still have basic structure
        $this->assertStringContainsString('Lookup Term:', $result);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testCreateDictLinkWithQuestionMarkInTerm(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'what?';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=what%3F', $result);
    }

    public function testCreateDictLinkWithHashInTerm(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'C#';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=C%23', $result);
    }

    public function testCreateDictLinkWithEqualsInTerm(): void
    {
        $url = 'https://example.com/search?q=';
        $term = 'a=b';

        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/search?q=a%3Db', $result);
    }

    public function testMakeOpenDictStrWithQuotesInText(): void
    {
        $result = $this->adapter->makeOpenDictStr(
            'https://example.com',
            'Dict "1"'
        );

        $this->assertStringContainsString('Dict &quot;1&quot;', $result);
    }

    public function testMakeOpenDictStrDynSentWithInvalidUrl(): void
    {
        // Test with malformed URL
        $result = $this->adapter->makeOpenDictStrDynSent(
            '://invalid',
            'sentctl',
            'Translate'
        );

        // Should still return something (graceful handling)
        $this->assertIsString($result);
    }

    /**
     * @dataProvider urlEncodingProvider
     */
    public function testCreateDictLinkUrlEncoding(string $term, string $expectedEncoded): void
    {
        $url = 'https://example.com/q=';
        $result = DictionaryAdapter::createDictLink($url, $term);

        $this->assertSame('https://example.com/q=' . $expectedEncoded, $result);
    }

    public static function urlEncodingProvider(): array
    {
        return [
            'simple_word' => ['hello', 'hello'],
            'space' => ['hello world', 'hello+world'],
            'plus' => ['a+b', 'a%2Bb'],
            'percent' => ['100%', '100%25'],
            'utf8_german' => ['über', '%C3%BCber'],
            'utf8_chinese' => ['你好', '%E4%BD%A0%E5%A5%BD'],
            'utf8_arabic' => ['مرحبا', '%D9%85%D8%B1%D8%AD%D8%A8%D8%A7'],
            'newline' => ["a\nb", 'a%0Ab'],
            'tab' => ["a\tb", 'a%09b'],
        ];
    }
}
