<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Engine;

use MHStudio\FilamentBookingEngine\Data\TimeRange;

/** Generates candidate slot ranges from availability windows. */
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

        // Without a positive interval, tile the window edge to edge.
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
