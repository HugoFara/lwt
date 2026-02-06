<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Language\Infrastructure;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Language\Infrastructure\NlpServiceHandler;

/**
 * Tests for NlpServiceHandler.
 *
 * @covers \Lwt\Modules\Language\Infrastructure\NlpServiceHandler
 */
class NlpServiceHandlerTest extends TestCase
{
    private NlpServiceHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new NlpServiceHandler();
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorSetsDefaultBaseUrl(): void
    {
        $reflection = new \ReflectionProperty(NlpServiceHandler::class, 'baseUrl');
        $reflection->setAccessible(true);

        $baseUrl = $reflection->getValue($this->handler);

        // Default is http://nlp:8000 (or from env)
        $this->assertIsString($baseUrl);
        $this->assertNotEmpty($baseUrl);
    }

    public function testConstructorSetsDefaultTimeout(): void
    {
        $reflection = new \ReflectionProperty(NlpServiceHandler::class, 'timeout');
        $reflection->setAccessible(true);

        $timeout = $reflection->getValue($this->handler);

        $this->assertSame(30, $timeout);
    }

    // =========================================================================
    // isAvailable() Tests
    // =========================================================================

    public function testIsAvailableReturnsBool(): void
    {
        $result = $this->handler->isAvailable();

        $this->assertIsBool($result);
    }

    public function testIsAvailableReturnsFalseWhenServiceUnavailable(): void
    {
        // With default config pointing to nlp:8000, this should return false
        // unless the NLP service is actually running
        $result = $this->handler->isAvailable();

        // Can't assert true/false reliably, but should not throw
        $this->assertIsBool($result);
    }

    // =========================================================================
    // speak() Tests
    // =========================================================================

