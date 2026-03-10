<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\ImportUtilities;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * Unit tests for ImportUtilities.
 *
 * Tests pure-logic methods (delimiters, column parsing, temp files).
 * DB-dependent methods are skipped.
 */
class ImportUtilitiesTest extends TestCase
{
    private ImportUtilities $utilities;

    protected function setUp(): void
    {
        $this->utilities = new ImportUtilities();
    }

    // =========================================================================
    // BATCH_SIZE constant
    // =========================================================================

    #[Test]
    public function batchSizeConstantEquals500(): void
    {
        $this->assertSame(500, ImportUtilities::BATCH_SIZE);
    }

    // =========================================================================
    // getDelimiter
    // =========================================================================

    #[Test]
    public function getDelimiterReturnsCommaForC(): void
    {
        $this->assertSame(',', $this->utilities->getDelimiter('c'));
    }

    #[Test]
    public function getDelimiterReturnsHashForH(): void
    {
        $this->assertSame('#', $this->utilities->getDelimiter('h'));
    }

    #[Test]
    public function getDelimiterReturnsTabForT(): void
    {
        $this->assertSame("\t", $this->utilities->getDelimiter('t'));
    }

    #[Test]
    public function getDelimiterReturnsTabForDefault(): void
    {
        $this->assertSame("\t", $this->utilities->getDelimiter('z'));
    }

    #[Test]
    public function getDelimiterReturnsTabForEmptyString(): void
    {
        $this->assertSame("\t", $this->utilities->getDelimiter(''));
    }

    // =========================================================================
    // getSqlDelimiter
    // =========================================================================

    #[Test]
    public function getSqlDelimiterReturnsCommaForC(): void
    {
        $this->assertSame(',', $this->utilities->getSqlDelimiter('c'));
    }

    #[Test]
    public function getSqlDelimiterReturnsHashForH(): void
    {
        $this->assertSame('#', $this->utilities->getSqlDelimiter('h'));
    }

    #[Test]
    public function getSqlDelimiterReturnsEscapedTabForT(): void
    {
        $this->assertSame("\\t", $this->utilities->getSqlDelimiter('t'));
    }

    #[Test]
    public function getSqlDelimiterReturnsEscapedTabForDefault(): void
    {
        $this->assertSame("\\t", $this->utilities->getSqlDelimiter('x'));
    }

    // =========================================================================
    // parseColumnMapping
    // =========================================================================

    #[Test]
    public function parseColumnMappingWithWordOnly(): void
    {
        $result = $this->utilities->parseColumnMapping([1 => 'w'], false);

        $this->assertArrayHasKey('columns', $result);
        $this->assertArrayHasKey('fields', $result);
        $this->assertSame('WoText', $result['columns'][1]);
        $this->assertSame(1, $result['fields']['txt']);
    }

    #[Test]
    public function parseColumnMappingWithWordRemoveSpaces(): void
    {
        $result = $this->utilities->parseColumnMapping([1 => 'w'], true);

        $this->assertSame('@wotext', $result['columns'][1]);
        $this->assertSame(1, $result['fields']['txt']);
    }

    #[Test]
    public function parseColumnMappingWithTranslation(): void
    {
        $result = $this->utilities->parseColumnMapping([1 => 'w', 2 => 't'], false);

        $this->assertSame('WoText', $result['columns'][1]);
        $this->assertSame('WoTranslation', $result['columns'][2]);
        $this->assertSame(2, $result['fields']['tr']);
    }

    #[Test]
    public function parseColumnMappingWithRomanization(): void
    {
        $result = $this->utilities->parseColumnMapping([1 => 'w', 2 => 'r'], false);

        $this->assertSame('WoRomanization', $result['columns'][2]);
        $this->assertSame(2, $result['fields']['ro']);
    }

    #[Test]
    public function parseColumnMappingWithSentence(): void
    {
        $result = $this->utilities->parseColumnMapping([1 => 'w', 2 => 's'], false);

        $this->assertSame('WoSentence', $result['columns'][2]);
        $this->assertSame(2, $result['fields']['se']);
    }

    #[Test]
    public function parseColumnMappingWithTagList(): void
    {
        $result = $this->utilities->parseColumnMapping([1 => 'w', 2 => 'g'], false);

        $this->assertSame('@taglist', $result['columns'][2]);
        $this->assertSame(2, $result['fields']['tl']);
    }

    #[Test]
    public function parseColumnMappingWithSkippedColumnMid(): void
    {
        // Column 2 is 'x' (skip) but not at end, so it becomes @dummy
        $result = $this->utilities->parseColumnMapping([1 => 'w', 2 => 'x', 3 => 't'], false);

        $this->assertSame('@dummy', $result['columns'][2]);
        $this->assertSame('WoTranslation', $result['columns'][3]);
    }

