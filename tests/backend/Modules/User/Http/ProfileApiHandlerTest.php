<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Http;

use Lwt\Modules\User\Application\UserFacade;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Http\ProfileApiHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The profile and preferences endpoints.
 *
 * These carry the behaviour that used to live in
 * `UserController::updateProfile()` and `changePassword()` (issue #262), so
 * the validation rules those tests covered are asserted here instead.
 *
 * Nothing in this handler takes an account ID: every route resolves the user
 * from the session, so there is no object-reference surface. What is worth
 * pinning is that the writes refuse to run when no account is signed in, and
 * that a password change still demands the current password.
 */
#[CoversClass(ProfileApiHandler::class)]
class ProfileApiHandlerTest extends TestCase
{
    /** @var UserFacade&MockObject */
    private UserFacade $userFacade;

    /** @var User&MockObject */
    private User $user;

    private ProfileApiHandler $handler;

    protected function setUp(): void
    {
        $this->userFacade = $this->createMock(UserFacade::class);
        $this->user = $this->createMock(User::class);
        $this->handler = new ProfileApiHandler($this->userFacade);
    }

    /** Sign a user in for the duration of a test. */
    private function signIn(): void
    {
        $this->userFacade->method('getCurrentUser')->willReturn($this->user);
    }

    /** Leave the session empty, as in single-user mode. */
    private function signOut(): void
    {
        $this->userFacade->method('getCurrentUser')->willReturn(null);
    }

    // =========================================================================
    // Reading
    // =========================================================================

    #[Test]
    public function readingWithNoAccountReportsSo(): void
    {
        $this->signOut();

        $this->assertArrayHasKey('error', $this->handler->getProfile());
    }

    #[Test]
    public function readingReturnsTheSignedInUsersOwnFields(): void
    {
        $this->signIn();
        $this->user->method('username')->willReturn('alice');
        $this->user->method('email')->willReturn('alice@example.test');
        $this->user->method('emailVerifiedAt')->willReturn(new \DateTimeImmutable());

        $result = $this->handler->getProfile();

        $this->assertSame('alice', $result['profile']['username']);
        $this->assertSame('alice@example.test', $result['profile']['email']);
        $this->assertTrue($result['profile']['emailVerified']);
    }

    #[Test]
    public function readingNeverReturnsCredentials(): void
    {
        $this->signIn();
        $this->user->method('username')->willReturn('alice');
        $this->user->method('email')->willReturn('alice@example.test');
        $this->user->method('emailVerifiedAt')->willReturn(null);

        $profile = $this->handler->getProfile()['profile'];

        $this->assertSame(
            ['username', 'email', 'emailVerified'],
            array_keys($profile),
            'The profile payload should carry nothing beyond these three fields.'
        );
    }

    // =========================================================================
    // Updating the profile
    // =========================================================================

    #[Test]
    public function updatingWithNoAccountWritesNothing(): void
    {
        $this->signOut();
        $this->userFacade->expects($this->never())->method('updateProfile');

        $result = $this->handler->updateProfile(['username' => 'x', 'email' => 'x@y.z']);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function updatingRequiresBothFields(): void
    {
        $this->signIn();
        $this->userFacade->expects($this->never())->method('updateProfile');

        $this->assertFalse($this->handler->updateProfile(['username' => '', 'email' => 'a@b.c'])['success']);
    }

    #[Test]
    public function updatingRequiresAnEmail(): void
    {
        $this->signIn();
        $this->userFacade->expects($this->never())->method('updateProfile');

        $this->assertFalse($this->handler->updateProfile(['username' => 'alice', 'email' => '  '])['success']);
    }

    #[Test]
    public function aChangedEmailTriggersVerification(): void
    {
        $this->signIn();
        $this->userFacade->method('updateProfile')->willReturn(true);
        $this->userFacade->expects($this->once())->method('sendVerificationEmail');

        $result = $this->handler->updateProfile(['username' => 'alice', 'email' => 'new@example.test']);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['emailChanged']);
    }

    #[Test]
    public function anUnchangedEmailDoesNotTriggerVerification(): void
    {
        $this->signIn();
        $this->userFacade->method('updateProfile')->willReturn(false);
        $this->userFacade->expects($this->never())->method('sendVerificationEmail');

        $result = $this->handler->updateProfile(['username' => 'alice', 'email' => 'same@example.test']);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['emailChanged']);
    }

    #[Test]
    public function aRejectedUpdateSurfacesTheReason(): void
    {
        $this->signIn();
        $this->userFacade->method('updateProfile')
            ->willThrowException(new \InvalidArgumentException('Username already taken'));

        $result = $this->handler->updateProfile(['username' => 'alice', 'email' => 'a@b.c']);

        $this->assertFalse($result['success']);
        $this->assertSame('Username already taken', $result['error']);
    }

    // =========================================================================
    // Changing the password
    // =========================================================================

    #[Test]
    public function changingAPasswordWithNoAccountWritesNothing(): void
    {
        $this->signOut();
        $this->userFacade->expects($this->never())->method('changePassword');

        $result = $this->handler->changePassword([
            'current_password' => 'a', 'new_password' => 'b', 'new_password_confirm' => 'b',
        ]);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function changingAPasswordRequiresEveryField(): void
    {
        $this->signIn();
        $this->userFacade->expects($this->never())->method('changePassword');

        $this->assertFalse($this->handler->changePassword([
            'current_password' => '', 'new_password' => 'b', 'new_password_confirm' => 'b',
        ])['success']);
    }

    #[Test]
    public function theConfirmationMustMatch(): void
    {
        $this->signIn();
        $this->userFacade->expects($this->never())->method('changePassword');

        $result = $this->handler->changePassword([
            'current_password' => 'old', 'new_password' => 'new', 'new_password_confirm' => 'typo',
        ]);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function theCurrentPasswordIsPassedThroughForVerification(): void
    {
        // Holding a session must not be enough to replace the password; the
        // facade checks the old one, so it has to receive it.
        $this->signIn();
        $this->userFacade->expects($this->once())
            ->method('changePassword')
            ->with($this->user, 'old-secret', 'new-secret');

        $result = $this->handler->changePassword([
            'current_password' => 'old-secret',
            'new_password' => 'new-secret',
            'new_password_confirm' => 'new-secret',
        ]);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function aWrongCurrentPasswordSurfacesTheReason(): void
    {
        $this->signIn();
        $this->userFacade->method('changePassword')
            ->willThrowException(new \InvalidArgumentException('Current password is incorrect'));

        $result = $this->handler->changePassword([
            'current_password' => 'wrong', 'new_password' => 'n', 'new_password_confirm' => 'n',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Current password is incorrect', $result['error']);
    }

    // =========================================================================
    // Preferences
    // =========================================================================

    #[Test]
    public function preferencesAreReturnedAsAMap(): void
    {
        $this->userFacade->method('getUserPreferences')
            ->willReturn(['set-texts-per-page' => '10']);

        $this->assertSame(
            ['set-texts-per-page' => '10'],
            $this->handler->getPreferences()['settings']
        );
    }

    #[Test]
    public function preferencesAcceptEitherAWrappedOrBareMap(): void
    {
        // The client sends {settings: {...}}; accepting a bare map too keeps
        // the endpoint usable from a plain PUT body.
        $this->userFacade->expects($this->exactly(2))
            ->method('saveUserPreferencesFromData')
            ->with(['set-texts-per-page' => '25'])
            ->willReturn(['success' => true]);

        $this->handler->savePreferences(['settings' => ['set-texts-per-page' => '25']]);
        $this->handler->savePreferences(['set-texts-per-page' => '25']);
    }

    // =========================================================================
    // Routing
    // =========================================================================

    #[Test]
    public function anUnknownSubPathIs404(): void
    {
        $this->assertSame(404, $this->handler->routeGet(['profile', 'nonsense'], [])->getStatusCode());
        $this->assertSame(404, $this->handler->routePut(['profile', 'nonsense'], [])->getStatusCode());
    }
}
