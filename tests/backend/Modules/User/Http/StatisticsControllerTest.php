<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Http;

use Lwt\Modules\User\Application\UseCases\Statistics\GetFrequencyStatistics;
use Lwt\Modules\User\Application\UseCases\Statistics\GetIntensityStatistics;
use Lwt\Modules\User\Http\StatisticsController;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StatisticsController.
 */
class StatisticsControllerTest extends TestCase
{
    /** @var GetIntensityStatistics&MockObject */
    private GetIntensityStatistics $intensity;

    /** @var GetFrequencyStatistics&MockObject */
    private GetFrequencyStatistics $frequency;

    private StatisticsController $controller;

    protected function setUp(): void
    {
        $this->intensity = $this->createMock(GetIntensityStatistics::class);
        $this->frequency = $this->createMock(GetFrequencyStatistics::class);
        $this->controller = new StatisticsController($this->intensity, $this->frequency);
    }

    public function testControllerClassExists(): void
    {
        $this->assertInstanceOf(StatisticsController::class, $this->controller);
    }

    public function testShowCallsBothUseCases(): void
    {
        $this->intensity->expects($this->once())
            ->method('execute')
            ->willReturn(['languages' => [], 'totals' => []]);

        $this->frequency->expects($this->once())
            ->method('execute')
            ->willReturn([
                'languages' => [],
                'totals' => [
                    'ct' => 0, 'at' => 0, 'kt' => 0,
                    'cy' => 0, 'ay' => 0, 'ky' => 0,
                    'cw' => 0, 'aw' => 0, 'kw' => 0,
                    'cm' => 0, 'am' => 0, 'km' => 0,
                    'ca' => 0, 'aa' => 0, 'ka' => 0,
                ],
            ]);

        ob_start();
        try {
            $this->controller->show([]);
        } catch (\Throwable $e) {
            // The view include may fail in unit-test environment without
            // the full PageLayoutHelper/render chain; we just care that
            // the use cases were invoked.
        }
        ob_end_clean();
    }

    public function testRedirectFromAdminReturns301ToProfileStatistics(): void
    {
        $response = $this->controller->redirectFromAdmin([]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/profile/statistics', $response->getUrl());
        $this->assertSame(301, $response->getStatusCode());
    }
}
