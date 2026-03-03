<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Application\Services;

use Lwt\Modules\User\Application\Services\TokenHasher;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the TokenHasher service.
 *
 * @covers \Lwt\Modules\User\Application\Services\TokenHasher
 */
class TokenHasherTest extends TestCase
{
    private TokenHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = new TokenHasher();
    }

    // =========================================================================
    // hash() Tests
    // =========================================================================

    public function testHashReturns64HexCharacters(): void
    {
        $hash = $this->hasher->hash('some-token-value');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function testHashIsDeterministic(): void
    {
        $token = 'same-token';

        $hash1 = $this->hasher->hash($token);
        $hash2 = $this->hasher->hash($token);

        // SHA-256 is deterministic (no salt)
        $this->assertEquals($hash1, $hash2);
    }

    public function testHashProducesDifferentOutputForDifferentTokens(): void
    {
        $hash1 = $this->hasher->hash('token-one');
        $hash2 = $this->hasher->hash('token-two');

        $this->assertNotEquals($hash1, $hash2);
    }

    public function testHashMatchesExpectedSha256Output(): void
    {
        $token = 'test-token';
        $expected = hash('sha256', $token);

        $this->assertEquals($expected, $this->hasher->hash($token));
    }

    // =========================================================================
    // verify() Tests
    // =========================================================================

    public function testVerifyReturnsTrueForCorrectToken(): void
    {
        $token = 'my-secret-token';
        $hash = $this->hasher->hash($token);

        $this->assertTrue($this->hasher->verify($token, $hash));
    }

    public function testVerifyReturnsFalseForWrongToken(): void
    {
        $hash = $this->hasher->hash('correct-token');

        $this->assertFalse($this->hasher->verify('wrong-token', $hash));
    }

    public function testVerifyReturnsFalseForEmptyToken(): void
    {
        $hash = $this->hasher->hash('some-token');

        $this->assertFalse($this->hasher->verify('', $hash));
    }

    public function testVerifyReturnsFalseForNearMissToken(): void
    {
        $token = 'abcdef123456';
        $hash = $this->hasher->hash($token);

        // Single character difference
        $this->assertFalse($this->hasher->verify('abcdef123457', $hash));
    }

    public function testVerifyReturnsFalseForTokenWithExtraCharacter(): void
    {
        $token = 'my-token';
        $hash = $this->hasher->hash($token);

        $this->assertFalse($this->hasher->verify('my-token-', $hash));
    }

    public function testVerifyReturnsFalseForTruncatedToken(): void
    {
        $token = 'my-token';
        $hash = $this->hasher->hash($token);

        $this->assertFalse($this->hasher->verify('my-toke', $hash));
    }

    public function testVerifyReturnsFalseForCaseDifference(): void
    {
        $token = 'My-Token';
        $hash = $this->hasher->hash($token);

        $this->assertFalse($this->hasher->verify('my-token', $hash));
    }

    // =========================================================================
    // generate() Tests
    // =========================================================================

    public function testGenerateReturnsHexString(): void
    {
        $token = $this->hasher->generate();

        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
    }

    public function testGenerateDefaultLengthIs64HexChars(): void
    {
        $token = $this->hasher->generate();

        // 32 bytes = 64 hex characters
        $this->assertEquals(64, strlen($token));
    }

    public function testGenerateCustomLength(): void
    {
        $token = $this->hasher->generate(16);

        // 16 bytes = 32 hex characters
        $this->assertEquals(32, strlen($token));
    }

    public function testGenerateProducesUniqueTokens(): void
    {
        $token1 = $this->hasher->generate();
        $token2 = $this->hasher->generate();

        $this->assertNotEquals($token1, $token2);
    }

    // =========================================================================
    // Integration: generate + hash + verify
    // =========================================================================

    public function testGeneratedTokenCanBeHashedAndVerified(): void
    {
        $token = $this->hasher->generate();
        $hash = $this->hasher->hash($token);

        $this->assertTrue($this->hasher->verify($token, $hash));
    }

    public function testDifferentGeneratedTokenFailsVerification(): void
    {
        $token1 = $this->hasher->generate();
        $token2 = $this->hasher->generate();
        $hash = $this->hasher->hash($token1);

        $this->assertFalse($this->hasher->verify($token2, $hash));
    }
}