    #[Test]
    public function parseColumnMappingWithSkippedColumnAtEnd(): void
    {
        // Column at max position with 'x' gets unset
        $result = $this->utilities->parseColumnMapping([1 => 'w', 2 => 'x'], false);

        $this->assertArrayNotHasKey(2, $result['columns']);
    }

    #[Test]
    public function parseColumnMappingWithGapFillsDummy(): void
    {
        // Column 2 is missing, should be filled as @dummy
        $result = $this->utilities->parseColumnMapping([1 => 'w', 3 => 't'], false);

        $this->assertSame('@dummy', $result['columns'][2]);
        $this->assertSame('WoTranslation', $result['columns'][3]);
    }

    #[Test]
    public function parseColumnMappingFieldsDefaultsToZero(): void
    {
        $result = $this->utilities->parseColumnMapping([1 => 'w'], false);

        $this->assertSame(0, $result['fields']['tr']);
        $this->assertSame(0, $result['fields']['ro']);
        $this->assertSame(0, $result['fields']['se']);
        $this->assertSame(0, $result['fields']['tl']);
    }

    #[Test]
    public function parseColumnMappingEmptyArray(): void
    {
        $result = $this->utilities->parseColumnMapping([], false);

        $this->assertEmpty($result['columns']);
        $this->assertSame(0, $result['fields']['txt']);
    }

    #[Test]
    public function parseColumnMappingDeduplicatesColumns(): void
    {
        // Two columns mapped to 'w' — array_unique keeps only first
        $result = $this->utilities->parseColumnMapping([1 => 'w', 2 => 'w'], false);

        // After array_unique, only key 1 remains
        $this->assertSame(1, $result['fields']['txt']);
    }

    #[Test]
    public function parseColumnMappingAllFields(): void
    {
        $result = $this->utilities->parseColumnMapping(
            [1 => 'w', 2 => 't', 3 => 'r', 4 => 's', 5 => 'g'],
            false
        );

        $this->assertSame(1, $result['fields']['txt']);
        $this->assertSame(2, $result['fields']['tr']);
        $this->assertSame(3, $result['fields']['ro']);
        $this->assertSame(4, $result['fields']['se']);
        $this->assertSame(5, $result['fields']['tl']);
    }

    // =========================================================================
    // createTempFile
    // =========================================================================

    #[Test]
    public function createTempFileReturnsFilePath(): void
    {
        $path = $this->utilities->createTempFile('test content');

        $this->assertFileExists($path);
        @unlink($path);
    }

    #[Test]
    public function createTempFileCreatesReadableFile(): void
    {
        $path = $this->utilities->createTempFile('hello world');

        $this->assertIsReadable($path);
        @unlink($path);
    }

    #[Test]
    public function createTempFilePrefixedWithLWT(): void
    {
        $path = $this->utilities->createTempFile('data');

        $basename = basename($path);
        $this->assertStringStartsWith('LWT', $basename);
        @unlink($path);
    }

    // =========================================================================
    // DB-dependent methods — skip
    // =========================================================================

    #[Test]
    public function getLanguageDataMethodExists(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'getLanguageData');
        $this->assertTrue($method->isPublic());
        $this->assertSame('int', $method->getParameters()[0]->getType()?->getName());
    }

    #[Test]
    public function isRightToLeftMethodExists(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'isRightToLeft');
        $this->assertTrue($method->isPublic());
        $this->assertSame('bool', $method->getReturnType()?->getName());
    }

    #[Test]
    public function isLocalInfileEnabledMethodExists(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'isLocalInfileEnabled');
        $this->assertTrue($method->isPublic());
        $this->assertSame('bool', $method->getReturnType()?->getName());
    }

    #[Test]
    public function getLastWordUpdateReturnType(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'getLastWordUpdate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->getReturnType()?->allowsNull());
    }

    #[Test]
    public function countImportedTermsSignature(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'countImportedTerms');
        $this->assertTrue($method->isPublic());
        $this->assertSame('int', $method->getReturnType()?->getName());
        $this->assertSame('string', $method->getParameters()[0]->getType()?->getName());
    }

    #[Test]
    public function getImportedTermsSignature(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'getImportedTerms');
        $this->assertTrue($method->isPublic());
        $this->assertCount(3, $method->getParameters());
        $this->assertSame('string', $method->getParameters()[0]->getType()?->getName());
        $this->assertSame('int', $method->getParameters()[1]->getType()?->getName());
        $this->assertSame('int', $method->getParameters()[2]->getType()?->getName());
    }

    #[Test]
    public function handleMultiwordsSignature(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'handleMultiwords');
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', $method->getReturnType()?->getName());
        $this->assertSame('int', $method->getParameters()[0]->getType()?->getName());
        $this->assertSame('string', $method->getParameters()[1]->getType()?->getName());
    }

    #[Test]
    public function linkWordsToTextItemsSignature(): void
    {
        $method = new ReflectionMethod(ImportUtilities::class, 'linkWordsToTextItems');
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', $method->getReturnType()?->getName());
        $this->assertCount(0, $method->getParameters());
    }
}
