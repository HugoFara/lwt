<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/Modules/User/Application/Services/PasswordService.php';

use Lwt\Modules\User\Application\Services\PasswordService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the PasswordService class.
 */
class PasswordServiceTest extends TestCase
{
    private PasswordService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordService();
    }

    // =========================================================================
    // Hashing Tests
    // =========================================================================

    public function testHashReturnsString(): void
    {
        $hash = $this->service->hash('password123');

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function testHashProducesDifferentHashesForSamePassword(): void
    {
        $hash1 = $this->service->hash('password123');
        $hash2 = $this->service->hash('password123');

        // Due to salting, hashes should be different
        $this->assertNotEquals($hash1, $hash2);
    }

    public function testHashedPasswordIsNotPlaintext(): void
    {
        $password = 'mysecretpassword';
        $hash = $this->service->hash($password);

        $this->assertNotEquals($password, $hash);
        $this->assertStringNotContainsString($password, $hash);
    }

    // =========================================================================
    // Verification Tests
    // =========================================================================

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $password = 'correctpassword';
        $hash = $this->service->hash($password);

        $this->assertTrue($this->service->verify($password, $hash));
    }

    public function testVerifyReturnsFalseForIncorrectPassword(): void
    {
        $password = 'correctpassword';
        $hash = $this->service->hash($password);

        $this->assertFalse($this->service->verify('wrongpassword', $hash));
    }

    public function testVerifyReturnsFalseForEmptyPassword(): void
    {
        $hash = $this->service->hash('somepassword');

        $this->assertFalse($this->service->verify('', $hash));
    }

    public function testVerifyIsCaseSensitive(): void
    {
        $password = 'Password123';
        $hash = $this->service->hash($password);

        $this->assertFalse($this->service->verify('password123', $hash));
        $this->assertFalse($this->service->verify('PASSWORD123', $hash));
    }

    public function testVerifyHandlesSpecialCharacters(): void
    {
        $password = 'p@$$w0rd!#%^&*()';
        $hash = $this->service->hash($password);

        $this->assertTrue($this->service->verify($password, $hash));
    }

    public function testVerifyHandlesUnicodeCharacters(): void
    {
        $password = 'пароль密码كلمة';
        $hash = $this->service->hash($password);

        $this->assertTrue($this->service->verify($password, $hash));
    }

    // =========================================================================
    // Rehash Tests
    // =========================================================================

    public function testNeedsRehashReturnsFalseForFreshHash(): void
    {
        $hash = $this->service->hash('password123');

        $this->assertFalse($this->service->needsRehash($hash));
    }

    public function testNeedsRehashReturnsTrueForOutdatedHash(): void
    {
        // Create an MD5 hash (definitely outdated)
        $outdatedHash = md5('password123');

        $this->assertTrue($this->service->needsRehash($outdatedHash));
    }

    // =========================================================================
    // Hash Info Tests
    // =========================================================================

    public function testGetHashInfoReturnsArray(): void
    {
        $hash = $this->service->hash('password123');
        $info = $this->service->getHashInfo($hash);

        $this->assertIsArray($info);
        $this->assertArrayHasKey('algo', $info);
        $this->assertArrayHasKey('algoName', $info);
        $this->assertArrayHasKey('options', $info);
    }

    public function testGetHashInfoShowsCorrectAlgorithm(): void
    {
        $hash = $this->service->hash('password123');
        $info = $this->service->getHashInfo($hash);

        // Should be either argon2id or bcrypt
        $this->assertContains(
            $info['algoName'],
            ['argon2id', 'bcrypt'],
            'Algorithm should be argon2id or bcrypt'
        );
    }

    // =========================================================================
    // Password Strength Validation Tests
    // =========================================================================

    public function testValidateStrengthPassesValidPassword(): void
    {
        $result = $this->service->validateStrength('Password1');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateStrengthRejectsTooShortPassword(): void
    {
        $result = $this->service->validateStrength('Pass1');

        $this->assertFalse($result['valid']);
        $this->assertContains('Password must be at least 8 characters long', $result['errors']);
    }

    public function testValidateStrengthRejectsTooLongPassword(): void
    {
        $longPassword = str_repeat('a', 129) . '1';
        $result = $this->service->validateStrength($longPassword);

        $this->assertFalse($result['valid']);
        $this->assertContains('Password cannot exceed 128 characters', $result['errors']);
    }

    public function testValidateStrengthRejectsNoLetters(): void
    {
        $result = $this->service->validateStrength('12345678');

        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one letter', $result['errors']);
    }

    public function testValidateStrengthRejectsNoNumbers(): void
    {
        $result = $this->service->validateStrength('Password');

        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one number', $result['errors']);
    }

    public function testValidateStrengthCanReturnMultipleErrors(): void
    {
        // Too short, no letters, no numbers
        $result = $this->service->validateStrength('!!!');

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(2, count($result['errors']));
    }

    // =========================================================================
    // Token Generation Tests
    // =========================================================================

    public function testGenerateTokenReturnsString(): void
    {
        $token = $this->service->generateToken();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateTokenReturnsCorrectLength(): void
    {
        // Default 32 bytes = 64 hex characters
        $token = $this->service->generateToken();
        $this->assertEquals(64, strlen($token));

        // 16 bytes = 32 hex characters
        $token = $this->service->generateToken(16);
        $this->assertEquals(32, strlen($token));
    }

    public function testGenerateTokenReturnsHexString(): void
    {
        $token = $this->service->generateToken();

        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
    }

    public function testGenerateTokenReturnsUniqueValues(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $this->service->generateToken();
        }

        // All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(100, $uniqueTokens);
    }
}
