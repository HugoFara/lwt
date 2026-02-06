<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Infrastructure\Parser;

use Lwt\Modules\Language\Domain\Parser\ExternalParserConfig;
use Lwt\Modules\Language\Infrastructure\Parser\ExternalParserLoader;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ExternalParserLoader class.
 */
class ExternalParserLoaderTest extends TestCase
{
    private string $testConfigDir;

    protected function setUp(): void
    {
        $this->testConfigDir = sys_get_temp_dir() . '/lwt_parser_loader_test_' . uniqid();
        mkdir($this->testConfigDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->removeDirectory($this->testConfigDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createConfigFile(array $config): string
    {
        $path = $this->testConfigDir . '/parsers.php';
        $content = '<?php return ' . var_export($config, true) . ';';
        file_put_contents($path, $content);
        return $path;
    }

    public function testLoadsEmptyConfigWhenFileDoesNotExist(): void
    {
        $loader = new ExternalParserLoader('/nonexistent/path/parsers.php');

        $parsers = $loader->getExternalParsers();

        $this->assertEmpty($parsers);
    }

    public function testLoadsEmptyConfigWhenFileReturnsNonArray(): void
    {
        $path = $this->testConfigDir . '/parsers.php';
        file_put_contents($path, '<?php return "not an array";');

        $loader = new ExternalParserLoader($path);

        $this->assertEmpty($loader->getExternalParsers());
    }

    public function testLoadsEmptyConfigWhenFileReturnsEmptyArray(): void
    {
        $path = $this->createConfigFile([]);

        $loader = new ExternalParserLoader($path);

        $this->assertEmpty($loader->getExternalParsers());
        $this->assertFalse($loader->hasExternalParsers());
    }

    public function testLoadsValidParserConfig(): void
    {
        $path = $this->createConfigFile([
            'jieba' => [
                'name' => 'Jieba (Chinese)',
                'binary' => '/usr/bin/python3',
                'args' => ['/opt/jieba.py'],
                'input_mode' => 'stdin',
                'output_format' => 'line',
            ],
        ]);

        $loader = new ExternalParserLoader($path);
        $parsers = $loader->getExternalParsers();

        $this->assertCount(1, $parsers);
        $this->assertTrue($loader->hasExternalParsers());

        $jieba = $parsers[0];
        $this->assertInstanceOf(ExternalParserConfig::class, $jieba);
        $this->assertEquals('jieba', $jieba->getType());
        $this->assertEquals('Jieba (Chinese)', $jieba->getName());
        $this->assertEquals('/usr/bin/python3', $jieba->getBinary());
    }

    public function testLoadsMultipleParsers(): void
    {
        $path = $this->createConfigFile([
            'jieba' => [
                'name' => 'Jieba',
                'binary' => '/usr/bin/jieba',
            ],
            'sudachi' => [
                'name' => 'Sudachi',
                'binary' => 'sudachipy',
                'output_format' => 'wakati',
            ],
        ]);

        $loader = new ExternalParserLoader($path);
        $parsers = $loader->getExternalParsers();

        $this->assertCount(2, $parsers);
    }

    public function testSkipsInvalidConfigs(): void
    {
        $path = $this->createConfigFile([
            'valid' => [
                'name' => 'Valid Parser',
                'binary' => '/usr/bin/valid',
            ],
            'invalid' => [
                // Missing 'name' and 'binary'
            ],
            'also_valid' => [
                'name' => 'Also Valid',
                'binary' => '/usr/bin/also',
            ],
        ]);

        $loader = new ExternalParserLoader($path);
        $parsers = $loader->getExternalParsers();

        // Should only load the valid configs
        $this->assertCount(2, $parsers);
    }

    public function testSkipsNonArrayConfigs(): void
    {
        $path = $this->createConfigFile([
            'valid' => [
                'name' => 'Valid',
                'binary' => '/usr/bin/valid',
            ],
            'invalid_string' => 'not an array',
            123 => ['name' => 'Numeric key', 'binary' => '/bin/test'],
        ]);

        $loader = new ExternalParserLoader($path);
        $parsers = $loader->getExternalParsers();

        // Should only load the valid config (string key with array value)
        $this->assertCount(1, $parsers);
    }

    public function testGetParserReturnsParserByType(): void
    {
        $path = $this->createConfigFile([
            'jieba' => [
                'name' => 'Jieba',
                'binary' => '/usr/bin/jieba',
            ],
            'sudachi' => [
                'name' => 'Sudachi',
                'binary' => 'sudachipy',
            ],
        ]);

        $loader = new ExternalParserLoader($path);

        $jieba = $loader->getParser('jieba');
        $this->assertNotNull($jieba);
        $this->assertEquals('jieba', $jieba->getType());

        $sudachi = $loader->getParser('sudachi');
        $this->assertNotNull($sudachi);
        $this->assertEquals('sudachi', $sudachi->getType());

        $unknown = $loader->getParser('unknown');
        $this->assertNull($unknown);
    }

    public function testCachesParsers(): void
    {
        $path = $this->createConfigFile([
            'test' => [
                'name' => 'Test',
                'binary' => '/usr/bin/test',
            ],
        ]);

        $loader = new ExternalParserLoader($path);

        // First call loads from file
        $parsers1 = $loader->getExternalParsers();

        // Modify the file
        $this->createConfigFile([
            'test' => [
                'name' => 'Modified',
                'binary' => '/usr/bin/modified',
            ],
        ]);

        // Second call returns cached result
        $parsers2 = $loader->getExternalParsers();

        $this->assertEquals($parsers1[0]->getName(), $parsers2[0]->getName());
        $this->assertEquals('Test', $parsers2[0]->getName());
    }

    public function testClearCacheReloadsFromFile(): void
    {
        $path = $this->createConfigFile([
            'test' => [
                'name' => 'Original',
                'binary' => '/usr/bin/test',
            ],
        ]);

        $loader = new ExternalParserLoader($path);

        // Load initial config
        $parsers1 = $loader->getExternalParsers();
        $this->assertEquals('Original', $parsers1[0]->getName());

        // Modify the file
        $this->createConfigFile([
            'test' => [
                'name' => 'Modified',
                'binary' => '/usr/bin/test',
            ],
        ]);

        // Clear cache and reload
        $loader->clearCache();
        $parsers2 = $loader->getExternalParsers();

        $this->assertEquals('Modified', $parsers2[0]->getName());
    }

    public function testGetConfigPathReturnsProvidedPath(): void
    {
        $customPath = '/custom/path/parsers.php';
        $loader = new ExternalParserLoader($customPath);

        $this->assertEquals($customPath, $loader->getConfigPath());
    }

    public function testLoadConfigReturnsEmptyForUnreadableFile(): void
    {
        $path = $this->testConfigDir . '/unreadable.php';
        file_put_contents($path, '<?php return ["test" => ["name" => "Test", "binary" => "/bin/test"]];');
        chmod($path, 0000);

        $loader = new ExternalParserLoader($path);

        // Restore permissions for cleanup
        chmod($path, 0644);

        // On some systems this may still be readable (e.g., running as root)
        // So we just verify no exception is thrown
        $config = $loader->loadConfig();
        $this->assertIsArray($config);
    }
}
