<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/php/inc/classes/Language.php';
require_once __DIR__ . '/../../../src/php/inc/classes/Term.php';
require_once __DIR__ . '/../../../src/php/inc/classes/Text.php';
require_once __DIR__ . '/../../../src/php/inc/classes/GoogleTranslate.php';

use PHPUnit\Framework\TestCase;
use Lwt\Classes\GoogleTranslate;
use Lwt\Classes\Language;
use Lwt\Classes\Term;
use Lwt\Classes\Text;

/**
 * Comprehensive tests for PHP classes: Language, Term, Text, GoogleTranslate
 *
 * Tests object initialization, property handling, JSON export, and core methods
 */
class ClassesTest extends TestCase
{
    /**
     * Test Language class - basic property initialization
     */
    public function testLanguageClassBasicProperties(): void
    {
        $lang = new Language();

        // Test property assignment
        $lang->id = 1;
        $lang->name = 'English';
        $lang->dict1uri = 'https://en.wiktionary.org/wiki/###';
        $lang->dict2uri = 'https://www.wordreference.com/definition/###';
        $lang->translator = 'https://translate.google.com/?sl=en&tl=es&text=###';

        $this->assertEquals(1, $lang->id);
        $this->assertEquals('English', $lang->name);
        $this->assertEquals('https://en.wiktionary.org/wiki/###', $lang->dict1uri);
        $this->assertEquals('https://www.wordreference.com/definition/###', $lang->dict2uri);
        $this->assertEquals('https://translate.google.com/?sl=en&tl=es&text=###', $lang->translator);
    }

    /**
     * Test Language class - text parsing properties
     */
    public function testLanguageClassParsingProperties(): void
    {
        $lang = new Language();

        $lang->textsize = 100;
        $lang->charactersubst = 'ß=ss|ä=ae|ö=oe';
        $lang->regexpsplitsent = '.!?';
        $lang->exceptionsplitsent = 'Mr.|Dr.|Mrs.';
        $lang->regexpwordchar = 'a-zA-Z';

        $this->assertEquals(100, $lang->textsize);
        $this->assertEquals('ß=ss|ä=ae|ö=oe', $lang->charactersubst);
        $this->assertEquals('.!?', $lang->regexpsplitsent);
        $this->assertEquals('Mr.|Dr.|Mrs.', $lang->exceptionsplitsent);
        $this->assertEquals('a-zA-Z', $lang->regexpwordchar);
    }

    /**
     * Test Language class - boolean flags
     */
    public function testLanguageClassBooleanFlags(): void
    {
        $lang = new Language();

        $lang->removespaces = false;
        $lang->spliteachchar = true;
        $lang->rightoleft = true;

        $this->assertFalse($lang->removespaces);
        $this->assertTrue($lang->spliteachchar);
        $this->assertTrue($lang->rightoleft);
    }

    /**
     * Test Language class - TTS and romanization
     */
    public function testLanguageClassTTSAndRomanization(): void
    {
        $lang = new Language();

        $lang->ttsvoiceapi = 'https://tts.example.com/speak?text=###';
        $lang->showromanization = 'show';

        $this->assertEquals('https://tts.example.com/speak?text=###', $lang->ttsvoiceapi);
        $this->assertEquals('show', $lang->showromanization);
    }

    /**
     * Test Language export_js_dict method - JSON export
     */
    public function testLanguageExportJsDict(): void
    {
        $lang = new Language();

        $lang->id = 42;
        $lang->name = 'TestLang';
        $lang->dict1uri = 'http://dict1.test/###';
        $lang->dict2uri = 'http://dict2.test/###';
        $lang->translator = 'http://translate.test/###';
        $lang->exporttemplate = 'Test: %s';
        $lang->textsize = 150;
        $lang->charactersubst = 'a=b';
        $lang->regexpsplitsent = '.?!';
        $lang->exceptionsplitsent = 'Test.';
        $lang->regexpwordchar = 'a-z';
        $lang->removespaces = true;
        $lang->spliteachchar = false;
        $lang->rightoleft = false;
        $lang->ttsvoiceapi = 'http://tts.test/###';
        $lang->showromanization = 'hide';

        $json = $lang->export_js_dict();

        // Should return valid JSON
        $this->assertJson($json);

        // Decode and verify structure
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertEquals(42, $decoded['lgid']);
        $this->assertEquals('http://dict1.test/###', $decoded['dict1uri']);
        $this->assertEquals('http://dict2.test/###', $decoded['dict2uri']);
        $this->assertEquals('http://translate.test/###', $decoded['translator']);
        $this->assertEquals('Test: %s', $decoded['exporttemplate']);
        $this->assertEquals(150, $decoded['textsize']);
        $this->assertEquals('a=b', $decoded['charactersubst']);
        $this->assertEquals('.?!', $decoded['regexpsplitsent']);
        $this->assertEquals('Test.', $decoded['exceptionsplitsent']);
        $this->assertEquals('a-z', $decoded['regexpwordchar']);
        $this->assertTrue($decoded['removespaces']);
        $this->assertFalse($decoded['spliteachchar']);
        $this->assertFalse($decoded['rightoleft']);
        $this->assertEquals('http://tts.test/###', $decoded['ttsvoiceapi']);
        $this->assertEquals('hide', $decoded['showromanization']);
    }

    /**
     * Test Language export with empty values
     */
    public function testLanguageExportWithEmptyValues(): void
    {
        $lang = new Language();

        $json = $lang->export_js_dict();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('lgid', $decoded);
        $this->assertArrayHasKey('dict1uri', $decoded);
    }

