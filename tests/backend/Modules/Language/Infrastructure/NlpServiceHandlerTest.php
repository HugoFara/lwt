<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Language\Infrastructure;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Lwt\Modules\Language\Infrastructure\NlpServiceHandler;
use Lwt\Tests\Modules\Language\Infrastructure\FakeNlpHttpClient;

/**
 * Tests for NlpServiceHandler.
 *
 */
#[CoversClass(NlpServiceHandler::class)]
class NlpServiceHandlerTest extends TestCase
{
    private NlpServiceHandler $handler;
    private FakeNlpHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new FakeNlpHttpClient();
        $this->handler = new NlpServiceHandler($this->httpClient);
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

    public function testConstructorAcceptsNoArgumentsForBackwardCompatibility(): void
    {
        $handler = new NlpServiceHandler();

        $this->assertInstanceOf(NlpServiceHandler::class, $handler);
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
        $this->httpClient->response = null;

        $this->assertFalse($this->handler->isAvailable());
    }

    public function testIsAvailableReturnsTrueWhenHealthEndpointResponds(): void
    {
        $this->httpClient->response = '{"status":"ok"}';

        $this->assertTrue($this->handler->isAvailable());
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
        $this->httpClient->response = null;

        $this->assertNull($this->handler->speak('Test text', 'en_US-amy-medium'));
    }

    public function testSpeakEncodesAudioAsBase64DataUrl(): void
    {
        $this->httpClient->response = "WAV_BYTES";

        $result = $this->handler->speak('hello', 'en_US-amy-medium');

        $this->assertSame('data:audio/wav;base64,' . base64_encode('WAV_BYTES'), $result);
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
        $this->httpClient->response = null;

        $this->assertSame([], $this->handler->getVoices());
    }

    public function testGetVoicesReturnsVoicesFromPayload(): void
    {
        $this->httpClient->response = json_encode(['voices' => [['id' => 'v1'], ['id' => 'v2']]]);

        $result = $this->handler->getVoices();

        $this->assertCount(2, $result);
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
        $this->httpClient->response = null;

        $this->assertSame([], $this->handler->getInstalledVoices());
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
        $this->httpClient->response = null;

        $this->assertFalse($this->handler->downloadVoice('nonexistent-voice'));
    }

    public function testDownloadVoiceReturnsTrueOnSuccess(): void
    {
        $this->httpClient->response = '{"success":true}';

        $this->assertTrue($this->handler->downloadVoice('en_US-amy-medium'));
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
        $this->httpClient->response = null;

        $this->assertFalse($this->handler->deleteVoice('nonexistent-voice'));
    }

    public function testDeleteVoiceUrlEncodesSpecialCharacters(): void
    {
        $this->httpClient->response = '{"success":true}';

        $this->handler->deleteVoice('voice-with/special&chars');

        $this->assertCount(1, $this->httpClient->calls);
        $this->assertStringContainsString(
            urlencode('voice-with/special&chars'),
            $this->httpClient->calls[0]['url']
        );
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
        $this->httpClient->response = null;

        $this->assertNull($this->handler->parse('Test text', 'mecab'));
    }

    public function testParseAcceptsMecabParser(): void
    {
        $this->httpClient->response = null;

        $this->assertNull($this->handler->parse('日本語テスト', 'mecab'));
        $this->assertStringContainsString('"parser":"mecab"', $this->httpClient->calls[0]['body'] ?? '');
    }

    public function testParseAcceptsJiebaParser(): void
    {
        $this->httpClient->response = null;

        $this->assertNull($this->handler->parse('中文测试', 'jieba'));
        $this->assertStringContainsString('"parser":"jieba"', $this->httpClient->calls[0]['body'] ?? '');
    }

    public function testParseReturnsDecodedResult(): void
    {
        $this->httpClient->response = json_encode(['sentences' => [], 'tokens' => ['a', 'b']]);

        $result = $this->handler->parse('hello', 'mecab');

        $this->assertIsArray($result);
        $this->assertSame(['a', 'b'], $result['tokens']);
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
        $this->httpClient->response = null;

        $this->assertSame([], $this->handler->getAvailableParsers());
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
        $this->httpClient->response = null;

        $this->assertNull($this->handler->lemmatize('running', 'en'));
    }

    public function testLemmatizeReturnsLemmaFromPayload(): void
    {
        $this->httpClient->response = json_encode(['lemma' => 'run']);

        $this->assertSame('run', $this->handler->lemmatize('running', 'en'));
    }

