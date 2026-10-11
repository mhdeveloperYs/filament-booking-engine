<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Engine;

use MHStudio\FilamentBookingEngine\Data\TimeRange;

/**
 * Turns availability windows into candidate slot ranges.
 *
 * A slot is emitted only when it fits entirely inside its window. The interval
 * controls the grid of candidate start times and is independent of the duration,
 * so a 60-minute service on a 15-minute grid produces overlapping candidates —
 * callers decide what to do with them (the AvailabilityEngine dedupes via busy
 * intervals; a picker may collapse overlaps).
 */
final class SlotGenerator
{
    /**
     * @param  TimeRange[]  $windows
     * @return TimeRange[]
     */
    public function generate(array $windows, int $durationMinutes, int $intervalMinutes): array
    {
        if ($durationMinutes <= 0) {
            return [];
        }

        // A non-positive interval is meaningless; fall back to the duration so
        // candidates tile the window without overlapping.
        $interval = $intervalMinutes > 0 ? $intervalMinutes : $durationMinutes;

        $slots = [];

        foreach ($windows as $window) {
            foreach ($this->slotsForWindow($window, $durationMinutes, $interval) as $slot) {
                $slots[] = $slot;
            }
        }

        usort(
            $slots,
            static fn (TimeRange $a, TimeRange $b): int => $a->start->getTimestamp() <=> $b->start->getTimestamp(),
        );

        return $slots;
    }

    /**
     * @return TimeRange[]
     */
    private function slotsForWindow(TimeRange $window, int $durationMinutes, int $intervalMinutes): array
    {
        $slots = [];
        $cursor = $window->start;

        while (true) {
            $end = $cursor->addMinutes($durationMinutes);

            if ($end->greaterThan($window->end)) {
                break;
            }

            $slots[] = new TimeRange($cursor, $end);
            $cursor = $cursor->addMinutes($intervalMinutes);
        }

        return $slots;
    }
}
