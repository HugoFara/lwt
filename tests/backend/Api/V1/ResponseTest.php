<?php declare(strict_types=1);
namespace Lwt\Tests\Api\V1;

require_once __DIR__ . '/../../../../src/backend/Api/V1/Response.php';

use Lwt\Api\V1\Response;
use Lwt\Shared\Infrastructure\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Response class.
 *
 * Tests JSON response helper methods that return JsonResponse objects.
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
        $this->assertTrue($reflection->hasMethod('notFound'));
        $this->assertTrue($reflection->hasMethod('created'));

        // Check they are static
        $this->assertTrue($reflection->getMethod('send')->isStatic());
        $this->assertTrue($reflection->getMethod('success')->isStatic());
        $this->assertTrue($reflection->getMethod('error')->isStatic());
        $this->assertTrue($reflection->getMethod('notFound')->isStatic());
        $this->assertTrue($reflection->getMethod('created')->isStatic());
    }

    /**
     * Test success returns JsonResponse with correct status.
     */
    public function testSuccessReturnsJsonResponse(): void
    {
        $data = ['key' => 'value'];
        $response = Response::success($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($data, $response->getData());
    }

    /**
     * Test success with custom status code.
     */
    public function testSuccessWithCustomStatus(): void
    {
        $data = ['key' => 'value'];
        $response = Response::success($data, 201);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Test error returns JsonResponse with error format.
     */
    public function testErrorReturnsJsonResponse(): void
    {
        $response = Response::error('Something went wrong');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(['error' => 'Something went wrong'], $response->getData());
    }

    /**
     * Test error with custom status code.
     */
    public function testErrorWithCustomStatus(): void
    {
        $response = Response::error('Not allowed', 403);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test notFound returns 404 error response.
     */
    public function testNotFoundReturnsJsonResponse(): void
    {
        $response = Response::notFound();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['error' => 'Not found'], $response->getData());
    }

    /**
     * Test notFound with custom message.
     */
    public function testNotFoundWithCustomMessage(): void
    {
        $response = Response::notFound('Resource not found');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['error' => 'Resource not found'], $response->getData());
    }

    /**
     * Test created returns 201 response.
     */
    public function testCreatedReturnsJsonResponse(): void
    {
        $data = ['id' => 1, 'name' => 'New item'];
        $response = Response::created($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($data, $response->getData());
    }

    /**
     * Test send returns JsonResponse with given status and data.
     */
    public function testSendReturnsJsonResponse(): void
    {
        $data = ['custom' => 'data'];
        $response = Response::send(202, $data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals($data, $response->getData());
    }
}
