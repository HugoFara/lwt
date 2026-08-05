<?php

/**
 * FSRS-6 scheduler.
 *
 * This file is a hand-port of the Free Spaced Repetition Scheduler reference
 * implementation (open-spaced-repetition/py-fsrs), and is therefore a
 * derivative work of it. The rest of LWT is released into the public domain
 * under the Unlicense; this file alone carries the reference implementation's
 * MIT notice, reproduced below as that licence requires.
 *
 * ---------------------------------------------------------------------------
 * MIT License
 *
 * Copyright (c) 2022 Open Spaced Repetition
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Lwt\Modules\Review\Domain\Scheduling;

use DateInterval;
use DateTimeImmutable;

/**
 * FSRS-6, the memory model Anki uses.
 *
 * Each term carries Stability (days for recall probability to fall to 0.9),
 * Difficulty (1..10) and, derived from those plus elapsed time,
 * Retrievability. A graded review updates S and D; the next interval is
 * whatever puts retrievability at the configured target.
 *
 * Ported with no sub-day learning steps, which is exactly the reference
 * implementation configured with an empty `learning_steps` — that branch sends
 * a term straight to the Review state. LWT schedules in whole days, so
 * sub-day steps would have nothing to express.
 *
 * Deliberately omitted from phase 2a: interval fuzzing (it exists to spread
 * Anki's daily load and would only add nondeterminism here) and the parameter
 * optimiser (needs accumulated history — see review_log).
 */
final class Fsrs6Scheduler implements SchedulerInterface
{
    private readonly FsrsParameters $params;
    private readonly float $decay;
    private readonly float $factor;

    public function __construct(?FsrsParameters $params = null)
    {
        $this->params = $params ?? new FsrsParameters();
        $this->decay = $this->params->decay();
        $this->factor = $this->params->factor();
    }

    public function review(?MemoryState $current, Rating $rating, DateTimeImmutable $now): SchedulingResult
    {
        $elapsedDays = $current?->elapsedDays($now) ?? 0;

        if ($current === null) {
            // First ever review: seed S and D from the grade alone.
            $stability = $this->initialStability($rating);
            $difficulty = $this->initialDifficulty($rating, true);
        } elseif ($elapsedDays < 1) {
            // Same-day repeat — the long-term formula assumes measurable decay
            // has happened, so FSRS uses a separate short-term update.
            $stability = $this->shortTermStability($current->stability, $rating);
            $difficulty = $this->nextDifficulty($current->difficulty, $rating);
        } else {
            $retrievability = $this->retrievability($current, $now);
            $stability = $this->nextStability(
                $current->difficulty,
                $current->stability,
                $retrievability,
                $rating
            );
            $difficulty = $this->nextDifficulty($current->difficulty, $rating);
        }

        $intervalDays = $this->nextInterval($stability);

        $state = new MemoryState(
            stability: $stability,
            difficulty: $difficulty,
            due: $now->add(new DateInterval('P' . $intervalDays . 'D')),
            lastReview: $now,
            reps: ($current?->reps ?? 0) + 1,
            lapses: ($current?->lapses ?? 0) + ($rating === Rating::Again ? 1 : 0),
            state: $rating === Rating::Again ? SchedulingState::Relearning : SchedulingState::Review,
        );

        return new SchedulingResult($state, $intervalDays, $elapsedDays);
    }

    public function retrievability(?MemoryState $state, DateTimeImmutable $now): float
    {
        if ($state === null || $state->lastReview === null) {
            return 0.0;
        }

        $elapsedDays = $state->elapsedDays($now);

        return (1 + $this->factor * $elapsedDays / $state->stability) ** $this->decay;
    }

    /**
     * Days until retrievability decays to the target retention.
     */
    private function nextInterval(float $stability): int
    {
        $interval = ($stability / $this->factor)
            * (($this->params->desiredRetention ** (1 / $this->decay)) - 1);

        $rounded = (int) round($interval);

        return max(1, min($rounded, $this->params->maximumInterval));
    }

    /**
     * S after a first review: w0..w3, indexed by grade.
     */
    private function initialStability(Rating $rating): float
    {
        return $this->clampStability($this->params->w($rating->value - 1));
    }

