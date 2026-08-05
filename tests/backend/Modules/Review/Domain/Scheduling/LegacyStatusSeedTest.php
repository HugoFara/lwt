<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\Domain\Scheduling;

use DateTimeImmutable;
use DateTimeZone;
use Lwt\Modules\Review\Domain\Scheduling\Fsrs6Scheduler;
use Lwt\Modules\Review\Domain\Scheduling\FsrsParameters;
use Lwt\Modules\Review\Domain\Scheduling\LegacyStatusSeed;
use Lwt\Modules\Review\Domain\Scheduling\Rating;
use Lwt\Modules\Review\Domain\Scheduling\SchedulingState;
use PHPUnit\Framework\TestCase;

/**
 * Seeding existing terms into FSRS state.
 *
 * The point of these is continuity: an install upgrading to phase 2a must not
 * have its whole vocabulary become due at once, and must not lose the
 * distinction between a status-1 term and a status-5 one.
 */
final class LegacyStatusSeedTest extends TestCase
{
    private function changedAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('UTC'));
    }

    public function testEveryLearningStatusSeedsSchedulableState(): void
    {
        foreach ([1, 2, 3, 4, 5] as $status) {
            $state = LegacyStatusSeed::forStatus($status, $this->changedAt());

            $this->assertNotNull($state, "status {$status} must seed");
            $this->assertGreaterThanOrEqual(FsrsParameters::STABILITY_MIN, $state->stability);
            $this->assertGreaterThanOrEqual(FsrsParameters::MIN_DIFFICULTY, $state->difficulty);
            $this->assertLessThanOrEqual(FsrsParameters::MAX_DIFFICULTY, $state->difficulty);
            $this->assertSame(SchedulingState::Review, $state->state);
            $this->assertSame(0, $state->reps, 'a seeded term has no real review history');
        }
    }

    public function testStabilityIncreasesWithStatus(): void
    {
        $previous = 0.0;

        foreach ([1, 2, 3, 4, 5] as $status) {
            $state = LegacyStatusSeed::forStatus($status, $this->changedAt());
            $this->assertNotNull($state);

            $this->assertGreaterThan(
                $previous,
                $state->stability,
                "status {$status} must seed a higher stability than status " . ($status - 1)
            );
            $previous = $state->stability;
        }
    }

    public function testIgnoredAndWellKnownAreNotScheduled(): void
    {
        $this->assertNull(LegacyStatusSeed::forStatus(98, $this->changedAt()));
        $this->assertNull(LegacyStatusSeed::forStatus(99, $this->changedAt()));
    }

    public function testUnknownStatusIsNotScheduled(): void
    {
        $this->assertNull(LegacyStatusSeed::forStatus(0, $this->changedAt()));
        $this->assertNull(LegacyStatusSeed::forStatus(42, $this->changedAt()));
    }

    /**
     * The seed's due date must reproduce the legacy Leitner schedule, so
     * upgrading does not dump a user's whole vocabulary into the queue.
     */
    public function testDueDateMatchesTheLegacyInterval(): void
    {
        $changed = $this->changedAt();

        $expectedDays = [1 => 1, 2 => 2, 3 => 9, 4 => 27, 5 => 71];

        foreach ($expectedDays as $status => $days) {
            $state = LegacyStatusSeed::forStatus($status, $changed);
            $this->assertNotNull($state);

            $this->assertSame(
                $days,
                (int) $changed->diff($state->due)->days,
                "status {$status} should stay due {$days} days after its last status change"
            );
        }
    }

    /**
     * A seeded term must survive a real review without the scheduler choking
     * on it — this is the actual upgrade path for every existing term.
     */
    public function testSeededStateFeedsTheSchedulerCleanly(): void
    {
        $scheduler = new Fsrs6Scheduler();
        $changed = $this->changedAt();
        $reviewedAt = $changed->modify('+30 days');

        $seed = LegacyStatusSeed::forStatus(4, $changed);
        $this->assertNotNull($seed);

        $result = $scheduler->review($seed, Rating::Good, $reviewedAt);

        $this->assertGreaterThan($seed->stability, $result->state->stability);
        $this->assertSame(1, $result->state->reps);
        $this->assertSame(30, $result->elapsedDays);
        $this->assertGreaterThan(0, $result->intervalDays);
    }
}
