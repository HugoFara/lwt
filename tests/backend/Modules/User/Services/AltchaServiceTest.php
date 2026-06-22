<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Services;

use Lwt\Modules\User\Application\Services\AltchaService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the self-hosted ALTCHA proof-of-work captcha service.
 */
class AltchaServiceTest extends TestCase
{
    private const KEY = 'test-secret-key';

    private function service(bool $enabled = true): AltchaService
    {
        return new AltchaService(self::KEY, $enabled);
    }

    /**
     * Solve a challenge the way a browser would: find the number whose
     * SHA-256(salt . number) equals the challenge hash.
     *
     * @param array{algorithm: string, challenge: string, maxnumber: int,
     *              salt: string, signature: string} $challenge
     */
    private function solve(array $challenge): string
    {
        for ($n = 0; $n <= $challenge['maxnumber']; $n++) {
            if (hash('sha256', $challenge['salt'] . $n) === $challenge['challenge']) {
                return base64_encode((string) json_encode([
                    'algorithm' => $challenge['algorithm'],
                    'challenge' => $challenge['challenge'],
                    'number' => $n,
                    'salt' => $challenge['salt'],
                    'signature' => $challenge['signature'],
                ]));
            }
        }
        self::fail('Challenge was not solvable within maxnumber');
    }

    public function testCreateChallengeHasExpectedStructure(): void
    {
        $challenge = $this->service()->createChallenge();

        $this->assertSame('SHA-256', $challenge['algorithm']);
        $this->assertNotSame('', $challenge['challenge']);
        $this->assertStringContainsString('?expires=', $challenge['salt']);
        $this->assertGreaterThan(0, $challenge['maxnumber']);
        // The signature is the HMAC of the challenge under the server key.
        $this->assertSame(
            hash_hmac('sha256', $challenge['challenge'], self::KEY),
            $challenge['signature']
        );
    }

    public function testVerifyAcceptsValidSolution(): void
    {
        $service = $this->service();
        $payload = $this->solve($service->createChallenge());

        $this->assertTrue($service->verify($payload));
    }

    public function testVerifyRejectsTamperedNumber(): void
    {
        $service = $this->service();
        $challenge = $service->createChallenge();
        $payload = $this->solve($challenge);

        $decoded = json_decode((string) base64_decode($payload, true), true);
        $decoded['number'] = (int) $decoded['number'] + 1; // wrong answer
        $tampered = base64_encode((string) json_encode($decoded));

        $this->assertFalse($service->verify($tampered));
    }

    public function testVerifyRejectsForgedSignature(): void
    {
        // A challenge signed with a different key must not validate — this is
        // what stops a client minting its own trivial challenges.
        $foreign = new AltchaService('a-different-key');
        $payload = $this->solve($foreign->createChallenge());

        $this->assertFalse($this->service()->verify($payload));
    }

    public function testVerifyRejectsExpiredChallenge(): void
    {
        $salt = 'deadbeef?expires=' . (time() - 10);
        $number = 7;
        $challenge = hash('sha256', $salt . $number);
        $payload = base64_encode((string) json_encode([
            'algorithm' => 'SHA-256',
            'challenge' => $challenge,
            'number' => $number,
            'salt' => $salt,
            'signature' => hash_hmac('sha256', $challenge, self::KEY),
        ]));

        $this->assertFalse($this->service()->verify($payload));
    }

    public function testVerifyRejectsEmptyAndMalformedPayloads(): void
    {
        $service = $this->service();

        $this->assertFalse($service->verify(''));
        $this->assertFalse($service->verify('not-base64-or-json!!'));
        $this->assertFalse($service->verify(base64_encode('{"not":"a challenge"}')));
    }

    public function testDisabledServiceAlwaysVerifies(): void
    {
        $service = $this->service(false);

        $this->assertTrue($service->isEnabled() === false);
        $this->assertTrue($service->verify(''));
        $this->assertTrue($service->verify('anything'));
    }
}
