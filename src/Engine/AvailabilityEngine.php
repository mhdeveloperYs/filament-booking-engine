<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Engine;

use Carbon\CarbonImmutable;
use MHStudio\FilamentBookingEngine\Data\AvailabilityContext;
use MHStudio\FilamentBookingEngine\Data\Slot;
use MHStudio\FilamentBookingEngine\Data\TimeRange;

/** Computes bookable slots from windows, occupied intervals and booking policy. */
final class AvailabilityEngine
{
    public function __construct(
        private readonly SlotGenerator $generator = new SlotGenerator,
        private readonly ConflictGuard $guard = new ConflictGuard,
    ) {}

    /**
     * Every candidate slot, with the reason it is unavailable.
     *
     * @return Slot[]
     */
    public function slots(AvailabilityContext $context): array
    {
        $now = $context->now ?? CarbonImmutable::now($context->timezone);
        $earliest = $now->addMinutes($context->minimumNoticeMinutes);
        $latest = $context->maximumAdvanceDays !== null
            ? $now->addDays($context->maximumAdvanceDays)
            : null;

        $candidates = $this->generator->generate(
            windows: $context->windows,
            durationMinutes: $context->durationMinutes,
            intervalMinutes: $context->slotIntervalMinutes,
        );

        $slots = [];

        foreach ($candidates as $candidate) {
            $reason = $this->reasonFor($candidate, $context, $now, $earliest, $latest);

            $slots[] = new Slot($candidate, $reason === null, $reason);
        }

        return $slots;
    }

    /**
     * @return Slot[]
     */
    public function availableSlots(AvailabilityContext $context): array
    {
        return array_values(array_filter(
            $this->slots($context),
            static fn (Slot $slot): bool => $slot->available,
        ));
    }

    private function reasonFor(
        TimeRange $candidate,
        AvailabilityContext $context,
        CarbonImmutable $now,
        CarbonImmutable $earliest,
        ?CarbonImmutable $latest,
    ): ?string {
        // Range problems are checked first: they are the more actionable message.
        if ($candidate->end->lessThanOrEqualTo($now)) {
            return Slot::REASON_PAST;
        }

        if ($candidate->start->lessThan($earliest)) {
            return Slot::REASON_NOTICE;
        }

        if ($latest !== null && $candidate->start->greaterThan($latest)) {
            return Slot::REASON_TOO_FAR;
        }

        if ($this->guard->conflicts(
            $candidate,
            $context->busy,
            $context->bufferBeforeMinutes,
            $context->bufferAfterMinutes,
        )) {
            return Slot::REASON_BOOKED;
        }

        return null;
    }
}
