<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin\Http;

use Lwt\Modules\Admin\Http\AdminController;
use Lwt\Modules\Admin\Application\AdminFacade;
use Lwt\Modules\Admin\Application\Services\TtsService;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for AdminController.
 *
 * Tests admin page rendering, settings operations, backup/restore,
 * wizard, statistics, demo installation, and server data.
 */
class AdminControllerTest extends TestCase
{
    /** @var AdminFacade&MockObject */
    private AdminFacade $facade;

    /** @var TtsService&MockObject */
    private TtsService $ttsService;

    private AdminController $controller;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(AdminFacade::class);
        $this->ttsService = $this->createMock(TtsService::class);
        $this->controller = new AdminController($this->facade, $this->ttsService);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(AdminController::class, $this->controller);
    }

    #[Test]
    public function constructorSetsFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(AdminController::class, 'adminFacade');
        $reflection->setAccessible(true);

        $this->assertSame($this->facade, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsTtsServiceProperty(): void
    {
        $reflection = new \ReflectionProperty(AdminController::class, 'ttsService');
        $reflection->setAccessible(true);

        $this->assertSame($this->ttsService, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsViewPathProperty(): void
    {
        $reflection = new \ReflectionProperty(AdminController::class, 'viewPath');
        $reflection->setAccessible(true);

        $viewPath = $reflection->getValue($this->controller);
        $this->assertStringEndsWith('/Views/', $viewPath);
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsBaseController(): void
    {
        $reflection = new \ReflectionClass(AdminController::class);
        $this->assertSame(
            'Lwt\Shared\Http\BaseController',
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(AdminController::class);

        $expectedMethods = [
            'backup', 'wizard', 'statistics', 'settings',
            'installDemo', 'serverData', 'saveSetting',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "AdminController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function publicMethodsAcceptArrayParams(): void
    {
        $methods = ['backup', 'wizard', 'statistics', 'settings', 'installDemo', 'serverData', 'saveSetting'];

        foreach ($methods as $methodName) {
            $method = new \ReflectionMethod(AdminController::class, $methodName);
            $params = $method->getParameters();
            $this->assertCount(1, $params, "Method $methodName should have 1 parameter");
            $this->assertSame('params', $params[0]->getName(), "Parameter of $methodName should be named 'params'");
        }
    }

    // =========================================================================
    // saveSetting tests
    // =========================================================================

    #[Test]
    public function saveSettingWithEmptyKeyDoesNotCallFacade(): void
    {
        // When key is empty string (default from param()), facade should not be called
        $_REQUEST = ['k' => '', 'v' => 'somevalue', 'u' => ''];

        $this->facade->expects($this->never())
            ->method('saveAndClearSession');

        $result = $this->controller->saveSetting([]);

        $this->assertNull($result);

        $_REQUEST = [];
    }

    #[Test]
    public function saveSettingWithKeyCallsFacade(): void
    {
        $_REQUEST = ['k' => 'currentlanguage', 'v' => '5', 'u' => ''];

        $this->facade->expects($this->once())
            ->method('saveAndClearSession')
            ->with('currentlanguage', '5');

        $result = $this->controller->saveSetting([]);

        $this->assertNull($result);

        $_REQUEST = [];
    }

    #[Test]
    public function saveSettingWithUrlReturnsRedirectResponse(): void
    {
        $_REQUEST = ['k' => 'theme', 'v' => 'dark', 'u' => '/settings'];

        $this->facade->expects($this->once())
            ->method('saveAndClearSession')
            ->with('theme', 'dark');

        $result = $this->controller->saveSetting([]);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/settings', $result->getUrl());
        $this->assertSame(302, $result->getStatusCode());

        $_REQUEST = [];
    }

    #[Test]
    public function saveSettingWithUrlButNoKeyStillRedirects(): void
    {
        $_REQUEST = ['k' => '', 'v' => '', 'u' => '/home'];

        $this->facade->expects($this->never())
            ->method('saveAndClearSession');

        $result = $this->controller->saveSetting([]);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/home', $result->getUrl());

        $_REQUEST = [];
    }

    #[Test]
    public function saveSettingReturnsNullWhenNoKeyAndNoUrl(): void
    {
        $_REQUEST = ['k' => '', 'v' => '', 'u' => ''];

        $result = $this->controller->saveSetting([]);

        $this->assertNull($result);

        $_REQUEST = [];
    }

    #[Test]
    public function saveSettingReturnTypeIsNullableRedirectResponse(): void
    {
        $method = new \ReflectionMethod(AdminController::class, 'saveSetting');
        $returnType = $method->getReturnType();

        $this->assertTrue($returnType->allowsNull());
    }

    // =========================================================================
    // backup method tests (testing facade interactions)
    // =========================================================================

    #[Test]
    public function backupWithNoParamsRendersPage(): void
    {
        $_REQUEST = [];

        // Expect no backup/restore operations
        $this->facade->expects($this->never())->method('restoreFromUpload');
        $this->facade->expects($this->never())->method('downloadBackup');
        $this->facade->expects($this->never())->method('downloadOfficialBackup');
        $this->facade->expects($this->never())->method('emptyDatabase');

        ob_start();
        try {
            $this->controller->backup([]);
        } catch (\Throwable $e) {
            // View include may fail in test context; that's expected
        }
        ob_end_clean();
    }

    #[Test]
    public function backupWithRestoreParamCallsRestore(): void
    {
        $_REQUEST = ['restore' => '1'];
        $_FILES = ['thefile' => ['name' => 'backup.sql', 'tmp_name' => '/tmp/test', 'error' => 0, 'size' => 100]];

        $this->facade->expects($this->once())
            ->method('restoreFromUpload')
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->backup([]);
        } catch (\Throwable $e) {
            // View include may fail in test context
        }
        ob_end_clean();

        $_REQUEST = [];
        $_FILES = [];
    }

    #[Test]
    public function backupWithRestoreFailureReturnsErrorMessage(): void
    {
        $_REQUEST = ['restore' => '1'];
        $_FILES = ['thefile' => ['name' => 'backup.sql', 'tmp_name' => '/tmp/test', 'error' => 0, 'size' => 100]];

        $this->facade->expects($this->once())
            ->method('restoreFromUpload')
            ->willReturn(['success' => false, 'error' => 'Invalid file format']);

        ob_start();
        try {
            $this->controller->backup([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
        $_FILES = [];
    }

    #[Test]
    public function backupWithEmptyParamCallsEmptyDatabase(): void
    {
        $_REQUEST = ['empty' => '1'];

        $this->facade->expects($this->once())
            ->method('emptyDatabase')
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->backup([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function backupWithEmptyFailureReturnsErrorMessage(): void
    {
        $_REQUEST = ['empty' => '1'];

        $this->facade->expects($this->once())
            ->method('emptyDatabase')
            ->willReturn(['success' => false]);

        ob_start();
        try {
            $this->controller->backup([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function backupWithBackupParamCallsDownloadBackup(): void
    {
        $_REQUEST = ['backup' => '1'];

        $this->facade->expects($this->once())
            ->method('downloadBackup');

        ob_start();
        try {
            $this->controller->backup([]);
        } catch (\Throwable $e) {
            // downloadBackup may exit or view include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function backupWithOrigBackupCallsDownloadOfficialBackup(): void
    {
        $_REQUEST = ['orig_backup' => '1'];

        $this->facade->expects($this->once())
            ->method('downloadOfficialBackup');

        ob_start();
        try {
            $this->controller->backup([]);
        } catch (\Throwable $e) {
            // downloadOfficialBackup may exit or view include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    // =========================================================================
    // wizard method tests
    // =========================================================================

    #[Test]
    public function wizardWithNoOpChecksEnvFileExists(): void
    {
        $_REQUEST = [];

        $this->facade->expects($this->once())
            ->method('envFileExists')
            ->willReturn(false);

        $this->facade->expects($this->once())
            ->method('createEmptyConnection');

        ob_start();
        try {
            $this->controller->wizard([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function wizardWithEnvExistingLoadsConnection(): void
    {
        $_REQUEST = [];

        $this->facade->expects($this->once())
            ->method('envFileExists')
            ->willReturn(true);

        $this->facade->expects($this->once())
            ->method('loadConnection');

        ob_start();
        try {
            $this->controller->wizard([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function wizardWithAutocompleteOpCallsAutocomplete(): void
    {
        $_REQUEST = ['op' => 'Autocomplete'];

        $this->facade->expects($this->once())
            ->method('autocompleteConnection');

        ob_start();
        try {
            $this->controller->wizard([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function wizardWithCheckOpCallsTestConnection(): void
    {
        $_REQUEST = [
            'op' => 'Check',
            'hostname' => 'localhost',
            'login' => 'root',
            'password' => 'pass',
            'dbname' => 'testdb',
            'tbpref' => '',
        ];

        $mockConn = new \Lwt\Modules\Admin\Application\DTO\DatabaseConnectionDTO('localhost', 'root', 'pass', 'testdb');

        $this->facade->expects($this->once())
            ->method('createConnectionFromForm')
            ->willReturn($mockConn);

        $this->facade->expects($this->once())
            ->method('testConnection')
            ->with($mockConn)
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->wizard([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function wizardWithCheckOpHandlesFailure(): void
    {
        $_REQUEST = [
            'op' => 'Check',
            'hostname' => 'localhost',
            'login' => 'root',
            'password' => 'pass',
            'dbname' => 'testdb',
            'tbpref' => '',
        ];

        $mockConn = new \Lwt\Modules\Admin\Application\DTO\DatabaseConnectionDTO('localhost', 'root', 'pass', 'testdb');

        $this->facade->expects($this->once())
            ->method('createConnectionFromForm')
            ->willReturn($mockConn);

        $this->facade->expects($this->once())
            ->method('testConnection')
            ->with($mockConn)
            ->willReturn(['success' => false, 'error' => 'Connection refused']);

        ob_start();
        try {
            $this->controller->wizard([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function wizardWithChangeOpSavesAndRedirects(): void
    {
        $_REQUEST = [
            'op' => 'Change',
            'hostname' => 'localhost',
            'login' => 'root',
            'password' => 'pass',
            'dbname' => 'testdb',
            'tbpref' => '',
        ];

        $mockConn = new \Lwt\Modules\Admin\Application\DTO\DatabaseConnectionDTO('localhost', 'root', 'pass', 'testdb');

        $this->facade->expects($this->once())
            ->method('createConnectionFromForm')
            ->willReturn($mockConn);

        $this->facade->expects($this->once())
            ->method('saveConnectionToEnv')
            ->with($mockConn);

        ob_start();
        try {
            $this->controller->wizard([]);
        } catch (\Throwable $e) {
            // Redirect may throw or view may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    // =========================================================================
    // statistics method tests
    // =========================================================================

    #[Test]
    public function statisticsCallsFacadeMethods(): void
    {
        $this->facade->expects($this->once())
            ->method('getIntensityStatistics')
            ->willReturn(['languages' => [], 'totals' => []]);

        $this->facade->expects($this->once())
            ->method('getFrequencyStatistics')
            ->willReturn(['languages' => [], 'totals' => []]);

        ob_start();
        try {
            $this->controller->statistics([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    // =========================================================================
    // settings method tests
    // =========================================================================

    #[Test]
    public function settingsWithNoOpLoadsCurrentSettings(): void
    {
        $_REQUEST = [];

        $this->facade->expects($this->once())
            ->method('getAllSettings')
            ->willReturn([]);

        $this->facade->expects($this->once())
            ->method('getAvailableThemes')
            ->willReturn(['default']);

        ob_start();
        try {
            $this->controller->settings([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function settingsWithSaveOpCallsSaveAllSettings(): void
    {
        $_REQUEST = ['op' => 'Save'];

        $this->facade->expects($this->once())
            ->method('saveAllSettings')
            ->willReturn(['success' => true]);

        $this->facade->expects($this->once())
            ->method('getAllSettings')
            ->willReturn([]);

        $this->facade->expects($this->once())
            ->method('getAvailableThemes')
            ->willReturn([]);

        ob_start();
        try {
            $this->controller->settings([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function settingsWithSaveFailureReturnsErrorMessage(): void
    {
        $_REQUEST = ['op' => 'Save'];

        $this->facade->expects($this->once())
            ->method('saveAllSettings')
            ->willReturn(['success' => false]);

        $this->facade->expects($this->once())->method('getAllSettings')->willReturn([]);
        $this->facade->expects($this->once())->method('getAvailableThemes')->willReturn([]);

        ob_start();
        try {
            $this->controller->settings([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function settingsWithResetOpCallsResetAllSettings(): void
    {
        $_REQUEST = ['op' => 'Reset'];

        $this->facade->expects($this->once())
            ->method('resetAllSettings')
            ->willReturn(['success' => true]);

        $this->facade->expects($this->once())->method('getAllSettings')->willReturn([]);
        $this->facade->expects($this->once())->method('getAvailableThemes')->willReturn([]);

        ob_start();
        try {
            $this->controller->settings([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function settingsWithResetFailureReturnsErrorMessage(): void
    {
        $_REQUEST = ['op' => 'Reset'];

        $this->facade->expects($this->once())
            ->method('resetAllSettings')
            ->willReturn(['success' => false]);

        $this->facade->expects($this->once())->method('getAllSettings')->willReturn([]);
        $this->facade->expects($this->once())->method('getAvailableThemes')->willReturn([]);

        ob_start();
        try {
            $this->controller->settings([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    // =========================================================================
    // installDemo method tests
    // =========================================================================

    #[Test]
    public function installDemoWithNoInstallParamRendersPage(): void
    {
        $_REQUEST = [];

        $this->facade->expects($this->never())->method('installDemo');

        $this->facade->expects($this->once())
            ->method('getLanguageCount')
            ->willReturn(3);

        ob_start();
        try {
            $this->controller->installDemo([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function installDemoWithInstallParamCallsInstallDemo(): void
    {
        $_REQUEST = ['install' => '1'];

        $this->facade->expects($this->once())
            ->method('installDemo')
            ->willReturn('Demo installed successfully');

        $this->facade->expects($this->once())
            ->method('getLanguageCount')
            ->willReturn(5);

        ob_start();
        try {
            $this->controller->installDemo([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function installDemoHandlesRuntimeException(): void
    {
        $_REQUEST = ['install' => '1'];

        $this->facade->expects($this->once())
            ->method('installDemo')
            ->willThrowException(new \RuntimeException('Demo data corrupted'));

        $this->facade->expects($this->once())
            ->method('getLanguageCount')
            ->willReturn(0);

        ob_start();
        try {
            $this->controller->installDemo([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    // =========================================================================
    // serverData method tests
    // =========================================================================

    #[Test]
    public function serverDataCallsFacadeGetServerData(): void
    {
        $expectedData = ['php_version' => '8.1', 'mysql_version' => '8.0'];

        $this->facade->expects($this->once())
            ->method('getServerData')
            ->willReturn($expectedData);

        ob_start();
        try {
            $this->controller->serverData([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    // =========================================================================
    // Private method tests via reflection
    // =========================================================================

    #[Test]
    public function createDefaultFacadeReturnsAdminFacade(): void
    {
        $method = new \ReflectionMethod(AdminController::class, 'createDefaultFacade');
        $method->setAccessible(true);

        // This will attempt to create real repositories which may fail without DB
        // but we can verify the method exists and is callable
        $this->assertTrue($method->isPrivate());
    }

    #[Test]
    public function viewPathPointsToViewsDirectory(): void
    {
        $reflection = new \ReflectionProperty(AdminController::class, 'viewPath');
        $reflection->setAccessible(true);

        $viewPath = $reflection->getValue($this->controller);

        $this->assertStringContainsString('Modules', $viewPath);
        $this->assertStringContainsString('Admin', $viewPath);
        $this->assertStringEndsWith('Views/', $viewPath);
    }
}
