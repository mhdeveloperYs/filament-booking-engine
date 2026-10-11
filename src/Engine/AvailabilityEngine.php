<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Engine;

use Carbon\CarbonImmutable;
use MHStudio\FilamentBookingEngine\Data\AvailabilityContext;
use MHStudio\FilamentBookingEngine\Data\Slot;
use MHStudio\FilamentBookingEngine\Data\TimeRange;

/**
 * Computes bookable slots from availability windows, existing busy intervals and
 * the booking policy (notice, advance limit, buffers).
 *
 * The engine is deliberately pure and dependency-free: give it an
 * AvailabilityContext, get back Slot[].
 */
final class AvailabilityEngine
{
    public function __construct(
        private readonly SlotGenerator $generator = new SlotGenerator,
        private readonly ConflictGuard $guard = new ConflictGuard,
    ) {}

    /**
     * Every candidate slot, available or not, with the reason it was refused.
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
        // Priority order is intentional and documented: a slot that is both out
        // of range and busy reports the range problem first, which is the more
        // actionable message for a user moving their window.
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