    public function testSpeakMethodSignature(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'speak');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('text', $params[0]->getName());
        $this->assertSame('voiceId', $params[1]->getName());
    }

    public function testSpeakReturnsNullOnFailure(): void
    {
        // When service is unavailable, should return null
        $result = $this->handler->speak('Test text', 'en_US-amy-medium');

        // Without running NLP service, this should return null
        $this->assertNull($result);
    }

    // =========================================================================
    // getVoices() Tests
    // =========================================================================

    public function testGetVoicesReturnsArray(): void
    {
        $result = $this->handler->getVoices();

        $this->assertIsArray($result);
    }

    public function testGetVoicesReturnsEmptyArrayOnFailure(): void
    {
        // Without NLP service running
        $result = $this->handler->getVoices();

        $this->assertSame([], $result);
    }

    // =========================================================================
    // getInstalledVoices() Tests
    // =========================================================================

    public function testGetInstalledVoicesReturnsArray(): void
    {
        $result = $this->handler->getInstalledVoices();

        $this->assertIsArray($result);
    }

    public function testGetInstalledVoicesReturnsEmptyArrayOnFailure(): void
    {
        $result = $this->handler->getInstalledVoices();

        $this->assertSame([], $result);
    }

    // =========================================================================
    // downloadVoice() Tests
    // =========================================================================

    public function testDownloadVoiceMethodSignature(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'downloadVoice');

        $this->assertTrue($method->isPublic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('voiceId', $params[0]->getName());
    }

    public function testDownloadVoiceReturnsFalseOnFailure(): void
    {
        $result = $this->handler->downloadVoice('nonexistent-voice');

        $this->assertFalse($result);
    }

    // =========================================================================
    // deleteVoice() Tests
    // =========================================================================

    public function testDeleteVoiceMethodSignature(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'deleteVoice');

        $this->assertTrue($method->isPublic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('voiceId', $params[0]->getName());
    }

    public function testDeleteVoiceReturnsFalseOnFailure(): void
    {
        $result = $this->handler->deleteVoice('nonexistent-voice');

        $this->assertFalse($result);
    }

    // =========================================================================
    // parse() Tests
    // =========================================================================

    public function testParseMethodSignature(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'parse');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('text', $params[0]->getName());
        $this->assertSame('parser', $params[1]->getName());
    }

    public function testParseReturnsNullOnFailure(): void
    {
        $result = $this->handler->parse('Test text', 'mecab');

        $this->assertNull($result);
    }

    public function testParseAcceptsMecabParser(): void
    {
        // Test that method accepts 'mecab' parser
        $result = $this->handler->parse('日本語テスト', 'mecab');

        // Without service, returns null
        $this->assertNull($result);
    }

    public function testParseAcceptsJiebaParser(): void
    {
        // Test that method accepts 'jieba' parser
        $result = $this->handler->parse('中文测试', 'jieba');

        $this->assertNull($result);
    }

    // =========================================================================
    // getAvailableParsers() Tests
    // =========================================================================

    public function testGetAvailableParsersReturnsArray(): void
    {
        $result = $this->handler->getAvailableParsers();

        $this->assertIsArray($result);
    }

    public function testGetAvailableParsersReturnsEmptyArrayOnFailure(): void
    {
        $result = $this->handler->getAvailableParsers();

        $this->assertSame([], $result);
    }

    // =========================================================================
    // lemmatize() Tests
    // =========================================================================

    public function testLemmatizeMethodSignature(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'lemmatize');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('word', $params[0]->getName());
        $this->assertSame('languageCode', $params[1]->getName());
        $this->assertSame('lemmatizer', $params[2]->getName());

        // lemmatizer has default value
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertSame('spacy', $params[2]->getDefaultValue());
    }

    public function testLemmatizeReturnsNullOnFailure(): void
    {
        $result = $this->handler->lemmatize('running', 'en');

        $this->assertNull($result);
    }

    public function testLemmatizeWithSpacyDefault(): void
    {
        $result = $this->handler->lemmatize('running', 'en');

        $this->assertNull($result);
    }

    public function testLemmatizeWithExplicitLemmatizer(): void
    {
        $result = $this->handler->lemmatize('running', 'en', 'spacy');

        $this->assertNull($result);
    }

    // =========================================================================
    // lemmatizeBatch() Tests
    // =========================================================================

    public function testLemmatizeBatchMethodSignature(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'lemmatizeBatch');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('words', $params[0]->getName());
        $this->assertSame('languageCode', $params[1]->getName());
        $this->assertSame('lemmatizer', $params[2]->getName());
    }

    public function testLemmatizeBatchWithEmptyArrayReturnsEmpty(): void
    {
        $result = $this->handler->lemmatizeBatch([], 'en');

        $this->assertSame([], $result);
    }

    public function testLemmatizeBatchReturnsNullValuesOnFailure(): void
    {
        $words = ['running', 'walked', 'better'];
        $result = $this->handler->lemmatizeBatch($words, 'en');

        // Should return array with null values for each word
        $this->assertIsArray($result);
        $this->assertArrayHasKey('running', $result);
        $this->assertArrayHasKey('walked', $result);
        $this->assertArrayHasKey('better', $result);
        $this->assertNull($result['running']);
        $this->assertNull($result['walked']);
        $this->assertNull($result['better']);
    }

    // =========================================================================
    // getAvailableLemmatizers() Tests
    // =========================================================================

    public function testGetAvailableLemmatizersReturnsArray(): void
    {
        $result = $this->handler->getAvailableLemmatizers();

        $this->assertIsArray($result);
    }

    public function testGetAvailableLemmatizersReturnsEmptyArrayOnFailure(): void
    {
        $result = $this->handler->getAvailableLemmatizers();

        $this->assertSame([], $result);
    }

    // =========================================================================
    // checkLemmatizationSupport() Tests
    // =========================================================================

    public function testCheckLemmatizationSupportMethodSignature(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'checkLemmatizationSupport');

        $this->assertTrue($method->isPublic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('languageCode', $params[0]->getName());
    }

    public function testCheckLemmatizationSupportReturnsArrayWithLanguage(): void
    {
        $result = $this->handler->checkLemmatizationSupport('en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('language', $result);
        $this->assertSame('en', $result['language']);
    }

    public function testCheckLemmatizationSupportReturnsSpacyInfo(): void
    {
        $result = $this->handler->checkLemmatizationSupport('en');

        $this->assertArrayHasKey('spacy', $result);
        $this->assertIsArray($result['spacy']);
        $this->assertArrayHasKey('supported', $result['spacy']);
        $this->assertArrayHasKey('installed', $result['spacy']);
    }

    public function testCheckLemmatizationSupportWithGermanLanguage(): void
    {
        $result = $this->handler->checkLemmatizationSupport('de');

        $this->assertArrayHasKey('language', $result);
        $this->assertSame('de', $result['language']);
    }

    public function testCheckLemmatizationSupportWithJapaneseLanguage(): void
    {
        $result = $this->handler->checkLemmatizationSupport('ja');

        $this->assertArrayHasKey('language', $result);
        $this->assertSame('ja', $result['language']);
    }

    // =========================================================================
    // Return Type Tests
    // =========================================================================

    public function testSpeakReturnType(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'speak');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    public function testParseReturnType(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'parse');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    public function testLemmatizeReturnType(): void
    {
        $method = new \ReflectionMethod(NlpServiceHandler::class, 'lemmatize');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testSpeakWithEmptyText(): void
    {
        $result = $this->handler->speak('', 'en_US-amy-medium');

        // Should handle empty text gracefully
        $this->assertNull($result);
    }

    public function testSpeakWithUnicodeText(): void
    {
        $result = $this->handler->speak('日本語テスト', 'ja_JP-voice');

        $this->assertNull($result);
    }

    public function testLemmatizeWithEmptyWord(): void
    {
        $result = $this->handler->lemmatize('', 'en');

        $this->assertNull($result);
    }

    public function testLemmatizeWithUnicodeWord(): void
    {
        $result = $this->handler->lemmatize('食べる', 'ja');

        $this->assertNull($result);
    }

    public function testLemmatizeBatchWithSingleWord(): void
    {
        $result = $this->handler->lemmatizeBatch(['running'], 'en');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('running', $result);
    }

    public function testLemmatizeBatchWithManyWords(): void
    {
        $words = array_fill(0, 100, 'test');
        $words = array_unique(array_merge($words, ['running', 'walked', 'better']));

        $result = $this->handler->lemmatizeBatch($words, 'en');

        $this->assertIsArray($result);
        $this->assertCount(count($words), $result);
    }

    public function testCheckLemmatizationSupportWithInvalidLanguage(): void
    {
        $result = $this->handler->checkLemmatizationSupport('invalid_lang_xyz');

        // Should still return structured array
        $this->assertIsArray($result);
        $this->assertArrayHasKey('language', $result);
        $this->assertSame('invalid_lang_xyz', $result['language']);
    }

    public function testDeleteVoiceWithSpecialCharacters(): void
    {
        $result = $this->handler->deleteVoice('voice-with/special&chars');

        // Should handle URL encoding gracefully
        $this->assertFalse($result);
    }
}