    /**
     * D after a first review: w4 - e^(w5 * (G-1)) + 1.
     *
     * Left unclamped when used as the mean-reversion target, which is what the
     * reference does — clamping it there would bias the reversion.
     */
    private function initialDifficulty(Rating $rating, bool $clamp): float
    {
        $difficulty = $this->params->w(4)
            - (M_E ** ($this->params->w(5) * ($rating->value - 1)))
            + 1;

        return $clamp ? $this->clampDifficulty($difficulty) : $difficulty;
    }

    /**
     * S for a same-day repeat.
     */
    private function shortTermStability(float $stability, Rating $rating): float
    {
        $increase = (M_E ** ($this->params->w(17) * ($rating->value - 3 + $this->params->w(18))))
            * ($stability ** -$this->params->w(19));

        // Only Good and Easy are floored at "no loss of stability". Hard is
        // deliberately allowed to reduce stability on a same-day repeat.
        //
        // Note for future updates: py-fsrs's unreleased `main` widens this
        // clamp to include Hard, which changes the result materially (a same-
        // day Hard becomes a no-op instead of a ~44% stability cut). We follow
        // the released v6.3.1 behaviour, which is what the reference vectors in
        // the test fixture were generated from — regenerate them if this is
        // ever retargeted at a newer release.
        if ($rating === Rating::Good || $rating === Rating::Easy) {
            $increase = max($increase, 1.0);
        }

        return $this->clampStability($stability * $increase);
    }

    /**
     * D after a review: grade-driven delta, linearly damped near the ceiling,
     * then reverted toward the "Easy" baseline by w7.
     */
    private function nextDifficulty(float $difficulty, Rating $rating): float
    {
        $target = $this->initialDifficulty(Rating::Easy, false);

        $deltaDifficulty = -($this->params->w(6) * ($rating->value - 3));
        $damped = $difficulty + ((10.0 - $difficulty) * $deltaDifficulty / 9.0);

        $next = $this->params->w(7) * $target + (1 - $this->params->w(7)) * $damped;

        return $this->clampDifficulty($next);
    }

    private function nextStability(
        float $difficulty,
        float $stability,
        float $retrievability,
        Rating $rating
    ): float {
        $next = $rating === Rating::Again
            ? $this->nextForgetStability($difficulty, $stability, $retrievability)
            : $this->nextRecallStability($difficulty, $stability, $retrievability, $rating);

        return $this->clampStability($next);
    }

    /**
     * S after a lapse. Capped by the same-day term so a lapse can never
     * increase stability.
     */
    private function nextForgetStability(
        float $difficulty,
        float $stability,
        float $retrievability
    ): float {
        $longTerm = $this->params->w(11)
            * ($difficulty ** -$this->params->w(12))
            * ((($stability + 1) ** $this->params->w(13)) - 1)
            * (M_E ** ((1 - $retrievability) * $this->params->w(14)));

        $shortTerm = $stability / (M_E ** ($this->params->w(17) * $this->params->w(18)));

        return min($longTerm, $shortTerm);
    }

    /**
     * S after a successful recall. The lower the retrievability at review
     * time, the bigger the gain — reviewing something you almost forgot is
     * worth more than reviewing something fresh.
     */
    private function nextRecallStability(
        float $difficulty,
        float $stability,
        float $retrievability,
        Rating $rating
    ): float {
        $hardPenalty = $rating === Rating::Hard ? $this->params->w(15) : 1.0;
        $easyBonus = $rating === Rating::Easy ? $this->params->w(16) : 1.0;

        return $stability * (
            1
            + (M_E ** $this->params->w(8))
            * (11 - $difficulty)
            * ($stability ** -$this->params->w(9))
            * ((M_E ** ((1 - $retrievability) * $this->params->w(10))) - 1)
            * $hardPenalty
            * $easyBonus
        );
    }

    private function clampStability(float $stability): float
    {
        return max($stability, FsrsParameters::STABILITY_MIN);
    }

    private function clampDifficulty(float $difficulty): float
    {
        return min(max($difficulty, FsrsParameters::MIN_DIFFICULTY), FsrsParameters::MAX_DIFFICULTY);
    }
}
