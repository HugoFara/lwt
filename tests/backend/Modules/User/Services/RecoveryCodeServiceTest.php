<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Services;

use Lwt\Modules\User\Application\Services\RecoveryCodeService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the recovery code service.
 */
class RecoveryCodeServiceTest extends TestCase
{
    private RecoveryCodeService $service;

    protected function setUp(): void
    {
        $this->service = new RecoveryCodeService();
    }

    public function testGenerateReturnsCodeAndHash(): void
    {
        $generated = $this->service->generate();

        $this->assertArrayHasKey('code', $generated);
        $this->assertArrayHasKey('hash', $generated);
        $this->assertNotSame('', $generated['code']);
        // Displayed as dash-separated groups for readability.
        $this->assertMatchesRegularExpression('/^[0-9A-F]{5}(-[0-9A-F]{5})+$/', $generated['code']);
        // The hash is not the code.
        $this->assertNotSame($generated['code'], $generated['hash']);
    }

    public function testGenerateProducesUniqueCodes(): void
    {
        $this->assertNotSame(
            $this->service->generate()['code'],
            $this->service->generate()['code']
        );
    }

    public function testVerifyAcceptsTheGeneratedCode(): void
    {
        $generated = $this->service->generate();

        $this->assertTrue($this->service->verify($generated['code'], $generated['hash']));
    }

    public function testVerifyIsTolerantOfFormattingAndCase(): void
    {
        $generated = $this->service->generate();
        $messy = ' ' . strtolower(str_replace('-', ' ', $generated['code'])) . ' ';

        // Lower-case, spaces instead of dashes, surrounding whitespace.
        $this->assertTrue($this->service->verify($messy, $generated['hash']));
    }

    public function testVerifyRejectsWrongCode(): void
    {
        $generated = $this->service->generate();

        $this->assertFalse($this->service->verify('WRONG-CODE-00000-00000', $generated['hash']));
        $this->assertFalse($this->service->verify('', $generated['hash']));
    }
}
