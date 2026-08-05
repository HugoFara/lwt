<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\Domain\Scheduling;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Lwt\Modules\Review\Domain\Scheduling\Fsrs6Scheduler;
use Lwt\Modules\Review\Domain\Scheduling\FsrsParameters;
use Lwt\Modules\Review\Domain\Scheduling\MemoryState;
use Lwt\Modules\Review\Domain\Scheduling\Rating;
use Lwt\Modules\Review\Domain\Scheduling\SchedulingState;
use PHPUnit\Framework\TestCase;

/**
 * Validates the FSRS-6 port against ground truth from the reference
 * implementation.
 *
 * The vectors in fixtures/fsrs6_reference_vectors.json were produced by
 * open-spaced-repetition/py-fsrs (see the generator script alongside them),
 * configured with empty learning/relearning steps and fuzzing disabled — the
 * same configuration this port implements. Regenerate them with:
 *
 *   python -m venv .venv && ./.venv/bin/pip install fsrs
 *   ./.venv/bin/python tests/.../fixtures/generate_reference_vectors.py
 *
 * If these fail after a change to Fsrs6Scheduler, the port has drifted from
 * the reference — that is the point of the test.
 */
final class Fsrs6SchedulerTest extends TestCase
{
    /** Reference vectors are rounded to 12 decimal places when generated. */
    private const EPSILON = 1e-9;

    private const START = '2026-01-01 12:00:00';

    private function scheduler(): Fsrs6Scheduler
    {
        return new Fsrs6Scheduler();
    }

    private function start(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::START, new DateTimeZone('UTC'));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function referenceSequenceProvider(): array
    {
        $path = __DIR__ . '/fixtures/fsrs6_reference_vectors.json';
        $raw = file_get_contents($path);
        self::assertIsString($raw, 'reference vectors fixture is unreadable');

        /** @var list<array{name: string, reviews: list<array<string, mixed>>}> $sequences */
        $sequences = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $cases = [];
        foreach ($sequences as $sequence) {
            $cases[$sequence['name']] = [$sequence];
        }

        return $cases;
    }

