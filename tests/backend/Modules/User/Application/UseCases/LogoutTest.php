<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Application\UseCases;

use Lwt\Modules\User\Application\UseCases\Logout;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Logout use case.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LogoutTest extends TestCase
{
    private Logout $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = new Logout();
    }

    protected function tearDown(): void
    {
        Globals::setCurrentUserId(null);
        parent::tearDown();
    }

    // =========================================================================
    // execute() - Clears User ID
    // =========================================================================

    #[Test]
    public function executeClearsCurrentUserId(): void
    {
        Globals::setCurrentUserId(42);
        $this->assertEquals(42, Globals::getCurrentUserId());

        $this->useCase->execute();

        $this->assertNull(Globals::getCurrentUserId());
    }

    #[Test]
    public function executeHandlesAlreadyNullUserId(): void
    {
        Globals::setCurrentUserId(null);

        // Should not throw when user ID is already null
        $this->useCase->execute();

        $this->assertNull(Globals::getCurrentUserId());
    }

    // =========================================================================
    // execute() - Session Destruction
    // =========================================================================

    #[Test]
    public function executeDestroysSession(): void
    {
        // Start a session and set some data
        session_start();
        $_SESSION['LWT_USER_ID'] = 5;
        $_SESSION['some_data'] = 'value';

        $this->useCase->execute();

        // Session should be destroyed - $_SESSION cleared
        $this->assertEmpty($_SESSION);
    }

    #[Test]
    public function executeStartsSessionIfNoneActive(): void
    {
        // Ensure no session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Should not throw - it starts a session internally before destroying it
        $this->useCase->execute();

        $this->assertNull(Globals::getCurrentUserId());
    }

    // =========================================================================
    // execute() - Return Type
    // =========================================================================

    #[Test]
    public function executeReturnsVoid(): void
    {
        Globals::setCurrentUserId(1);

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    // =========================================================================
    // execute() - Sequential Calls
    // =========================================================================

    #[Test]
    public function executeCanBeCalledMultipleTimes(): void
    {
        Globals::setCurrentUserId(10);

        $this->useCase->execute();
        $this->assertNull(Globals::getCurrentUserId());

        // Second call should not throw
        $this->useCase->execute();
        $this->assertNull(Globals::getCurrentUserId());
    }
}
