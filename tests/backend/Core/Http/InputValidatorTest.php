<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Http;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Http/InputValidator.php';

/**
 * Unit tests for the InputValidator class.
 *
 * Tests input validation and extraction from request superglobals.
 */
class InputValidatorTest extends TestCase
{
    private array $originalRequest;
    private array $originalGet;
    private array $originalPost;
    private array $originalFiles;
    private array $originalServer;
    private array $originalSession;

    protected function setUp(): void
    {
        parent::setUp();

        // Save original superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalFiles = $_FILES;
        $this->originalServer = $_SERVER;
        $this->originalSession = $_SESSION ?? [];

        // Reset superglobals
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_FILES = $this->originalFiles;
        $_SERVER = $this->originalServer;
        $_SESSION = $this->originalSession;

        parent::tearDown();
    }

    // ===== getString tests =====

    public function testGetStringReturnsValue(): void
    {
        $_REQUEST['name'] = 'John Doe';

        $this->assertEquals('John Doe', InputValidator::getString('name'));
    }

    public function testGetStringTrimsWhitespace(): void
    {
        $_REQUEST['name'] = '  John Doe  ';

        $this->assertEquals('John Doe', InputValidator::getString('name'));
    }

    public function testGetStringNoTrimPreservesWhitespace(): void
    {
        $_REQUEST['name'] = '  John Doe  ';

        $this->assertEquals('  John Doe  ', InputValidator::getString('name', '', false));
    }

    public function testGetStringReturnsDefaultWhenMissing(): void
    {
        $this->assertEquals('default', InputValidator::getString('missing', 'default'));
    }

    public function testGetStringReturnsDefaultForNonString(): void
    {
        $_REQUEST['arr'] = ['not', 'a', 'string'];

        $this->assertEquals('default', InputValidator::getString('arr', 'default'));
    }

    public function testGetStringFromGetReturnsValue(): void
    {
        $_GET['param'] = 'value';

        $this->assertEquals('value', InputValidator::getStringFromGet('param'));
    }

    public function testGetStringFromPostReturnsValue(): void
    {
        $_POST['param'] = 'value';

        $this->assertEquals('value', InputValidator::getStringFromPost('param'));
    }

    // ===== getInt tests =====

    public function testGetIntReturnsValue(): void
    {
        $_REQUEST['id'] = '42';

        $this->assertEquals(42, InputValidator::getInt('id'));
    }

    public function testGetIntReturnsDefaultWhenMissing(): void
    {
        $this->assertEquals(10, InputValidator::getInt('missing', 10));
    }

    public function testGetIntReturnsDefaultForNonNumeric(): void
    {
        $_REQUEST['id'] = 'not a number';

        $this->assertEquals(0, InputValidator::getInt('id', 0));
    }

    public function testGetIntRespectsMinimum(): void
    {
        $_REQUEST['id'] = '5';

        $this->assertNull(InputValidator::getInt('id', null, 10));
    }

    public function testGetIntRespectsMaximum(): void
    {
        $_REQUEST['id'] = '100';

        $this->assertNull(InputValidator::getInt('id', null, null, 50));
    }

    public function testGetIntAcceptsValueWithinRange(): void
    {
        $_REQUEST['id'] = '25';

        $this->assertEquals(25, InputValidator::getInt('id', null, 10, 50));
    }

    public function testGetIntAcceptsNegativeNumbers(): void
    {
        $_REQUEST['offset'] = '-10';

        $this->assertEquals(-10, InputValidator::getInt('offset'));
    }

    // ===== requireInt tests =====

    public function testRequireIntReturnsValue(): void
    {
        $_REQUEST['id'] = '42';

        $this->assertEquals(42, InputValidator::requireInt('id'));
    }

    public function testRequireIntThrowsForMissingParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Required parameter 'missing' is missing");

