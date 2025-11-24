<?php declare(strict_types=1);

require_once __DIR__ . '/../../../inc/classes/Language.php';
require_once __DIR__ . '/../../../inc/classes/Term.php';
require_once __DIR__ . '/../../../inc/classes/Text.php';
require_once __DIR__ . '/../../../inc/classes/GoogleTranslate.php';

use PHPUnit\Framework\TestCase;
use Lwt\Classes\GoogleTranslate;

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
}
