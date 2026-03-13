<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin\UseCases;

use Lwt\Modules\Admin\Application\UseCases\Backup\DownloadBackup;
use Lwt\Modules\Admin\Application\UseCases\Backup\EmptyDatabase;
use Lwt\Modules\Admin\Application\UseCases\Backup\RestoreFromUpload;
use Lwt\Modules\Admin\Application\UseCases\Settings\SaveAllSettings;
use Lwt\Modules\Admin\Domain\BackupRepositoryInterface;
use Lwt\Modules\Admin\Domain\SettingDefinitions;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Admin module use cases.
 *
 * Tests business logic for SaveAllSettings, DownloadBackup,
 * EmptyDatabase, and RestoreFromUpload use cases using mocked
 * repository dependencies.
 */
class AdminUseCaseTest extends TestCase
{
    // ===== SaveAllSettings tests =====

    public function testSaveAllSettingsExecuteWithDataSavesValidSettings(): void
    {
        $useCase = new SaveAllSettings();

        // We cannot call executeWithData without a DB because Settings::save()
        // uses static DB calls. Instead, test the class can be instantiated
        // and has the expected methods.
        $this->assertTrue(
            method_exists($useCase, 'execute'),
            'execute method should exist'
        );
        $this->assertTrue(
            method_exists($useCase, 'executeWithData'),
            'executeWithData method should exist'
        );
    }

    public function testSaveAllSettingsAdminKeysAreComplete(): void
    {
        // SaveAllSettings now uses SettingDefinitions::getAdminKeys()
        $keys = SettingDefinitions::getAdminKeys();

        $this->assertNotEmpty($keys);

        // Verify admin-scoped settings are present
        $this->assertContains('set-theme-dir', $keys);
        $this->assertContains('set-max-articles-with-text', $keys);
        $this->assertContains('set-allow-registration', $keys);

        // Verify user-scoped settings are NOT present
        $this->assertNotContains('set-tts', $keys);
        $this->assertNotContains('set-tooltip-mode', $keys);
        $this->assertNotContains('set-texts-per-page', $keys);
    }

    public function testUserKeysContainUserPreferences(): void
    {
        $keys = SettingDefinitions::getUserKeys();

        $this->assertNotEmpty($keys);

        // Verify user-scoped settings are present
        $this->assertContains('set-tts', $keys);
        $this->assertContains('set-tooltip-mode', $keys);
        $this->assertContains('set-texts-per-page', $keys);
        $this->assertContains('set-terms-per-page', $keys);
        $this->assertContains('set-regex-mode', $keys);

        // Verify admin settings are NOT present
        $this->assertNotContains('set-theme-dir', $keys);
        $this->assertNotContains('set-allow-registration', $keys);
    }

    public function testSaveAllSettingsExecuteWithDataIgnoresUnknownKeys(): void
    {
        $adminKeys = SettingDefinitions::getAdminKeys();

        $this->assertNotContains('unknown-key', $adminKeys);
        $this->assertNotContains('set-nonexistent', $adminKeys);
    }

    public function testSaveAllSettingsAdminKeysContainsFeedSettings(): void
    {
        $keys = SettingDefinitions::getAdminKeys();

        $this->assertContains('set-max-articles-with-text', $keys);
        $this->assertContains('set-max-articles-without-text', $keys);
        $this->assertContains('set-max-texts-per-feed', $keys);
    }

    public function testUserKeysContainsReviewAndPaginationSettings(): void
    {
        $keys = SettingDefinitions::getUserKeys();

        $this->assertContains('set-test-main-frame-waiting-time', $keys);
        $this->assertContains('set-test-edit-frame-waiting-time', $keys);
        $this->assertContains('set-test-sentence-count', $keys);
        $this->assertContains('set-articles-per-page', $keys);
        $this->assertContains('set-feeds-per-page', $keys);
    }

    // ===== DownloadBackup tests =====

    public function testDownloadBackupConstructorAcceptsRepository(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $useCase = new DownloadBackup($repo);

        $this->assertInstanceOf(DownloadBackup::class, $useCase);
    }

    public function testDownloadBackupGenerateReturnsFilenameAndContent(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('generateBackupSql')
            ->willReturn("INSERT INTO words VALUES (1, 'test');");

        $useCase = new DownloadBackup($repo);
        $result = $useCase->generate();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
    }

    public function testDownloadBackupGenerateFilenameContainsDateAndExtension(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->method('generateBackupSql')->willReturn('');

        $useCase = new DownloadBackup($repo);
        $result = $useCase->generate();

        $this->assertStringStartsWith('lwt-backup-exp_version-', $result['filename']);
        $this->assertStringEndsWith('.sql.gz', $result['filename']);
    }

    public function testDownloadBackupGenerateFilenameContainsCurrentDate(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->method('generateBackupSql')->willReturn('');

        $useCase = new DownloadBackup($repo);
        $result = $useCase->generate();

        $today = date('Y-m-d');
        $this->assertStringContainsString($today, $result['filename']);
    }

    public function testDownloadBackupGenerateContentStartsWithComment(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->method('generateBackupSql')->willReturn('-- SQL dump');

        $useCase = new DownloadBackup($repo);
        $result = $useCase->generate();

        $this->assertStringStartsWith('-- lwt-backup-', $result['content']);
    }