    public function testLemmatizeWithSpacyDefault(): void
    {
        $this->httpClient->response = null;
        $this->handler->lemmatize('running', 'en');

        $this->assertStringContainsString('"lemmatizer":"spacy"', $this->httpClient->calls[0]['body'] ?? '');
    }

    public function testLemmatizeWithExplicitLemmatizer(): void
    {
        $this->httpClient->response = null;
        $this->handler->lemmatize('running', 'en', 'spacy');

        $this->assertStringContainsString('"lemmatizer":"spacy"', $this->httpClient->calls[0]['body'] ?? '');
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
        $this->assertSame([], $this->httpClient->calls, 'No request should be made for an empty batch');
    }

    public function testLemmatizeBatchReturnsNullValuesOnFailure(): void
    {
        $this->httpClient->response = null;
        $words = ['running', 'walked', 'better'];

        $result = $this->handler->lemmatizeBatch($words, 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('running', $result);
        $this->assertArrayHasKey('walked', $result);
        $this->assertArrayHasKey('better', $result);
        $this->assertNull($result['running']);
        $this->assertNull($result['walked']);
        $this->assertNull($result['better']);
    }

    public function testLemmatizeBatchReturnsResultsFromPayload(): void
    {
        $this->httpClient->response = json_encode([
            'results' => ['running' => 'run', 'walked' => 'walk'],
        ]);

        $result = $this->handler->lemmatizeBatch(['running', 'walked'], 'en');

        $this->assertSame('run', $result['running']);
        $this->assertSame('walk', $result['walked']);
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
        $this->httpClient->response = null;

        $this->assertSame([], $this->handler->getAvailableLemmatizers());
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
        $this->httpClient->response = null;

        $result = $this->handler->checkLemmatizationSupport('en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('language', $result);
        $this->assertSame('en', $result['language']);
    }

    public function testCheckLemmatizationSupportReturnsSpacyInfo(): void
    {
        $this->httpClient->response = null;

        $result = $this->handler->checkLemmatizationSupport('en');

        $this->assertArrayHasKey('spacy', $result);
        $this->assertIsArray($result['spacy']);
        $this->assertArrayHasKey('supported', $result['spacy']);
        $this->assertArrayHasKey('installed', $result['spacy']);
    }

    public function testCheckLemmatizationSupportWithGermanLanguage(): void
    {
        $this->httpClient->response = null;

        $result = $this->handler->checkLemmatizationSupport('de');

        $this->assertArrayHasKey('language', $result);
        $this->assertSame('de', $result['language']);
    }

    public function testCheckLemmatizationSupportWithJapaneseLanguage(): void
    {
        $this->httpClient->response = null;

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
        $this->httpClient->response = null;

        $this->assertNull($this->handler->speak('', 'en_US-amy-medium'));
    }

    public function testSpeakWithUnicodeText(): void
    {
        $this->httpClient->response = null;

        $this->assertNull($this->handler->speak('日本語テスト', 'ja_JP-voice'));
    }

    public function testLemmatizeWithEmptyWord(): void
    {
        $this->httpClient->response = null;

        $this->assertNull($this->handler->lemmatize('', 'en'));
    }

    public function testLemmatizeWithUnicodeWord(): void
    {
        $this->httpClient->response = null;

        $this->assertNull($this->handler->lemmatize('食べる', 'ja'));
    }

    public function testLemmatizeBatchWithSingleWord(): void
    {
        $this->httpClient->response = null;

        $result = $this->handler->lemmatizeBatch(['running'], 'en');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('running', $result);
    }

    public function testLemmatizeBatchWithManyWords(): void
    {
        $this->httpClient->response = null;
        $words = array_fill(0, 100, 'test');
        $words = array_unique(array_merge($words, ['running', 'walked', 'better']));

        $result = $this->handler->lemmatizeBatch($words, 'en');

        $this->assertIsArray($result);
        $this->assertCount(count($words), $result);
    }

    public function testCheckLemmatizationSupportWithInvalidLanguage(): void
    {
        $this->httpClient->response = null;

        $result = $this->handler->checkLemmatizationSupport('invalid_lang_xyz');

        // Should still return structured array
        $this->assertIsArray($result);
        $this->assertArrayHasKey('language', $result);
        $this->assertSame('invalid_lang_xyz', $result['language']);
    }

    public function testDeleteVoiceWithSpecialCharacters(): void
    {
        $this->httpClient->response = null;

        $this->assertFalse($this->handler->deleteVoice('voice-with/special&chars'));
    }
}
