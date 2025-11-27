<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Text;

require_once __DIR__ . '/../../../../src/backend/Core/settings.php';
require_once __DIR__ . '/../../../../src/backend/Core/Text/annotation_management.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for annotation_management.php functions
 */
final class AnnotationManagementTest extends TestCase
{
    /**
     * Test annotation to JSON conversion
     */
    public function testAnnotationToJson(): void
    {
        // Empty annotation
        $this->assertEquals('{}', annotation_to_json(''));

        // Single annotation
        $annotation = "1\tword\t5\ttranslation";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey(0, $decoded);
        $this->assertEquals(['word', '5', 'translation'], $decoded[0]);

        // Multiple annotations
        $annotation = "1\tword1\t5\ttrans1\n2\tword2\t3\ttrans2";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertCount(2, $decoded);
        $this->assertEquals(['word1', '5', 'trans1'], $decoded[0]);
        $this->assertEquals(['word2', '3', 'trans2'], $decoded[1]);
    }

    /**
     * Test annotation_to_json with edge cases
     */
    public function testAnnotationToJsonEdgeCases(): void
    {
        // Annotation with special characters
        $annotation = "1\tword's\t5\t\"translation\"";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);

        // Annotation with tabs in translation
        $annotation = "1\tword\t5\ttranslation\twith\ttabs";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);

        // Malformed annotation (missing fields)
        $annotation = "1\tword";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);

        // Unicode in annotations
        $annotation = "1\t日本語\t5\ttranslation";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertStringContainsString('日本語', $decoded[0][0]);
    }
}
