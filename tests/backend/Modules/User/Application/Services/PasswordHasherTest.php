<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Application\Services;

use Lwt\Modules\User\Application\Services\PasswordHasher;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the PasswordHasher service.
 *
 * @covers \Lwt\Modules\User\Application\Services\PasswordHasher
 */
class PasswordHasherTest extends TestCase
{
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = new PasswordHasher();
    }

    // =========================================================================
    // hash() Tests
    // =========================================================================

    public function testHashReturnsNonEmptyString(): void
    {
        $hash = $this->hasher->hash('MyPassword1');

        $this->assertNotEmpty($hash);
        $this->assertIsString($hash);
    }

    public function testHashReturnsDifferentHashesForSamePassword(): void
    {
        $hash1 = $this->hasher->hash('MyPassword1');
        $hash2 = $this->hasher->hash('MyPassword1');

        // Salted hashing should produce different hashes each time
        $this->assertNotEquals($hash1, $hash2);
    }

    public function testHashReturnsDifferentHashesForDifferentPasswords(): void
    {
        $hash1 = $this->hasher->hash('PasswordOne1');
        $hash2 = $this->hasher->hash('PasswordTwo2');

        $this->assertNotEquals($hash1, $hash2);
    }

    // =========================================================================
    // verify() Tests
    // =========================================================================

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $password = 'CorrectPassword1';
        $hash = $this->hasher->hash($password);

        $this->assertTrue($this->hasher->verify($password, $hash));
    }

    public function testVerifyReturnsFalseForWrongPassword(): void
    {
        $hash = $this->hasher->hash('CorrectPassword1');

        $this->assertFalse($this->hasher->verify('WrongPassword2', $hash));
    }

    public function testVerifyReturnsFalseForEmptyPassword(): void
    {
        $hash = $this->hasher->hash('SomePassword1');

        $this->assertFalse($this->hasher->verify('', $hash));
    }

    // =========================================================================
    // needsRehash() Tests
    // =========================================================================

    public function testNeedsRehashReturnsFalseForCurrentHash(): void
    {
        $hash = $this->hasher->hash('TestPassword1');

        // A freshly created hash should not need rehashing
        $this->assertFalse($this->hasher->needsRehash($hash));
    }

    public function testNeedsRehashReturnsTrueForWeakBcryptHash(): void
    {
        // Create a bcrypt hash with very low cost (weaker than the service default)
        $weakHash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 4]);

        // The service uses Argon2ID or bcrypt cost=12, so a cost=4 hash needs rehash
        $this->assertTrue($this->hasher->needsRehash($weakHash));
    }

    // =========================================================================
    // validateStrength() Tests
    // =========================================================================

    public function testValidateStrengthAcceptsStrongPassword(): void
    {
        $result = $this->hasher->validateStrength('StrongPass1');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateStrengthRejectsTooShortPassword(): void
    {
        $result = $this->hasher->validateStrength('Ab1');

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'Password must be at least 8 characters long',
            $result['errors']
        );
    }

    public function testValidateStrengthRejectsTooLongPassword(): void
    {
        $longPassword = str_repeat('Aa1', 50); // 150 characters

        $result = $this->hasher->validateStrength($longPassword);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'Password cannot exceed 128 characters',
            $result['errors']
        );
    }

    public function testValidateStrengthRejectsPasswordWithoutLetters(): void
    {
        $result = $this->hasher->validateStrength('12345678');

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'Password must contain at least one letter',
            $result['errors']
        );
    }

    public function testValidateStrengthRejectsPasswordWithoutNumbers(): void
    {
        $result = $this->hasher->validateStrength('abcdefgh');

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'Password must contain at least one number',
            $result['errors']
        );
    }

    public function testValidateStrengthReturnsMultipleErrors(): void
    {
        // Very short, no letters, no numbers
        $result = $this->hasher->validateStrength('!!!');

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(3, count($result['errors']));
    }

    public function testValidateStrengthAcceptsExactMinimumLength(): void
    {
        $result = $this->hasher->validateStrength('Abcdefg1'); // exactly 8 chars

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateStrengthAcceptsExactMaximumLength(): void
    {
        // 128 characters with letters and numbers
        $password = str_repeat('Ab1', 42) . 'Ab'; // 42*3 + 2 = 128

        $result = $this->hasher->validateStrength($password);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    // =========================================================================
    // generateToken() Tests
    // =========================================================================

    public function testGenerateTokenReturnsHexString(): void
    {
        $token = $this->hasher->generateToken();

        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
    }

    public function testGenerateTokenDefaultLength(): void
    {
        $token = $this->hasher->generateToken();

        // 32 bytes = 64 hex characters
        $this->assertEquals(64, strlen($token));
    }

    public function testGenerateTokenCustomLength(): void
    {
        $token = $this->hasher->generateToken(16);

        // 16 bytes = 32 hex characters
        $this->assertEquals(32, strlen($token));
    }

    public function testGenerateTokenProducesUniqueValues(): void
    {
        $token1 = $this->hasher->generateToken();
        $token2 = $this->hasher->generateToken();

        $this->assertNotEquals($token1, $token2);
    }
}