    /**
     * Replay each reference sequence and compare S, D, retrievability and the
     * scheduled interval at every step.
     *
     * @param array{name: string, reviews: list<array<string, mixed>>} $sequence
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('referenceSequenceProvider')]
    public function testMatchesReferenceImplementation(array $sequence): void
    {
        $scheduler = $this->scheduler();
        $now = $this->start();
        $state = null;

        foreach ($sequence['reviews'] as $index => $expected) {
            $advance = (int) $expected['advance_days'];
            if ($advance > 0) {
                $now = $now->add(new DateInterval('P' . $advance . 'D'));
            }

            $label = sprintf('%s step %d', $sequence['name'], $index);

            $this->assertEqualsWithDelta(
                (float) $expected['retrievability_before'],
                $scheduler->retrievability($state, $now),
                self::EPSILON,
                "{$label}: retrievability before review"
            );

            $result = $scheduler->review($state, Rating::from((int) $expected['grade']), $now);
            $state = $result->state;

            $this->assertEqualsWithDelta(
                (float) $expected['stability'],
                $state->stability,
                self::EPSILON,
                "{$label}: stability"
            );
            $this->assertEqualsWithDelta(
                (float) $expected['difficulty'],
                $state->difficulty,
                self::EPSILON,
                "{$label}: difficulty"
            );
            $this->assertSame(
                (int) $expected['interval_days'],
                $result->intervalDays,
                "{$label}: interval"
            );
        }
    }

    public function testFirstReviewSeedsStabilityFromGradeWeights(): void
    {
        $scheduler = $this->scheduler();

        foreach (Rating::cases() as $rating) {
            $result = $scheduler->review(null, $rating, $this->start());

            $this->assertEqualsWithDelta(
                FsrsParameters::DEFAULT_WEIGHTS[$rating->value - 1],
                $result->state->stability,
                self::EPSILON,
                "initial stability for {$rating->name}"
            );
        }
    }

    public function testRetrievabilityIsZeroForAnUnreviewedTerm(): void
    {
        $this->assertSame(0.0, $this->scheduler()->retrievability(null, $this->start()));
    }

    public function testRetrievabilityIsExactlyTargetAfterOneStabilityPeriod(): void
    {
        // Stability is *defined* as the time for retrievability to reach 0.9.
        $scheduler = $this->scheduler();
        $now = $this->start();

        $state = new MemoryState(
            stability: 10.0,
            difficulty: 5.0,
            due: $now,
            lastReview: $now,
        );

        $tenDaysLater = $now->add(new DateInterval('P10D'));

        $this->assertEqualsWithDelta(0.9, $scheduler->retrievability($state, $tenDaysLater), 1e-12);
    }

    public function testAgainRecordsALapseAndMovesToRelearning(): void
    {
        $scheduler = $this->scheduler();
        $now = $this->start();

        $first = $scheduler->review(null, Rating::Good, $now);
        $this->assertSame(0, $first->state->lapses);
        $this->assertSame(SchedulingState::Review, $first->state->state);

        $lapsed = $scheduler->review($first->state, Rating::Again, $now->add(new DateInterval('P5D')));

        $this->assertSame(1, $lapsed->state->lapses);
        $this->assertSame(2, $lapsed->state->reps);
        $this->assertSame(SchedulingState::Relearning, $lapsed->state->state);
    }

    public function testALapseNeverIncreasesStability(): void
    {
        $scheduler = $this->scheduler();
        $now = $this->start();

        $state = new MemoryState(
            stability: 50.0,
            difficulty: 5.0,
            due: $now,
            lastReview: $now,
        );

        $result = $scheduler->review($state, Rating::Again, $now->add(new DateInterval('P50D')));

        $this->assertLessThanOrEqual(50.0, $result->state->stability);
    }

    public function testEasyGivesALongerIntervalThanHardFromTheSameState(): void
    {
        $scheduler = $this->scheduler();
        $now = $this->start();
        $later = $now->add(new DateInterval('P10D'));

        $state = new MemoryState(
            stability: 10.0,
            difficulty: 5.0,
            due: $later,
            lastReview: $now,
        );

        $hard = $scheduler->review($state, Rating::Hard, $later)->intervalDays;
        $good = $scheduler->review($state, Rating::Good, $later)->intervalDays;
        $easy = $scheduler->review($state, Rating::Easy, $later)->intervalDays;

        $this->assertLessThan($good, $hard, 'Hard must schedule sooner than Good');
        $this->assertLessThan($easy, $good, 'Good must schedule sooner than Easy');
    }

    public function testDifficultyStaysWithinBounds(): void
    {
        $scheduler = $this->scheduler();
        $now = $this->start();
        $state = null;

        // Hammer Again repeatedly — difficulty must saturate at 10, not exceed it.
        for ($i = 0; $i < 40; $i++) {
            $now = $now->add(new DateInterval('P1D'));
            $state = $scheduler->review($state, Rating::Again, $now)->state;
            $this->assertGreaterThanOrEqual(FsrsParameters::MIN_DIFFICULTY, $state->difficulty);
            $this->assertLessThanOrEqual(FsrsParameters::MAX_DIFFICULTY, $state->difficulty);
            $this->assertGreaterThanOrEqual(FsrsParameters::STABILITY_MIN, $state->stability);
        }

        // ...and Easy repeatedly must saturate at 1.
        $state = null;
        for ($i = 0; $i < 40; $i++) {
            $now = $now->add(new DateInterval('P30D'));
            $state = $scheduler->review($state, Rating::Easy, $now)->state;
            $this->assertGreaterThanOrEqual(FsrsParameters::MIN_DIFFICULTY, $state->difficulty);
            $this->assertLessThanOrEqual(FsrsParameters::MAX_DIFFICULTY, $state->difficulty);
        }
    }

    public function testIntervalRespectsMaximum(): void
    {
        $scheduler = new Fsrs6Scheduler(new FsrsParameters(maximumInterval: 30));
        $now = $this->start();

        $state = new MemoryState(
            stability: 100000.0,
            difficulty: 1.0,
            due: $now,
            lastReview: $now,
        );

        $result = $scheduler->review($state, Rating::Easy, $now->add(new DateInterval('P1D')));

        $this->assertSame(30, $result->intervalDays);
    }

    public function testIntervalIsAtLeastOneDay(): void
    {
        $scheduler = $this->scheduler();
        $result = $scheduler->review(null, Rating::Again, $this->start());

        $this->assertGreaterThanOrEqual(1, $result->intervalDays);
    }

    public function testHigherRetentionTargetSchedulesSooner(): void
    {
        $now = $this->start();
        $state = new MemoryState(
            stability: 30.0,
            difficulty: 5.0,
            due: $now,
            lastReview: $now,
        );
        $later = $now->add(new DateInterval('P10D'));

        $relaxed = (new Fsrs6Scheduler(new FsrsParameters(desiredRetention: 0.8)))
            ->review($state, Rating::Good, $later)->intervalDays;
        $strict = (new Fsrs6Scheduler(new FsrsParameters(desiredRetention: 0.95)))
            ->review($state, Rating::Good, $later)->intervalDays;

        $this->assertLessThan($relaxed, $strict, 'A stricter retention target must review sooner');
    }

    public function testParametersRejectWrongWeightCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FsrsParameters([1.0, 2.0, 3.0]);
    }

    public function testParametersRejectOutOfRangeRetention(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FsrsParameters(desiredRetention: 1.0);
    }
}
