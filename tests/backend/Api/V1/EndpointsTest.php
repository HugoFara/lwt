<?php

declare(strict_types=1);

namespace Lwt\Tests\Api\V1;

require_once __DIR__ . '/../../../../src/backend/Api/V1/Response.php';
require_once __DIR__ . '/../../../../src/backend/Api/V1/Endpoints.php';

use Lwt\Api\V1\Endpoints;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Endpoints class.
 *
 * Tests API endpoint resolution and parsing.
 */
class EndpointsTest extends TestCase
{
    /**
     * Test parseFragments splits endpoint correctly.
     */
    public function testParseFragmentsSimple(): void
    {
        $fragments = Endpoints::parseFragments('version');
        $this->assertEquals(['version'], $fragments);
    }

    /**
     * Test parseFragments with path segments.
     */
    public function testParseFragmentsWithPath(): void
    {
        $fragments = Endpoints::parseFragments('terms/123/translations');
        $this->assertEquals(['terms', '123', 'translations'], $fragments);
    }

    /**
     * Test parseFragments with nested path.
     */
    public function testParseFragmentsNestedPath(): void
    {
        $fragments = Endpoints::parseFragments('languages/1/reading-configuration');
        $this->assertEquals(['languages', '1', 'reading-configuration'], $fragments);
    }

    /**
     * Test parseFragments with review endpoints.
     */
    public function testParseFragmentsReview(): void
    {
        $fragments = Endpoints::parseFragments('review/next-word');
        $this->assertEquals(['review', 'next-word'], $fragments);
    }

    /**
     * Test parseFragments with settings endpoint.
     */
    public function testParseFragmentsSettings(): void
    {
        $fragments = Endpoints::parseFragments('settings/theme-path');
        $this->assertEquals(['settings', 'theme-path'], $fragments);
    }

    /**
     * Test parseFragments with terms status endpoint.
     */
    public function testParseFragmentsTermsStatus(): void
    {
        $fragments = Endpoints::parseFragments('terms/42/status/up');
        $this->assertEquals(['terms', '42', 'status', 'up'], $fragments);
    }

    /**
     * Test parseFragments with texts endpoint.
     */
    public function testParseFragmentsTexts(): void
    {
        $fragments = Endpoints::parseFragments('texts/1/annotation');
        $this->assertEquals(['texts', '1', 'annotation'], $fragments);
    }

    /**
     * Test parseFragments with feeds endpoint.
     */
    public function testParseFragmentsFeeds(): void
    {
        $fragments = Endpoints::parseFragments('feeds/5/load');
        $this->assertEquals(['feeds', '5', 'load'], $fragments);
    }

    /**
     * Test that Endpoints class has the required static methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(Endpoints::class);

        $this->assertTrue($reflection->hasMethod('resolve'));
        $this->assertTrue($reflection->hasMethod('parseFragments'));

        // Check they are static
        $this->assertTrue($reflection->getMethod('resolve')->isStatic());
        $this->assertTrue($reflection->getMethod('parseFragments')->isStatic());
    }
}