        InputValidator::requireInt('missing');
    }

    public function testRequireIntThrowsForNonNumeric(): void
    {
        $_REQUEST['id'] = 'abc';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Parameter 'id' must be a valid integer");

        InputValidator::requireInt('id');
    }

    public function testRequireIntThrowsWhenBelowMinimum(): void
    {
        $_REQUEST['id'] = '5';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Parameter 'id' must be at least 10");

        InputValidator::requireInt('id', 10);
    }

    public function testRequireIntThrowsWhenAboveMaximum(): void
    {
        $_REQUEST['id'] = '100';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Parameter 'id' must be at most 50");

        InputValidator::requireInt('id', null, 50);
    }

    // ===== getPositiveInt tests =====

    public function testGetPositiveIntReturnsPositiveValue(): void
    {
        $_REQUEST['count'] = '5';

        $this->assertEquals(5, InputValidator::getPositiveInt('count'));
    }

    public function testGetPositiveIntRejectsZero(): void
    {
        $_REQUEST['count'] = '0';

        $this->assertNull(InputValidator::getPositiveInt('count'));
    }

    public function testGetPositiveIntRejectsNegative(): void
    {
        $_REQUEST['count'] = '-5';

        $this->assertNull(InputValidator::getPositiveInt('count'));
    }

    // ===== getNonNegativeInt tests =====

    public function testGetNonNegativeIntAcceptsZero(): void
    {
        $_REQUEST['offset'] = '0';

        $this->assertEquals(0, InputValidator::getNonNegativeInt('offset'));
    }

    public function testGetNonNegativeIntRejectsNegative(): void
    {
        $_REQUEST['offset'] = '-1';

        $this->assertNull(InputValidator::getNonNegativeInt('offset'));
    }

    // ===== getFloat tests =====

    public function testGetFloatReturnsValue(): void
    {
        $_REQUEST['price'] = '19.99';

        $this->assertEquals(19.99, InputValidator::getFloat('price'));
    }

    public function testGetFloatReturnsDefaultForNonNumeric(): void
    {
        $_REQUEST['price'] = 'free';

        $this->assertEquals(0.0, InputValidator::getFloat('price', 0.0));
    }

    public function testGetFloatRespectsRange(): void
    {
        $_REQUEST['rating'] = '3.5';

        $this->assertEquals(3.5, InputValidator::getFloat('rating', null, 0.0, 5.0));
    }

    // ===== getBool tests =====

    public function testGetBoolTrueValues(): void
    {
        $trueValues = ['1', 'true', 'yes', 'on', 'TRUE', 'Yes', 'ON'];

        foreach ($trueValues as $value) {
            $_REQUEST['flag'] = $value;
            $this->assertTrue(
                InputValidator::getBool('flag'),
                "Expected true for '$value'"
            );
        }
    }

    public function testGetBoolFalseValues(): void
    {
        $falseValues = ['0', 'false', 'no', 'off', '', 'FALSE', 'No', 'OFF'];

        foreach ($falseValues as $value) {
            $_REQUEST['flag'] = $value;
            $this->assertFalse(
                InputValidator::getBool('flag'),
                "Expected false for '$value'"
            );
        }
    }

    public function testGetBoolReturnsDefaultForInvalid(): void
    {
        $_REQUEST['flag'] = 'maybe';

        $this->assertNull(InputValidator::getBool('flag'));
        $this->assertTrue(InputValidator::getBool('flag', true));
    }

    // ===== getArray tests =====

    public function testGetArrayReturnsValue(): void
    {
        $_REQUEST['items'] = ['a', 'b', 'c'];

        $this->assertEquals(['a', 'b', 'c'], InputValidator::getArray('items'));
    }

    public function testGetArrayReturnsDefaultForNonArray(): void
    {
        $_REQUEST['items'] = 'not an array';

        $this->assertEquals([], InputValidator::getArray('items'));
    }

    // ===== getIntArray tests =====

    public function testGetIntArrayReturnsIntegers(): void
    {
        $_REQUEST['ids'] = ['1', '2', '3'];

        $this->assertEquals([1, 2, 3], InputValidator::getIntArray('ids'));
    }

    public function testGetIntArrayFiltersNonNumeric(): void
    {
        $_REQUEST['ids'] = ['1', 'abc', '3', 'def'];

        $this->assertEquals([1, 3], InputValidator::getIntArray('ids'));
    }

    // ===== getStringArray tests =====

    public function testGetStringArrayReturnsStrings(): void
    {
        $_REQUEST['names'] = ['  Alice  ', '  Bob  '];

        $this->assertEquals(['Alice', 'Bob'], InputValidator::getStringArray('names'));
    }

    public function testGetStringArrayNoTrimPreservesWhitespace(): void
    {
        $_REQUEST['names'] = ['  Alice  ', '  Bob  '];

        $this->assertEquals(
            ['  Alice  ', '  Bob  '],
            InputValidator::getStringArray('names', [], false)
        );
    }

    // ===== getEnum tests =====

    public function testGetEnumReturnsValidValue(): void
    {
        $_REQUEST['status'] = 'active';

        $this->assertEquals(
            'active',
            InputValidator::getEnum('status', ['active', 'inactive', 'pending'])
        );
    }

    public function testGetEnumReturnsDefaultForInvalid(): void
    {
        $_REQUEST['status'] = 'unknown';

        $this->assertEquals(
            'active',
            InputValidator::getEnum('status', ['active', 'inactive'], 'active')
        );
    }

    // ===== getIntEnum tests =====

    public function testGetIntEnumReturnsValidValue(): void
    {
        $_REQUEST['level'] = '2';

        $this->assertEquals(2, InputValidator::getIntEnum('level', [1, 2, 3], 1));
    }

    public function testGetIntEnumReturnsDefaultForInvalid(): void
    {
        $_REQUEST['level'] = '5';

        $this->assertEquals(1, InputValidator::getIntEnum('level', [1, 2, 3], 1));
    }

    // ===== getUrl tests =====

    public function testGetUrlReturnsValidUrl(): void
    {
        $_REQUEST['website'] = 'https://example.com/path?query=1';

        $this->assertEquals(
            'https://example.com/path?query=1',
            InputValidator::getUrl('website')
        );
    }

    public function testGetUrlReturnsDefaultForInvalid(): void
    {
        $_REQUEST['website'] = 'not a url';

        $this->assertEquals('', InputValidator::getUrl('website'));
    }

    // ===== getEmail tests =====

    public function testGetEmailReturnsValidEmail(): void
    {
        $_REQUEST['email'] = 'user@example.com';

        $this->assertEquals('user@example.com', InputValidator::getEmail('email'));
    }

    public function testGetEmailReturnsDefaultForInvalid(): void
    {
        $_REQUEST['email'] = 'not an email';

        $this->assertEquals('', InputValidator::getEmail('email'));
    }

    // ===== matchesPattern tests =====

    public function testMatchesPatternReturnsTrue(): void
    {
        $this->assertTrue(InputValidator::matchesPattern('abc123', '/^[a-z0-9]+$/'));
    }

    public function testMatchesPatternReturnsFalse(): void
    {
        $this->assertFalse(InputValidator::matchesPattern('ABC', '/^[a-z]+$/'));
    }

    // ===== getStringMatching tests =====

    public function testGetStringMatchingReturnsMatchingValue(): void
    {
        $_REQUEST['code'] = 'ABC-123';

        $this->assertEquals(
            'ABC-123',
            InputValidator::getStringMatching('code', '/^[A-Z]+-\d+$/')
        );
    }

    public function testGetStringMatchingReturnsDefaultForNonMatch(): void
    {
        $_REQUEST['code'] = 'invalid';

        $this->assertEquals(
            '',
            InputValidator::getStringMatching('code', '/^[A-Z]+-\d+$/')
        );
    }

    // ===== sanitizeHtml tests =====

    public function testSanitizeHtmlEscapesSpecialChars(): void
    {
        $input = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';

        $this->assertEquals($expected, InputValidator::sanitizeHtml($input));
    }

    public function testGetHtmlSafeReturnsSanitizedValue(): void
    {
        $_REQUEST['content'] = '<b>Bold</b>';

        $this->assertEquals('&lt;b&gt;Bold&lt;/b&gt;', InputValidator::getHtmlSafe('content'));
    }

    // ===== has and hasValue tests =====

    public function testHasReturnsTrueWhenSet(): void
    {
        $_REQUEST['key'] = 'value';

        $this->assertTrue(InputValidator::has('key'));
    }

    public function testHasReturnsFalseWhenNotSet(): void
    {
        $this->assertFalse(InputValidator::has('missing'));
    }

    public function testHasValueReturnsTrueForNonEmpty(): void
    {
        $_REQUEST['key'] = 'value';

        $this->assertTrue(InputValidator::hasValue('key'));
    }

    public function testHasValueReturnsFalseForEmptyString(): void
    {
        $_REQUEST['key'] = '   ';

        $this->assertFalse(InputValidator::hasValue('key'));
    }

    public function testHasValueReturnsTrueForNonEmptyArray(): void
    {
        $_REQUEST['items'] = [1, 2, 3];

        $this->assertTrue(InputValidator::hasValue('items'));
    }

    public function testHasValueReturnsFalseForEmptyArray(): void
    {
        $_REQUEST['items'] = [];

        $this->assertFalse(InputValidator::hasValue('items'));
    }

    // ===== Request method tests =====

    public function testGetMethodReturnsRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertEquals('POST', InputValidator::getMethod());
    }

    public function testIsPostReturnsTrue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertTrue(InputValidator::isPost());
    }

    public function testIsPostReturnsFalse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertFalse(InputValidator::isPost());
    }

    public function testIsGetReturnsTrue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertTrue(InputValidator::isGet());
    }

    // ===== getJson tests =====

    public function testGetJsonReturnsDecodedValue(): void
    {
        $_REQUEST['data'] = '{"key": "value", "number": 42}';

        $expected = ['key' => 'value', 'number' => 42];
        $this->assertEquals($expected, InputValidator::getJson('data'));
    }

    public function testGetJsonReturnsDefaultForInvalidJson(): void
    {
        $_REQUEST['data'] = 'not valid json';

        $this->assertNull(InputValidator::getJson('data'));
        $this->assertEquals(['default'], InputValidator::getJson('data', ['default']));
    }

    // ===== getMany tests =====

    public function testGetManyReturnsMultipleValues(): void
    {
        $_REQUEST['name'] = 'John';
        $_REQUEST['age'] = '30';
        $_REQUEST['active'] = 'true';
        $_REQUEST['score'] = '9.5';
        $_REQUEST['tags'] = ['php', 'mysql'];

        $schema = [
            'name' => '',
            'age' => 0,
            'active' => false,
            'score' => 0.0,
            'tags' => []
        ];

        $result = InputValidator::getMany($schema);

        $this->assertEquals('John', $result['name']);
        $this->assertEquals(30, $result['age']);
        $this->assertTrue($result['active']);
        $this->assertEquals(9.5, $result['score']);
        $this->assertEquals(['php', 'mysql'], $result['tags']);
    }

    public function testGetManyUsesDefaults(): void
    {
        $schema = [
            'name' => 'default',
            'count' => 10,
            'enabled' => true
        ];

        $result = InputValidator::getMany($schema);

        $this->assertEquals('default', $result['name']);
        $this->assertEquals(10, $result['count']);
        $this->assertTrue($result['enabled']);
    }
}
