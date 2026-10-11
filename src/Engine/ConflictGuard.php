<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Engine;

use MHStudio\FilamentBookingEngine\Data\TimeRange;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;

/** Decides whether a candidate interval collides with an occupied one. */
final class ConflictGuard
{
    /**
     * @param  iterable<TimeRange>  $busy
     */
    public function conflicts(
        TimeRange $candidate,
        iterable $busy,
        int $bufferBeforeMinutes = 0,
        int $bufferAfterMinutes = 0,
    ): bool {
        $occupied = $candidate->expand($bufferBeforeMinutes, $bufferAfterMinutes);

        foreach ($busy as $range) {
            if ($occupied->overlaps($range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  iterable<TimeRange>  $busy
     *
     * @throws SlotUnavailableException
     */
    public function assertAvailable(
        TimeRange $candidate,
        iterable $busy,
        int $bufferBeforeMinutes = 0,
        int $bufferAfterMinutes = 0,
    ): void {
        if ($this->conflicts($candidate, $busy, $bufferBeforeMinutes, $bufferAfterMinutes)) {
            throw new SlotUnavailableException(sprintf(
                'The slot [%s, %s) is not available.',
                $candidate->start->toIso8601String(),
                $candidate->end->toIso8601String(),
            ));
        }
    }
}
