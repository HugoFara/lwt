<?php

declare(strict_types=1);

namespace Lwt\Modules\Review\Domain\Scheduling;

/**
 * Where a term sits in the scheduling lifecycle.
 *
 * Values match Anki's `cards.type` so the .apkg exporter can write them
 * straight through once phase 2b wires scheduling into the round-trip.
 */
enum SchedulingState: int
{
    case New = 0;
    case Learning = 1;
    case Review = 2;
    case Relearning = 3;
}