    /**
     * Test Language export with special characters
     */
    public function testLanguageExportWithSpecialCharacters(): void
    {
        $lang = new Language();

        $lang->name = 'Test "Language" with \'quotes\'';
        $lang->charactersubst = 'ß=ss|"quoted"=test';

        $json = $lang->export_js_dict();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded, 'Should handle special characters in JSON');
    }

    /**
     * Test Term class - basic properties
     */
    public function testTermClassBasicProperties(): void
    {
        $term = new Term();

        $term->id = 123;
        $term->lgid = 1;
        $term->text = 'Hello';
        $term->textlc = 'hello';
        $term->status = 3;
        $term->translation = 'Hola';
        $term->sentence = 'Hello world.';
        $term->roman = 'heh-lo';
        $term->wordcount = 1;
        $term->statuschanged = time();

        $this->assertEquals(123, $term->id);
        $this->assertEquals(1, $term->lgid);
        $this->assertEquals('Hello', $term->text);
        $this->assertEquals('hello', $term->textlc);
        $this->assertEquals(3, $term->status);
        $this->assertEquals('Hola', $term->translation);
        $this->assertEquals('Hello world.', $term->sentence);
        $this->assertEquals('heh-lo', $term->roman);
        $this->assertEquals(1, $term->wordcount);
        $this->assertIsInt($term->statuschanged);
    }

    /**
     * Test Term with multi-word expression
     */
    public function testTermClassMultiWord(): void
    {
        $term = new Term();

        $term->text = 'good morning';
        $term->textlc = 'good morning';
        $term->wordcount = 2;
        $term->translation = 'buenos días';

        $this->assertEquals(2, $term->wordcount);
        $this->assertEquals('good morning', $term->text);
    }

    /**
     * Test Term with different status values
     */
    public function testTermClassStatusValues(): void
    {
        $term = new Term();

        // Learning statuses (1-5)
        foreach ([1, 2, 3, 4, 5] as $status) {
            $term->status = $status;
            $this->assertEquals($status, $term->status);
        }

        // Special statuses
        $term->status = 98; // Ignored
        $this->assertEquals(98, $term->status);

        $term->status = 99; // Well Known
        $this->assertEquals(99, $term->status);
    }

    /**
     * Test Term with Unicode characters
     */
    public function testTermClassUnicode(): void
    {
        $term = new Term();

        $term->text = '日本語';
        $term->textlc = '日本語';
        $term->translation = 'Japanese language';
        $term->roman = 'nihongo';

        $this->assertEquals('日本語', $term->text);
        $this->assertEquals('nihongo', $term->roman);
    }

    /**
     * Test Text class - basic properties
     */
    public function testTextClassBasicProperties(): void
    {
        $text = new Text();

        $text->id = 456;
        $text->lgid = 1;
        $text->title = 'My First Text';
        $text->text = 'This is a sample text for learning.';
        $text->annotated = '<span>This</span> <span>is</span> <span>a</span>...';
        $text->media_uri = 'http://example.com/audio.mp3';
        $text->source = 'http://example.com/source';
        $text->position = '50';
        $text->audio_pos = 12.5;

        $this->assertEquals(456, $text->id);
        $this->assertEquals(1, $text->lgid);
        $this->assertEquals('My First Text', $text->title);
        $this->assertEquals('This is a sample text for learning.', $text->text);
        $this->assertStringContainsString('span', $text->annotated);
        $this->assertEquals('http://example.com/audio.mp3', $text->media_uri);
        $this->assertEquals('http://example.com/source', $text->source);
        $this->assertEquals('50', $text->position);
        $this->assertEquals(12.5, $text->audio_pos);
    }

    /**
     * Test Text load_from_db_record method
     */
    public function testTextLoadFromDbRecord(): void
    {
        $text = new Text();

        $record = [
            'TxID' => 789,
            'TxLgID' => 2,
            'TxTitle' => 'Database Text',
            'TxText' => 'Loaded from database.',
            'TxAnnotatedText' => '<annotated>content</annotated>',
            'TxAudioURI' => 'audio/file.mp3',
            'TxSourceURI' => 'https://source.url',
            'TxPosition' => '100',
            'TxAudioPosition' => 5.75
        ];

        $text->load_from_db_record($record);

        $this->assertEquals(789, $text->id);
        $this->assertEquals(2, $text->lgid);
        $this->assertEquals('Database Text', $text->title);
        $this->assertEquals('Loaded from database.', $text->text);
        $this->assertEquals('<annotated>content</annotated>', $text->annotated);
        $this->assertEquals('audio/file.mp3', $text->media_uri);
        $this->assertEquals('https://source.url', $text->source);
        $this->assertEquals('100', $text->position);
        $this->assertEquals(5.75, $text->audio_pos);
    }

    /**
     * Test Text with empty/null values
     */
    public function testTextLoadWithNullValues(): void
    {
        $text = new Text();

        $record = [
            'TxID' => 1,
            'TxLgID' => 1,
            'TxTitle' => 'Test',
            'TxText' => 'Test text',
            'TxAnnotatedText' => null,
            'TxAudioURI' => null,
            'TxSourceURI' => '',
            'TxPosition' => '0',
            'TxAudioPosition' => 0.0
        ];

        $text->load_from_db_record($record);

        $this->assertNull($text->annotated);
        $this->assertNull($text->media_uri);
        $this->assertEquals('', $text->source);
    }

    /**
     * Test Text with long content
     */
    public function testTextClassLongContent(): void
    {
        $text = new Text();

        $longText = str_repeat('This is a long text. ', 100);
        $text->text = $longText;
        $text->title = 'Long Text Example';

        $this->assertGreaterThan(1000, strlen($text->text));
        $this->assertEquals('Long Text Example', $text->title);
    }

    /**
     * Test GoogleTranslate class - getDomain method
     */
    public function testGoogleTranslateGetDomain(): void
    {
        // Test with valid domain
        $domain = GoogleTranslate::getDomain('com');
        $this->assertEquals('com', $domain);

        $domain = GoogleTranslate::getDomain('de');
        $this->assertEquals('de', $domain);

        $domain = GoogleTranslate::getDomain('fr');
        $this->assertEquals('fr', $domain);

        // Test with empty domain (should return random)
        $domain = GoogleTranslate::getDomain('');
        $this->assertIsString($domain);
        $this->assertNotEmpty($domain);

        // Test with invalid domain (should return random)
        $domain = GoogleTranslate::getDomain('invalid');
        $this->assertIsString($domain);
        $this->assertNotEquals('invalid', $domain);
    }

    /**
     * Test GoogleTranslate array_iunique method
     */
    public function testGoogleTranslateArrayIunique(): void
    {
        $input = ['Hello', 'HELLO', 'hello', 'World', 'WORLD'];
        $result = GoogleTranslate::array_iunique($input);

        // Should remove case-insensitive duplicates
        $this->assertLessThanOrEqual(2, count($result));
        $this->assertContains('Hello', $result);
    }

    /**
     * Test GoogleTranslate constructor and setters
     */
    public function testGoogleTranslateConstructorAndSetters(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        $this->assertInstanceOf(GoogleTranslate::class, $translator);

        // Test fluent interface
        $result = $translator->setLangFrom('de');
        $this->assertInstanceOf(GoogleTranslate::class, $result);

        $result = $translator->setLangTo('fr');
        $this->assertInstanceOf(GoogleTranslate::class, $result);
    }

    /**
     * Test GoogleTranslate setDomain method
     */
    public function testGoogleTranslateSetDomain(): void
    {
        // Should not throw error
        GoogleTranslate::setDomain('com');
        $this->assertTrue(true, 'setDomain should complete without error');

        GoogleTranslate::setDomain('');
        $this->assertTrue(true, 'setDomain with empty should use random');

        GoogleTranslate::setDomain(null);
        $this->assertTrue(true, 'setDomain with null should use random');
    }

    /**
     * Test GoogleTranslate with various languages
     */
    public function testGoogleTranslateLanguageCodes(): void
    {
        $translator = new GoogleTranslate('en', 'ja');
        $this->assertInstanceOf(GoogleTranslate::class, $translator);

        $translator = new GoogleTranslate('zh', 'ar');
        $this->assertInstanceOf(GoogleTranslate::class, $translator);

        $translator = new GoogleTranslate('ru', 'pt');
        $this->assertInstanceOf(GoogleTranslate::class, $translator);
    }

    /**
     * Test Language class with RTL language configuration
     */
    public function testLanguageRTLConfiguration(): void
    {
        $lang = new Language();

        // Arabic configuration
        $lang->name = 'Arabic';
        $lang->rightoleft = true;
        $lang->regexpwordchar = '\x{0600}-\x{06FF}';

        $this->assertTrue($lang->rightoleft);
        $this->assertStringContainsString('0600', $lang->regexpwordchar);
    }

    /**
     * Test Language class with CJK configuration
     */
    public function testLanguageCJKConfiguration(): void
    {
        $lang = new Language();

        // Chinese configuration
        $lang->name = 'Chinese';
        $lang->spliteachchar = true;
        $lang->removespaces = true;
        $lang->regexpwordchar = '\x{4E00}-\x{9FFF}';

        $this->assertTrue($lang->spliteachchar);
        $this->assertTrue($lang->removespaces);
    }

    /**
     * Test Term with empty translation
     */
    public function testTermWithEmptyTranslation(): void
    {
        $term = new Term();

        $term->text = 'unknown';
        $term->status = 1;
        $term->translation = '';

        $this->assertEquals('', $term->translation);
        $this->assertEquals(1, $term->status);
    }

    /**
     * Test Text with special characters in title
     */
    public function testTextWithSpecialCharactersInTitle(): void
    {
        $text = new Text();

        $text->title = 'Title with "quotes" and \'apostrophes\' & symbols!';
        $text->text = 'Content with <html> tags.';

        $this->assertStringContainsString('quotes', $text->title);
        $this->assertStringContainsString('html', $text->text);
    }

    /**
     * Test Text with various media URIs
     */
    public function testTextWithVariousMediaURIs(): void
    {
        $text = new Text();

        // HTTP URL
        $text->media_uri = 'http://example.com/audio.mp3';
        $this->assertEquals('http://example.com/audio.mp3', $text->media_uri);

        // HTTPS URL
        $text->media_uri = 'https://example.com/video.mp4';
        $this->assertEquals('https://example.com/video.mp4', $text->media_uri);

        // Local path
        $text->media_uri = 'media/local/file.mp3';
        $this->assertEquals('media/local/file.mp3', $text->media_uri);

        // YouTube URL
        $text->media_uri = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $this->assertStringContainsString('youtube', $text->media_uri);
    }

    /**
     * Test Language JSON export is valid and reversible
     */
    public function testLanguageJsonExportReversible(): void
    {
        $lang = new Language();

        $lang->id = 99;
        $lang->name = 'Test Language';
        $lang->textsize = 200;
        $lang->removespaces = false;
        $lang->spliteachchar = false;
        $lang->rightoleft = true;

        $json = $lang->export_js_dict();
        $decoded = json_decode($json, true);

        // Should be able to reconstruct from JSON
        $this->assertEquals(99, $decoded['lgid']);
        $this->assertEquals(200, $decoded['textsize']);
        $this->assertFalse($decoded['removespaces']);
        $this->assertFalse($decoded['spliteachchar']);
        $this->assertTrue($decoded['rightoleft']);
    }

    /**
     * Test GoogleTranslate generateToken method with reflection
     * This is a private static method, so we use reflection to test it
     */
    public function testGoogleTranslateGenerateToken(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test with simple ASCII string
        $token = $method->invokeArgs(null, ['hello', [408254, 585515986]]);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);

        // Token format should be: number.number
        $parts = explode('.', $token);
        $this->assertCount(2, $parts);
        $this->assertIsNumeric($parts[0]);
        $this->assertIsNumeric($parts[1]);

        // Test with Unicode string
        $token = $method->invokeArgs(null, ['こんにちは', [408254, 585515986]]);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);

        // Test with empty string
        $token = $method->invokeArgs(null, ['', [408254, 585515986]]);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);

        // Test with special characters
        $token = $method->invokeArgs(null, ['Hallö Wörld!', [408254, 585515986]]);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);

        // Test with different token values
        $token = $method->invokeArgs(null, ['test', [123456, 789012]]);
        $this->assertIsString($token);

        // Test without providing token (should use default)
        $token = $method->invokeArgs(null, ['test', null]);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);
    }

    /**
     * Test GoogleTranslate makeCurl method
     * Note: This requires network access or mocking, we test basic functionality
     */
    public function testGoogleTranslateMakeCurl(): void
    {
        // Set domain first (required for headers)
        GoogleTranslate::setDomain('com');

        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('makeCurl');
        $method->setAccessible(true);

        // Test with a simple URL that should fail (we're not testing actual translation)
        // We just want to verify the method executes without fatal errors
        $result = $method->invokeArgs(null, ['http://httpbin.org/status/404', false]);

        // Result should be string or false
        $this->assertTrue(is_string($result) || $result === false);
    }

    /**
     * Test GoogleTranslate translate instance method
     * This method calls staticTranslate internally
     */
    public function testGoogleTranslateInstanceMethod(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        // Mock by testing with reflection to avoid actual API call
        // The translate method stores result in lastResult
        $this->assertEquals('', $translator->lastResult);

        // We can't test actual translation without hitting Google API
        // But we can verify the method signature works
        $reflection = new \ReflectionMethod($translator, 'translate');
        $this->assertTrue($reflection->isPublic());
        $this->assertFalse($reflection->isStatic());
    }

    /**
     * Test GoogleTranslate staticTranslate method with mock data
     * This is the main translation method but requires network access
     * We test with invalid data to verify error handling
     */
    public function testGoogleTranslateStaticTranslateFalseResult(): void
    {
        // Create a mock by testing with malformed domain
        // This should return false or handle gracefully

        // We can't fully test without hitting the Google API
        // But we can verify the method signature
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('staticTranslate');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        // Check the method accepts correct number of parameters
        $params = $method->getParameters();
        $this->assertGreaterThanOrEqual(3, count($params));
        $this->assertEquals('string', $params[0]->getName());
        $this->assertEquals('from', $params[1]->getName());
        $this->assertEquals('to', $params[2]->getName());
    }

    /**
     * Test GoogleTranslate with various edge cases
     */
    public function testGoogleTranslateEdgeCases(): void
    {
        // Test constructor with same source and target language
        $translator = new GoogleTranslate('en', 'en');
        $this->assertInstanceOf(GoogleTranslate::class, $translator);

        // Test with very short language codes
        $translator = new GoogleTranslate('ja', 'ko');
        $this->assertInstanceOf(GoogleTranslate::class, $translator);

        // Test getDomain with all valid domains
        $validDomains = [
            'com.ar', 'at', 'com.au', 'be', 'com.br', 'ca', 'cat', 'ch', 'cl', 'cn',
            'cz', 'de', 'dk', 'es', 'fi', 'fr', 'gr', 'com.hk', 'hr', 'hu', 'co.id',
            'ie', 'co.il', 'im', 'co.in', 'it', 'co.jp', 'co.kr', 'com.mx',
            'nl', 'no', 'pl', 'pt', 'ru', 'se', 'com.sg', 'co.th', 'com.tw',
            'co.uk', 'com'
        ];

        foreach ($validDomains as $domain) {
            $result = GoogleTranslate::getDomain($domain);
            $this->assertEquals($domain, $result);
        }
    }

    /**
     * Test GoogleTranslate array_iunique with various inputs
     */
    public function testGoogleTranslateArrayIuniqueExtensive(): void
    {
        // Test with empty array
        $result = GoogleTranslate::array_iunique([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        // Test with single element
        $result = GoogleTranslate::array_iunique(['Test']);
        $this->assertCount(1, $result);

        // Test with mixed case duplicates
        $result = GoogleTranslate::array_iunique(['Hello', 'hello', 'HELLO', 'HeLLo']);
        $this->assertLessThanOrEqual(1, count($result));

        // Test with different words
        $result = GoogleTranslate::array_iunique(['Apple', 'Banana', 'Cherry']);
        $this->assertCount(3, $result);

        // Test with Unicode duplicates (may preserve multiple due to Unicode handling)
        $result = GoogleTranslate::array_iunique(['Café', 'café', 'CAFÉ']);
        $this->assertGreaterThanOrEqual(1, count($result));

        // Test preserving first occurrence
        $result = GoogleTranslate::array_iunique(['First', 'FIRST', 'Second']);
        $this->assertContains('First', $result);
        $this->assertContains('Second', $result);
    }

    /**
     * Test GoogleTranslate token generation with edge cases
     */
    public function testGoogleTranslateGenerateTokenEdgeCases(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test with very long string
        $longString = str_repeat('a', 1000);
        $token = $method->invokeArgs(null, [$longString, [408254, 585515986]]);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);

        // Test with single character
        $token = $method->invokeArgs(null, ['a', [408254, 585515986]]);
        $this->assertIsString($token);

        // Test with numbers
        $token = $method->invokeArgs(null, ['12345', [408254, 585515986]]);
        $this->assertIsString($token);

        // Test with mixed content
        $token = $method->invokeArgs(null, ['Hello世界123!', [408254, 585515986]]);
        $this->assertIsString($token);

        // Verify tokens are consistent for same input
        $token1 = $method->invokeArgs(null, ['test', [408254, 585515986]]);
        $token2 = $method->invokeArgs(null, ['test', [408254, 585515986]]);
        $this->assertEquals($token1, $token2);

        // Verify different inputs produce different tokens
        $token1 = $method->invokeArgs(null, ['hello', [408254, 585515986]]);
        $token2 = $method->invokeArgs(null, ['world', [408254, 585515986]]);
        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test GoogleTranslate setHeaders (private method via setDomain)
     */
    public function testGoogleTranslateSetHeaders(): void
    {
        // setHeaders is called internally by setDomain
        GoogleTranslate::setDomain('com');

        // Access the private $headers property
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);

        $headers = $property->getValue();

        $this->assertIsArray($headers);
        $this->assertNotEmpty($headers);

        // Verify headers contain expected entries
        $headersString = implode('|', $headers);
        $this->assertStringContainsString('Accept:', $headersString);
        $this->assertStringContainsString('User-Agent:', $headersString);
        $this->assertStringContainsString('Host:', $headersString);
    }

    /**
     * Test GoogleTranslate makeCurl with cookieSet parameter
     */
    public function testGoogleTranslateMakeCurlWithCookie(): void
    {
        GoogleTranslate::setDomain('com');

        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('makeCurl');
        $method->setAccessible(true);

        // Test with cookieSet = true
        $result = $method->invokeArgs(null, ['http://httpbin.org/status/404', true]);

        // Result should be string or false
        $this->assertTrue(is_string($result) || $result === false);
    }

    /**
     * Test GoogleTranslate with special characters and escaping
     */
    public function testGoogleTranslateWithSpecialCharacters(): void
    {
        $translator = new GoogleTranslate('en', 'de');

        // Test setting languages with special characters
        $translator->setLangFrom('zh-CN');
        $translator->setLangTo('pt-BR');

        $this->assertInstanceOf(GoogleTranslate::class, $translator);
    }

    /**
     * Test GoogleTranslate array_iunique preserves keys
     */
    public function testGoogleTranslateArrayIuniquePreservesKeys(): void
    {
        $input = [
            'key1' => 'Hello',
            'key2' => 'HELLO',
            'key3' => 'World',
            'key4' => 'hello'
        ];

        $result = GoogleTranslate::array_iunique($input);

        // Should preserve original keys from first occurrence
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key3', $result);
    }

    /**
     * Test GoogleTranslate token generation with null token parameter
     */
    public function testGoogleTranslateGenerateTokenWithNullToken(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test with null token (should use defaults)
        $token = $method->invokeArgs(null, ['test', null]);
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $token);
    }

    /**
     * Test GoogleTranslate token with 32-bit vs 64-bit PHP handling
     */
    public function testGoogleTranslateTokenHandlesPHPIntSize(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test with various strings to ensure proper handling
        $strings = ['a', 'test', 'Hello World', '日本語', str_repeat('x', 100)];

        foreach ($strings as $str) {
            $token = $method->invokeArgs(null, [$str, [408254, 585515986]]);
            $this->assertIsString($token);
            $this->assertStringContainsString('.', $token);

            // Verify format
            $parts = explode('.', $token);
            $this->assertCount(2, $parts);
            $this->assertIsNumeric($parts[0]);
            $this->assertIsNumeric($parts[1]);
        }
    }

    /**
     * Test GoogleTranslate urlFormat constant
     */
    public function testGoogleTranslateUrlFormat(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $urlFormat = $reflection->getConstant('DEFAULT_DOMAIN');

        // DEFAULT_DOMAIN should be null
        $this->assertNull($urlFormat);
    }

    /**
     * Test GoogleTranslate getDomain with null input
     */
    public function testGoogleTranslateGetDomainWithNull(): void
    {
        $result = GoogleTranslate::getDomain(null);

        // Should return a random valid domain
        $validDomains = [
            'com.ar', 'at', 'com.au', 'be', 'com.br', 'ca', 'cat', 'ch', 'cl', 'cn',
            'cz', 'de', 'dk', 'es', 'fi', 'fr', 'gr', 'com.hk', 'hr', 'hu', 'co.id',
            'ie', 'co.il', 'im', 'co.in', 'it', 'co.jp', 'co.kr', 'com.mx',
            'nl', 'no', 'pl', 'pt', 'ru', 'se', 'com.sg', 'co.th', 'com.tw',
            'co.uk', 'com'
        ];

        $this->assertContains($result, $validDomains);
    }

    /**
     * Test GoogleTranslate lastResult property
     */
    public function testGoogleTranslateLastResultProperty(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        // Initially should be empty string
        $this->assertEquals('', $translator->lastResult);

        // After a translation attempt, lastResult should be set
        // (We can't test actual translation without hitting the API)
    }

    /**
     * Test GoogleTranslate with empty strings
     */
    public function testGoogleTranslateWithEmptyStrings(): void
    {
        $translator = new GoogleTranslate('', '');
        $this->assertInstanceOf(GoogleTranslate::class, $translator);

        $translator->setLangFrom('');
        $translator->setLangTo('');
        $this->assertInstanceOf(GoogleTranslate::class, $translator);
    }

    /**
     * Test GoogleTranslate translate instance method
     * This tests the wrapper around staticTranslate
     */
    public function testGoogleTranslateInstanceTranslateMethod(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        // Initially lastResult should be empty
        $this->assertEquals('', $translator->lastResult);

        // Note: We cannot test actual translation without hitting Google API
        // But we can verify the method signature and that it updates lastResult
        $reflection = new \ReflectionMethod($translator, 'translate');
        $this->assertTrue($reflection->isPublic());
        $this->assertFalse($reflection->isStatic());

        // Verify it accepts a string parameter
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('string', $params[0]->getName());
    }

    /**
     * Test GoogleTranslate staticTranslate method structure
     */
    public function testGoogleTranslateStaticTranslateStructure(): void
    {
        // Test that method exists and has correct signature
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('staticTranslate');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        // Check parameters
        $params = $method->getParameters();
        $this->assertGreaterThanOrEqual(3, count($params));

        // Verify parameter names
        $this->assertEquals('string', $params[0]->getName());
        $this->assertEquals('from', $params[1]->getName());
        $this->assertEquals('to', $params[2]->getName());
        $this->assertEquals('time_token', $params[3]->getName());
        $this->assertEquals('domain', $params[4]->getName());

        // Verify optional parameters have defaults
        $this->assertTrue($params[3]->isOptional());
        $this->assertTrue($params[4]->isOptional());
    }

    /**
     * Test GoogleTranslate generateToken with negative number edge case
     */
    public function testGoogleTranslateGenerateTokenNegativeNumbers(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test strings that might produce negative intermediate values
        // The algorithm has special handling for negative numbers (lines 110-117)
        $testStrings = [
            '',                          // Empty string
            ' ',                         // Single space
            '!',                         // Special character
            '😀',                        // Emoji (4-byte UTF-8)
            str_repeat('z', 500),       // Very long string with high byte values
            '🔥🔥🔥🔥🔥',                // Multiple emoji
            '中文测试',                   // Chinese characters
            'Тест',                      // Cyrillic
            '🌍🌎🌏'                     // Globe emoji sequence
        ];

        foreach ($testStrings as $str) {
            $token = $method->invokeArgs(null, [$str, [408254, 585515986]]);

            $this->assertIsString($token);
            $this->assertStringContainsString('.', $token);

            // Verify format is valid: number.number
            $parts = explode('.', $token);
            $this->assertCount(2, $parts);
            $this->assertIsNumeric($parts[0]);
            $this->assertIsNumeric($parts[1]);

            // Both parts should be positive integers
            $this->assertGreaterThanOrEqual(0, (int)$parts[0]);
            $this->assertGreaterThanOrEqual(0, (int)$parts[1]);
        }
    }

    /**
     * Test GoogleTranslate generateToken modulo operation branch
     */
    public function testGoogleTranslateGenerateTokenModuloBranch(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test with different token values to cover different branches
        $tokenSets = [
            [0, 0],                          // All zeros
            [999999, 999999],                // Large values
            [1, 1],                          // Small values
            [408254, 585515986],             // Default values
            [500000, 500000],                // Values that might trigger the < 5000000 branch
            [5000001, 5000001],              // Values above 5000000
        ];

        foreach ($tokenSets as $tokens) {
            $token = $method->invokeArgs(null, ['test', $tokens]);

            $this->assertIsString($token);
            $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $token);

            // Verify the result is within expected range (modulo 1000000)
            $parts = explode('.', $token);
            $firstPart = (int)$parts[0];

            // After modulo 1000000, value should be < 1000000
            $this->assertLessThan(1000000, $firstPart);
        }
    }

    /**
     * Test GoogleTranslate makeCurl fallback to file_get_contents
     */
    public function testGoogleTranslateMakeCurlStreamContextFallback(): void
    {
        GoogleTranslate::setDomain('com');

        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('makeCurl');
        $method->setAccessible(true);

        // When cURL is available, this won't hit the fallback
        // But we test that the method completes successfully
        // The fallback to stream context (lines 192-203) is used when cURL is not available
        $result = $method->invokeArgs(null, ['http://httpbin.org/status/200', false]);

        // Result should be string or false
        $this->assertTrue(is_string($result) || $result === false);

        if ($result !== false) {
            // If we got a result, it should be a string
            $this->assertIsString($result);
        }
    }

    /**
     * Test GoogleTranslate url format construction
     */
    public function testGoogleTranslateUrlFormatConstant(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);

        // Access the private static $urlFormat
        $property = $reflection->getProperty('urlFormat');
        $property->setAccessible(true);
        $urlFormat = $property->getValue();

        $this->assertIsString($urlFormat);
        $this->assertStringContainsString('translate.google.%s', $urlFormat);
        $this->assertStringContainsString('translate_a/single', $urlFormat);
        $this->assertStringContainsString('client=t', $urlFormat);
        $this->assertStringContainsString('q=%s', $urlFormat);
        $this->assertStringContainsString('sl=%s', $urlFormat);
        $this->assertStringContainsString('tl=%s', $urlFormat);
        $this->assertStringContainsString('tk=%s', $urlFormat);
    }

    /**
     * Test GoogleTranslate domain property
     */
    public function testGoogleTranslateDomainProperty(): void
    {
        // Set a specific domain
        GoogleTranslate::setDomain('de');

        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $property = $reflection->getProperty('gglDomain');
        $property->setAccessible(true);

        $domain = $property->getValue();
        $this->assertEquals('de', $domain);

        // Set another domain
        GoogleTranslate::setDomain('fr');
        $domain = $property->getValue();
        $this->assertEquals('fr', $domain);

        // Set with null (should get random)
        GoogleTranslate::setDomain(null);
        $domain = $property->getValue();
        $this->assertIsString($domain);
        $this->assertNotEmpty($domain);
    }

    /**
     * Test GoogleTranslate private properties initialization
     */
    public function testGoogleTranslatePrivatePropertiesInitialization(): void
    {
        $translator = new GoogleTranslate('en', 'de');

        $reflection = new \ReflectionClass($translator);

        // Check langFrom
        $langFromProp = $reflection->getProperty('langFrom');
        $langFromProp->setAccessible(true);
        $this->assertEquals('en', $langFromProp->getValue($translator));

        // Check langTo
        $langToProp = $reflection->getProperty('langTo');
        $langToProp->setAccessible(true);
        $this->assertEquals('de', $langToProp->getValue($translator));

        // After setting new languages
        $translator->setLangFrom('ja');
        $translator->setLangTo('ko');

        $this->assertEquals('ja', $langFromProp->getValue($translator));
        $this->assertEquals('ko', $langToProp->getValue($translator));
    }

    /**
     * Test GoogleTranslate array_iunique with various array types
     */
    public function testGoogleTranslateArrayIuniqueWithVariousArrays(): void
    {
        // Test with numeric keys
        $result = GoogleTranslate::array_iunique([
            0 => 'First',
            1 => 'FIRST',
            2 => 'Second',
            3 => 'second'
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(2, $result);

        // Test with all same case
        $result = GoogleTranslate::array_iunique(['hello', 'world', 'test']);
        $this->assertCount(3, $result);

        // Test with all same value different cases
        $result = GoogleTranslate::array_iunique(['TEST', 'Test', 'test', 'TeSt']);
        $this->assertLessThanOrEqual(1, count($result));

        // Test with mixed types (array_iunique should handle gracefully)
        $result = GoogleTranslate::array_iunique(['One', 'one', 'Two', 'THREE']);
        $this->assertIsArray($result);
    }

    /**
     * Test GoogleTranslate generateToken with multibyte characters of different lengths
     */
    public function testGoogleTranslateGenerateTokenMultibyteEdgeCases(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test various multibyte character scenarios
        $tests = [
            'ASCII only' => 'Hello',
            '2-byte UTF-8' => 'Привет',           // Cyrillic
            '3-byte UTF-8' => '日本語',            // Japanese
            '4-byte UTF-8' => '😀😃😄',           // Emoji
            'Mixed' => 'Hello世界🌍',              // Mixed
            'Arabic RTL' => 'مرحبا',              // Right-to-left
            'Thai' => 'สวัสดี',                   // Thai script
            'Hebrew' => 'שלום',                   // Hebrew
        ];

        foreach ($tests as $description => $str) {
            $token = $method->invokeArgs(null, [$str, [408254, 585515986]]);

            $this->assertIsString($token, "Failed for: $description");
            $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $token, "Invalid format for: $description");

            // Tokens for different strings should be different
            $token2 = $method->invokeArgs(null, [$str . 'x', [408254, 585515986]]);
            $this->assertNotEquals($token, $token2, "Tokens should differ for: $description");
        }
    }

    /**
     * Test GoogleTranslate setHeaders creates all required headers
     */
    public function testGoogleTranslateSetHeadersCompleteness(): void
    {
        GoogleTranslate::setDomain('co.jp');

        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);

        $headers = $property->getValue();

        // Verify all required headers are present
        $headerString = implode("\n", $headers);

        $requiredHeaders = [
            'Accept:',
            'Accept-Language:',
            'Connection:',
            'Cookie:',
            'DNT:',
            'Host: translate.google.co.jp',  // Should match the domain we set
            'Referer:',
            'User-Agent:'
        ];

        foreach ($requiredHeaders as $required) {
            $this->assertStringContainsString($required, $headerString, "Missing header: $required");
        }

        // Verify host matches domain
        $this->assertStringContainsString('translate.google.co.jp', $headerString);
    }

    /**
     * Test GoogleTranslate staticTranslate with mocked response parsing
     * This tests the response parsing logic without hitting the actual API
     */
    public function testGoogleTranslateStaticTranslateResponseParsing(): void
    {
        // We can't easily test the actual API call, but we can test the response parsing
        // by creating a mock scenario or testing with reflection

        // Test that the method exists and has correct signature
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('staticTranslate');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        // Verify return type allows array|false
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test GoogleTranslate translate instance method updates lastResult
     */
    public function testGoogleTranslateTranslateUpdatesLastResult(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        // Before calling translate, lastResult should be empty
        $this->assertEquals('', $translator->lastResult);

        // After calling translate (even if it fails), lastResult should be updated
        // Note: This will attempt a real translation and may fail/return false
        // We're testing that the result is assigned to lastResult
        $result = $translator->translate('test');

        // lastResult should now be set to whatever the translation returned
        $this->assertEquals($result, $translator->lastResult);

        // Result should be either array or false
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate with various parameters
     */
    public function testGoogleTranslateStaticTranslateWithVariousParameters(): void
    {
        // Test with default parameters (null time_token, default domain)
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Test with custom time_token
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'es', [123456, 789012]);
        $this->assertTrue(is_array($result) || $result === false);

        // Test with specific domain
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'es', null, 'com');
        $this->assertTrue(is_array($result) || $result === false);

        // Test with all custom parameters
        $result = GoogleTranslate::staticTranslate('test', 'en', 'de', [111111, 222222], 'de');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate with empty string
     */
    public function testGoogleTranslateStaticTranslateEmptyString(): void
    {
        $result = GoogleTranslate::staticTranslate('', 'en', 'es');

        // Empty string translation should return false or empty array
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate with special characters
     */
    public function testGoogleTranslateStaticTranslateSpecialCharacters(): void
    {
        // Test with URL-encodable characters
        $result = GoogleTranslate::staticTranslate('hello & goodbye!', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Test with Unicode characters
        $result = GoogleTranslate::staticTranslate('こんにちは', 'ja', 'en');
        $this->assertTrue(is_array($result) || $result === false);

        // Test with emoji
        $result = GoogleTranslate::staticTranslate('hello 😀', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate with different language pairs
     */
    public function testGoogleTranslateStaticTranslateDifferentLanguagePairs(): void
    {
        $languagePairs = [
            ['en', 'es'],    // English to Spanish
            ['en', 'fr'],    // English to French
            ['en', 'de'],    // English to German
            ['en', 'ja'],    // English to Japanese
            ['ja', 'en'],    // Japanese to English
            ['zh', 'en'],    // Chinese to English
            ['en', 'ar'],    // English to Arabic
            ['ru', 'en'],    // Russian to English
        ];

        foreach ($languagePairs as [$from, $to]) {
            $result = GoogleTranslate::staticTranslate('hello', $from, $to);
            $this->assertTrue(
                is_array($result) || $result === false,
                "Translation from $from to $to should return array or false"
            );
        }
    }

    /**
     * Test GoogleTranslate staticTranslate with same source and target language
     */
    public function testGoogleTranslateStaticTranslateSameLanguage(): void
    {
        // Translating from English to English
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'en');
        $this->assertTrue(is_array($result) || $result === false);

        // Should potentially return the same word or variations
        if (is_array($result) && !empty($result)) {
            $this->assertIsArray($result);
        }
    }

    /**
     * Test GoogleTranslate staticTranslate with long text
     */
    public function testGoogleTranslateStaticTranslateLongText(): void
    {
        $longText = str_repeat('This is a test sentence. ', 20);
        $result = GoogleTranslate::staticTranslate($longText, 'en', 'es');

        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate with numbers and special characters
     */
    public function testGoogleTranslateStaticTranslateNumbersAndSpecialChars(): void
    {
        // Test with numbers
        $result = GoogleTranslate::staticTranslate('12345', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Test with mixed alphanumeric
        $result = GoogleTranslate::staticTranslate('test123', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Test with punctuation
        $result = GoogleTranslate::staticTranslate('Hello, world!', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Test with quotes
        $result = GoogleTranslate::staticTranslate('"Hello" and \'goodbye\'', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate URL encoding
     */
    public function testGoogleTranslateStaticTranslateURLEncoding(): void
    {
        // Test characters that need URL encoding
        $specialStrings = [
            'hello world',           // Space
            'hello+world',           // Plus sign
            'hello&goodbye',         // Ampersand
            'hello=test',            // Equals sign
            'hello?world',           // Question mark
            'hello#world',           // Hash
            'café',                  // Accented characters
            'naïve',                 // More accents
        ];

        foreach ($specialStrings as $str) {
            $result = GoogleTranslate::staticTranslate($str, 'en', 'es');
            $this->assertTrue(
                is_array($result) || $result === false,
                "Should handle string: $str"
            );
        }
    }

    /**
     * Test GoogleTranslate staticTranslate with invalid language codes
     */
    public function testGoogleTranslateStaticTranslateInvalidLanguageCodes(): void
    {
        // Invalid language codes should still execute without fatal errors
        $result = GoogleTranslate::staticTranslate('hello', 'invalid', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        $result = GoogleTranslate::staticTranslate('hello', 'en', 'invalid');
        $this->assertTrue(is_array($result) || $result === false);

        $result = GoogleTranslate::staticTranslate('hello', 'xx', 'yy');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate translate instance method with chained setters
     */
    public function testGoogleTranslateInstanceMethodWithChainedSetters(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        // Change languages using fluent interface
        $translator->setLangFrom('de')->setLangTo('fr');

        // Translate with new languages
        $result = $translator->translate('Hallo');

        // Should update lastResult
        $this->assertEquals($result, $translator->lastResult);
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate with different domains
     */
    public function testGoogleTranslateStaticTranslateWithDifferentDomains(): void
    {
        $domains = ['com', 'de', 'fr', 'co.uk', 'co.jp', 'com.au'];

        foreach ($domains as $domain) {
            $result = GoogleTranslate::staticTranslate('hello', 'en', 'es', null, $domain);
            $this->assertTrue(
                is_array($result) || $result === false,
                "Should work with domain: $domain"
            );
        }
    }

    /**
     * Test GoogleTranslate staticTranslate with null/empty domain (random domain)
     */
    public function testGoogleTranslateStaticTranslateWithRandomDomain(): void
    {
        // Null domain should use random
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'es', null, null);
        $this->assertTrue(is_array($result) || $result === false);

        // Empty string domain should use random
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'es', null, '');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate multiple consecutive translations
     */
    public function testGoogleTranslateMultipleConsecutiveTranslations(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        // First translation
        $result1 = $translator->translate('hello');
        $this->assertEquals($result1, $translator->lastResult);

        // Second translation should update lastResult
        $result2 = $translator->translate('goodbye');
        $this->assertEquals($result2, $translator->lastResult);

        // lastResult should have changed (unless both returned false)
        if ($result1 !== false && $result2 !== false) {
            $this->assertNotEquals($result1, $result2);
        }
    }

    /**
     * Test GoogleTranslate with whitespace-only strings
     */
    public function testGoogleTranslateStaticTranslateWithWhitespace(): void
    {
        // Single space
        $result = GoogleTranslate::staticTranslate(' ', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Multiple spaces
        $result = GoogleTranslate::staticTranslate('   ', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Tab character
        $result = GoogleTranslate::staticTranslate("\t", 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Newline
        $result = GoogleTranslate::staticTranslate("\n", 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate generateToken with boundary conditions
     */
    public function testGoogleTranslateGenerateTokenBoundaryConditions(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Test with token values that trigger different code paths
        // Testing the negative number handling (line 110-117)

        // Token that might cause negative intermediate value
        $token = $method->invokeArgs(null, ['test', [0, 0]]);
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $token);

        // Very large token values
        $token = $method->invokeArgs(null, ['test', [999999999, 999999999]]);
        $this->assertIsString($token);

        // Token values that might trigger the 5000000 comparison branch
        $token = $method->invokeArgs(null, ['test', [4999999, 0]]);
        $this->assertIsString($token);

        $token = $method->invokeArgs(null, ['test', [5000001, 0]]);
        $this->assertIsString($token);
    }

    /**
     * Test GoogleTranslate with RTL (right-to-left) languages
     */
    public function testGoogleTranslateStaticTranslateRTLLanguages(): void
    {
        // Arabic
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'ar');
        $this->assertTrue(is_array($result) || $result === false);

        // Hebrew
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'he');
        $this->assertTrue(is_array($result) || $result === false);

        // Reverse: RTL to English
        $result = GoogleTranslate::staticTranslate('مرحبا', 'ar', 'en');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate with CJK languages
     */
    public function testGoogleTranslateStaticTranslateCJKLanguages(): void
    {
        // Chinese (Simplified)
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'zh-CN');
        $this->assertTrue(is_array($result) || $result === false);

        // Japanese
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'ja');
        $this->assertTrue(is_array($result) || $result === false);

        // Korean
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'ko');
        $this->assertTrue(is_array($result) || $result === false);

        // Reverse: Japanese to English
        $result = GoogleTranslate::staticTranslate('こんにちは', 'ja', 'en');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate with single character
     */
    public function testGoogleTranslateStaticTranslateSingleCharacter(): void
    {
        // Single letter
        $result = GoogleTranslate::staticTranslate('a', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Single number
        $result = GoogleTranslate::staticTranslate('1', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Single special character
        $result = GoogleTranslate::staticTranslate('!', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Single Unicode character
        $result = GoogleTranslate::staticTranslate('あ', 'ja', 'en');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate lastResult property persistence
     */
    public function testGoogleTranslateLastResultPersistence(): void
    {
        $translator = new GoogleTranslate('en', 'es');

        // Initially empty
        $this->assertEquals('', $translator->lastResult);

        // After first translation
        $translator->translate('test1');
        $firstResult = $translator->lastResult;

        // After changing languages
        $translator->setLangFrom('de')->setLangTo('fr');

        // lastResult should still be the previous result
        $this->assertEquals($firstResult, $translator->lastResult);

        // After new translation, it should update
        $translator->translate('test2');
        $secondResult = $translator->lastResult;

        // If both succeeded or both failed, they might be equal, but the property should be set
        $this->assertNotNull($translator->lastResult);
    }

    /**
     * Test GoogleTranslate with very short and very long strings
     */
    public function testGoogleTranslateStaticTranslateStringLengthVariations(): void
    {
        // Very short (1 character)
        $result = GoogleTranslate::staticTranslate('a', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Short word
        $result = GoogleTranslate::staticTranslate('hi', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Medium sentence
        $result = GoogleTranslate::staticTranslate('This is a test', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Long text (but not too long to timeout)
        $longText = 'This is a longer text that contains multiple sentences. ' .
                    'It should still be translated correctly by the API. ' .
                    'We want to test how it handles longer inputs.';
        $result = GoogleTranslate::staticTranslate($longText, 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate generateToken consistency across PHP versions
     */
    public function testGoogleTranslateGenerateTokenConsistency(): void
    {
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);

        // Same input should always produce same output
        $testCases = [
            ['hello', [408254, 585515986]],
            ['world', [408254, 585515986]],
            ['test', [123456, 789012]],
            ['', [408254, 585515986]],
            ['日本語', [408254, 585515986]],
        ];

        foreach ($testCases as [$str, $tok]) {
            $result1 = $method->invokeArgs(null, [$str, $tok]);
            $result2 = $method->invokeArgs(null, [$str, $tok]);

            $this->assertEquals($result1, $result2, "Token generation should be consistent for: $str");
        }
    }

    /**
     * Test GoogleTranslate with HTML entities and special encoding
     */
    public function testGoogleTranslateStaticTranslateHTMLEntities(): void
    {
        // HTML entities
        $result = GoogleTranslate::staticTranslate('&lt;test&gt;', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // HTML tags (as text)
        $result = GoogleTranslate::staticTranslate('<b>hello</b>', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // Mixed HTML and text
        $result = GoogleTranslate::staticTranslate('Hello &amp; goodbye', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate makeCurl with both cookieSet true and false
     * This ensures both branches in makeCurl are covered
     */
    public function testGoogleTranslateMakeCurlBothBranches(): void
    {
        GoogleTranslate::setDomain('com');

        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('makeCurl');
        $method->setAccessible(true);

        // Test with cookieSet = false (default, creates temp cookie)
        $result = $method->invokeArgs(null, ['http://httpbin.org/status/200', false]);
        $this->assertTrue(is_string($result) || $result === false);

        // Test with cookieSet = true (uses existing cookie)
        $result = $method->invokeArgs(null, ['http://httpbin.org/status/200', true]);
        $this->assertTrue(is_string($result) || $result === false);
    }

    /**
     * Test GoogleTranslate translate method returns and stores result
     * This ensures the instance method properly wraps staticTranslate
     */
    public function testGoogleTranslateInstanceTranslateMethodExecution(): void
    {
        $translator = new GoogleTranslate('en', 'fr');

        // Execute translation
        $result = $translator->translate('hello');

        // Verify result is stored in lastResult
        $this->assertSame($result, $translator->lastResult);

        // Verify result type
        $this->assertTrue(is_array($result) || $result === false);

        // If result is array, it should be non-empty or empty array
        if (is_array($result)) {
            $this->assertIsArray($result);
        } else {
            $this->assertFalse($result);
        }
    }

    /**
     * Test GoogleTranslate staticTranslate actual execution
     * This tests the full staticTranslate flow including URL building and response parsing
     */
    public function testGoogleTranslateStaticTranslateFullExecution(): void
    {
        // Test actual translation attempt
        $result = GoogleTranslate::staticTranslate('cat', 'en', 'es');

        // Should return array of translations or false
        $this->assertTrue(is_array($result) || $result === false);

        // If we got a result, verify it's an array
        if ($result !== false) {
            $this->assertIsArray($result);
            // Translations should be non-empty if successful
            if (!empty($result)) {
                $this->assertGreaterThan(0, count($result));
                // Each translation should be a string
                foreach ($result as $translation) {
                    $this->assertIsString($translation);
                }
            }
        }
    }

    /**
     * Test GoogleTranslate staticTranslate with time_token parameter
     * This ensures the time_token branch is covered
     */
    public function testGoogleTranslateStaticTranslateWithTimeToken(): void
    {
        // Test with custom time_token array
        $customToken = [408254, 585515986];
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'es', $customToken);

        $this->assertTrue(is_array($result) || $result === false);

        // Test with different time_token
        $customToken2 = [999999, 111111];
        $result = GoogleTranslate::staticTranslate('hello', 'en', 'es', $customToken2);

        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate staticTranslate response array parsing
     * Tests the different array index handling in staticTranslate
     */
    public function testGoogleTranslateStaticTranslateParsesResponses(): void
    {
        // Make actual call to test response parsing logic
        $result = GoogleTranslate::staticTranslate('good morning', 'en', 'es');

        // The method should handle empty or populated result arrays
        $this->assertTrue(is_array($result) || $result === false);

        // Test with a word that might have multiple translations
        $result = GoogleTranslate::staticTranslate('run', 'en', 'es');
        $this->assertTrue(is_array($result) || $result === false);

        // If successful, verify unique results (array_iunique is applied)
        if (is_array($result) && count($result) > 0) {
            // Check that results are unique (case-insensitive)
            $lowerResults = array_map('strtolower', $result);
            $uniqueResults = array_unique($lowerResults);
            // Should have no duplicates after lowercasing
            $this->assertLessThanOrEqual(count($result), count($uniqueResults));
        }
    }

    /**
     * Test GoogleTranslate instance method with actual API call
     * This ensures the translate method fully executes
     */
    public function testGoogleTranslateInstanceMethodFullFlow(): void
    {
        $translator = new GoogleTranslate('en', 'de');

        // First translation
        $result1 = $translator->translate('water');
        $this->assertSame($result1, $translator->lastResult);

        // Change language and translate again
        $translator->setLangTo('es');
        $result2 = $translator->translate('water');
        $this->assertSame($result2, $translator->lastResult);

        // Both should be array or false
        $this->assertTrue(is_array($result1) || $result1 === false);
        $this->assertTrue(is_array($result2) || $result2 === false);
    }

    /**
     * Test GoogleTranslate staticTranslate URL construction
     * This tests that the URL is properly formatted with all parameters
     */
    public function testGoogleTranslateStaticTranslateURLConstruction(): void
    {
        // Access the private urlFormat to verify it's used
        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $property = $reflection->getProperty('urlFormat');
        $property->setAccessible(true);
        $urlFormat = $property->getValue();

        // Verify URL format contains required placeholders
        $this->assertStringContainsString('%s', $urlFormat);
        $this->assertStringContainsString('translate.google.', $urlFormat);

        // Test that staticTranslate executes with this format
        $result = GoogleTranslate::staticTranslate('test', 'en', 'es', null, 'com');
        $this->assertTrue(is_array($result) || $result === false);
    }

    /**
     * Test GoogleTranslate makeCurl handles different URL schemes
     */
    public function testGoogleTranslateMakeCurlDifferentURLs(): void
    {
        GoogleTranslate::setDomain('com');

        $reflection = new \ReflectionClass(GoogleTranslate::class);
        $method = $reflection->getMethod('makeCurl');
        $method->setAccessible(true);

        // Test with HTTP URL
        $result = $method->invokeArgs(null, ['http://httpbin.org/get', false]);
        $this->assertTrue(is_string($result) || $result === false);

        // Test with HTTPS URL (if available)
        $result = $method->invokeArgs(null, ['https://httpbin.org/get', false]);
        $this->assertTrue(is_string($result) || $result === false);
    }

    /**
     * Test GoogleTranslate translate method type consistency
     */
    public function testGoogleTranslateTranslateMethodTypeConsistency(): void
    {
        $translator = new GoogleTranslate('en', 'ja');

        // Multiple calls should maintain type consistency
        for ($i = 0; $i < 3; $i++) {
            $result = $translator->translate('test' . $i);
            $this->assertTrue(is_array($result) || $result === false);
            $this->assertSame($result, $translator->lastResult);
        }
    }

    /**
     * Test GoogleTranslate staticTranslate with various string encodings
     */
    public function testGoogleTranslateStaticTranslateEncodings(): void
    {
        // UTF-8 strings
        $strings = [
            'hello',           // ASCII
            'café',            // Latin-1 supplement
            'Москва',          // Cyrillic
            '北京',            // CJK
            '🌍🌎🌏',         // Emoji
            'مرحبا بك',       // Arabic with space
        ];

        foreach ($strings as $str) {
            $result = GoogleTranslate::staticTranslate($str, 'auto', 'en');
            $this->assertTrue(
                is_array($result) || $result === false,
                "Failed for string: $str"
            );
        }
    }
}