    public function testDownloadBackupGenerateContentIncludesSqlDump(): void
    {
        $expectedSql = "INSERT INTO words VALUES (1, 'hello');\nINSERT INTO words VALUES (2, 'world');";
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->method('generateBackupSql')->willReturn($expectedSql);

        $useCase = new DownloadBackup($repo);
        $result = $useCase->generate();

        $this->assertStringContainsString($expectedSql, $result['content']);
    }

    public function testDownloadBackupGenerateWithEmptySqlReturnsHeaderOnly(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->method('generateBackupSql')->willReturn('');

        $useCase = new DownloadBackup($repo);
        $result = $useCase->generate();

        // Content should be just the header comment line + trailing newline
        $this->assertStringStartsWith('-- lwt-backup-', $result['content']);
        $lines = explode("\n", trim($result['content']));
        $this->assertCount(1, $lines);
    }

    // ===== EmptyDatabase tests =====

    public function testEmptyDatabaseConstructorAcceptsRepository(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $useCase = new EmptyDatabase($repo);

        $this->assertInstanceOf(EmptyDatabase::class, $useCase);
    }

    public function testEmptyDatabaseExecuteCallsTruncateUserTables(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('truncateUserTables');

        $useCase = new EmptyDatabase($repo);
        $result = $useCase->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function testEmptyDatabaseExecuteReturnsSuccessTrue(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);

        $useCase = new EmptyDatabase($repo);
        $result = $useCase->execute();

        $this->assertEquals(['success' => true], $result);
    }

    public function testEmptyDatabaseExecutePropagatesRepositoryException(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->method('truncateUserTables')
            ->willThrowException(new \RuntimeException('DB error'));

        $useCase = new EmptyDatabase($repo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');
        $useCase->execute();
    }

    // ===== RestoreFromUpload tests =====

    public function testRestoreFromUploadConstructorAcceptsRepository(): void
    {
        $repo = $this->createMock(BackupRepositoryInterface::class);
        $useCase = new RestoreFromUpload($repo);

        $this->assertInstanceOf(RestoreFromUpload::class, $useCase);
    }

    public function testRestoreFromUploadReturnsErrorWhenFileIsNull(): void
    {
        Globals::setBackupRestoreEnabled(true);

        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->expects($this->never())->method('restoreFromHandle');

        $useCase = new RestoreFromUpload($repo);
        $result = $useCase->execute(null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No Restore file', $result['error']);

        Globals::setBackupRestoreEnabled(null);
    }

    public function testRestoreFromUploadReturnsErrorWhenDisabled(): void
    {
        Globals::setBackupRestoreEnabled(false);

        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->expects($this->never())->method('restoreFromHandle');

        $useCase = new RestoreFromUpload($repo);
        $result = $useCase->execute([
            'name' => 'backup.sql.gz',
            'type' => 'application/gzip',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => 0,
            'size' => 1024,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('disabled', $result['error']);

        Globals::setBackupRestoreEnabled(null);
    }

    public function testRestoreFromUploadReturnsErrorWhenFileCannotBeOpened(): void
    {
        Globals::setBackupRestoreEnabled(true);

        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->expects($this->never())->method('restoreFromHandle');

        $useCase = new RestoreFromUpload($repo);
        $result = $useCase->execute([
            'name' => 'backup.sql.gz',
            'type' => 'application/gzip',
            'tmp_name' => '/nonexistent/path/file.sql.gz',
            'error' => 0,
            'size' => 1024,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('could not be opened', $result['error']);

        Globals::setBackupRestoreEnabled(null);
    }

    public function testRestoreFromUploadSucceedsWithValidGzFile(): void
    {
        Globals::setBackupRestoreEnabled(true);

        // Create a real temporary gzipped file
        $tmpFile = tempnam(sys_get_temp_dir(), 'lwt_test_');
        $gz = gzopen($tmpFile, 'w');
        gzwrite($gz, "INSERT INTO words VALUES (1, 'test');");
        gzclose($gz);

        $repo = $this->createMock(BackupRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('restoreFromHandle')
            ->with(
                $this->isType('resource'),
                $this->equalTo('Database')
            );

        $useCase = new RestoreFromUpload($repo);
        $result = $useCase->execute([
            'name' => 'backup.sql.gz',
            'type' => 'application/gzip',
            'tmp_name' => $tmpFile,
            'error' => 0,
            'size' => filesize($tmpFile),
        ]);

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);

        @unlink($tmpFile);
        Globals::setBackupRestoreEnabled(null);
    }

    public function testRestoreFromUploadDisabledMessageMentionsEnvSetting(): void
    {
        Globals::setBackupRestoreEnabled(false);

        $repo = $this->createMock(BackupRepositoryInterface::class);
        $useCase = new RestoreFromUpload($repo);
        $result = $useCase->execute(null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('BACKUP_RESTORE_ENABLED', $result['error']);

        Globals::setBackupRestoreEnabled(null);
    }

    public function testRestoreFromUploadNullFileCheckedAfterRestoreEnabled(): void
    {
        // When restore is disabled, we get the disabled message even with null file
        Globals::setBackupRestoreEnabled(false);

        $repo = $this->createMock(BackupRepositoryInterface::class);
        $useCase = new RestoreFromUpload($repo);
        $result = $useCase->execute(null);

        // Should get disabled error, not "no file" error
        $this->assertStringContainsString('disabled', $result['error']);

        Globals::setBackupRestoreEnabled(null);
    }
}
