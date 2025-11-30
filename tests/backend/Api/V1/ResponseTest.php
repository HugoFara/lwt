<?php declare(strict_types=1);
namespace Lwt\Tests\Api\V1;

require_once __DIR__ . '/../../../../src/backend/Api/V1/Response.php';

use Lwt\Api\V1\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Response class.
 *
 * Tests JSON response helper methods.
 * Note: Direct testing of Response::success/error is not possible since they call exit().
 * We test the class structure and method signatures instead.
 */
class ResponseTest extends TestCase
{
    /**
     * Test that Response class has the required static methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(Response::class);

        $this->assertTrue($reflection->hasMethod('send'));
        $this->assertTrue($reflection->hasMethod('success'));
        $this->assertTrue($reflection->hasMethod('error'));

        // Check they are static
        $this->assertTrue($reflection->getMethod('send')->isStatic());
        $this->assertTrue($reflection->getMethod('success')->isStatic());
        $this->assertTrue($reflection->getMethod('error')->isStatic());
    }

    /**
     * Test that success method accepts optional status parameter.
     */
    public function testSuccessMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(Response::class, 'success');
        $params = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertEquals('data', $params[0]->getName());
        $this->assertEquals('status', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertEquals(200, $params[1]->getDefaultValue());
    }

    /**
     * Test that error method has correct default status.
     */
    public function testErrorMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(Response::class, 'error');
        $params = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertEquals('message', $params[0]->getName());
        $this->assertEquals('status', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertEquals(400, $params[1]->getDefaultValue());
    }
}
