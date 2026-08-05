<?php

declare(strict_types=1);

namespace Lwt\Modules\Review\Domain\Scheduling;

use InvalidArgumentException;

/**
 * FSRS-6 tuning parameters.
 *
 * The 21 weights are the algorithm's fitted constants (w0..w20). The defaults
 * are FSRS-6's published values — identical to `DEFAULT_PARAMETERS` in
 * open-spaced-repetition/py-fsrs v6 — and are what every user gets until
 * per-user optimisation exists (that needs accumulated `review_log` history,
 * deliberately out of scope for phase 2a).
 *
 * w20 is the learnable decay of the forgetting curve, new in FSRS-6.
 */
final class FsrsParameters
{
    public const PARAMETER_COUNT = 21;

    /** FSRS-6 default decay (w20). */
    public const DEFAULT_DECAY = 0.1542;

    /** FSRS clamps stability to this floor to keep the power terms finite. */
    public const STABILITY_MIN = 0.001;

    public const MIN_DIFFICULTY = 1.0;
    public const MAX_DIFFICULTY = 10.0;

    /**
     * Published FSRS-6 defaults, w0..w20.
     *
     * @var list<float>
     */
    public const DEFAULT_WEIGHTS = [
        0.212, 1.2931, 2.3065, 8.2956, 6.4133, 0.8334, 3.0194, 0.001,
        1.8722, 0.1666, 0.796, 1.4835, 0.0614, 0.2629, 1.6483, 0.6014,
        1.8729, 0.5425, 0.0912, 0.0658, self::DEFAULT_DECAY,
    ];

    /** @var list<float> */
    public readonly array $weights;

    /**
     * @param list<float>|null $weights          21 FSRS weights; null = defaults.
     * @param float            $desiredRetention Target recall probability at the
     *                                           moment a term comes due.
     * @param int              $maximumInterval  Hard cap on scheduled days.
     */
    public function __construct(
        ?array $weights = null,
        public readonly float $desiredRetention = 0.9,
        public readonly int $maximumInterval = 36500,
    ) {
        $weights ??= self::DEFAULT_WEIGHTS;

        if (count($weights) !== self::PARAMETER_COUNT) {
            throw new InvalidArgumentException(
                'FSRS requires exactly ' . self::PARAMETER_COUNT . ' parameters, got ' . count($weights)
            );
        }
        if ($desiredRetention <= 0.0 || $desiredRetention >= 1.0) {
            throw new InvalidArgumentException('Desired retention must be strictly between 0 and 1');
        }
        if ($maximumInterval < 1) {
            throw new InvalidArgumentException('Maximum interval must be at least 1 day');
        }

        $this->weights = $weights;
    }

    /**
     * Weight w{$index}.
     */
    public function w(int $index): float
    {
        return $this->weights[$index];
    }

    /**
     * Curve decay, i.e. -w20. Negative by construction.
     */
    public function decay(): float
    {
        return -$this->weights[20];
    }

    /**
     * The constant that makes the forgetting curve pass through
     * R = 0.9 at t = stability.
     */
    public function factor(): float
    {
        return 0.9 ** (1 / $this->decay()) - 1;
    }
}
