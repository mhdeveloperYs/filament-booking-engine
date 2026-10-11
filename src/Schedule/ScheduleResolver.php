<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Schedule;

use Carbon\CarbonImmutable;
use MHStudio\FilamentBookingEngine\Data\AvailabilityContext;
use MHStudio\FilamentBookingEngine\Data\TimeRange;
use MHStudio\FilamentBookingEngine\Enums\Weekday;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use MHStudio\FilamentBookingEngine\Models\AvailabilityRule;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;

/** Maps persisted schedule data onto the engine's value objects. */
final class ScheduleResolver
{
    /**
     * Availability windows for a provider on a day. A date exception overrides
     * the weekly rules.
     *
     * @return TimeRange[]
     */
    public function windowsFor(Provider $provider, CarbonImmutable $day): array
    {
        $timezone = $provider->bookingTimezone();
        $local = $day->setTimezone($timezone);

        $exception = $provider->availabilityExceptions()
            ->whereDate('date', $local->toDateString())
            ->first();

        if ($exception) {
            if ($exception->is_closed) {
                return [];
            }

            return $this->windowsFromDefinition($exception->windows ?? [], $local, $timezone);
        }

        $weekday = Weekday::fromDate($local);

        return $provider->availabilityRules()
            ->where('weekday', $weekday->value)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->map(fn (AvailabilityRule $rule): TimeRange => $this->windowFromTimes(
                $local,
                (string) $rule->start_time,
                (string) $rule->end_time,
                $timezone,
            ))
            ->all();
    }

    /**
     * Occupied intervals for a provider, each widened by its own service buffers.
     *
     * @return TimeRange[]
     */
    public function busyFor(
        Provider $provider,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $excludeAppointmentId = null,
    ): array {
        // Widen the query window: applying buffers can extend an interval past it.
        $from = $from->subDay();
        $to = $to->addDay();

        return $provider->appointments()
            ->active()
            ->with('service')
            ->when($excludeAppointmentId !== null, fn ($query) => $query->whereKeyNot($excludeAppointmentId))
            ->where('start_at', '<', $to)
            ->where('end_at', '>', $from)
            ->get()
            ->map(function (Appointment $appointment): TimeRange {
                $before = (int) $appointment->service->buffer_before_minutes;
                $after = (int) $appointment->service->buffer_after_minutes;

                return (new TimeRange(
                    CarbonImmutable::parse($appointment->start_at),
                    CarbonImmutable::parse($appointment->end_at),
                ))->expand($before, $after);
            })
            ->all();
    }

    /** Engine context for a service and provider on a day. */
    public function contextFor(Service $service, Provider $provider, CarbonImmutable $day): AvailabilityContext
    {
        return new AvailabilityContext(
            windows: $this->windowsFor($provider, $day),
            busy: $this->busyFor($provider, $day->startOfDay(), $day->endOfDay()),
            durationMinutes: (int) $service->duration_minutes,
            slotIntervalMinutes: (int) ($service->slot_interval_minutes
                ?? config('booking-engine.slot_interval_minutes', 15)),
            bufferBeforeMinutes: (int) $service->buffer_before_minutes,
            bufferAfterMinutes: (int) $service->buffer_after_minutes,
            minimumNoticeMinutes: (int) config('booking-engine.minimum_notice_minutes', 0),
            maximumAdvanceDays: config('booking-engine.maximum_advance_days'),
            timezone: $provider->bookingTimezone(),
        );
    }

    /**
     * @param  array<int, array{start?: string, end?: string}>  $definitions
     * @return TimeRange[]
     */
    private function windowsFromDefinition(array $definitions, CarbonImmutable $local, string $timezone): array
    {
        $windows = [];

        foreach ($definitions as $definition) {
            $start = $definition['start'] ?? null;
            $end = $definition['end'] ?? null;

            if (is_string($start) && is_string($end) && $start !== '' && $end !== '') {
                $windows[] = $this->windowFromTimes($local, $start, $end, $timezone);
            }
        }

        return $windows;
    }

    private function windowFromTimes(CarbonImmutable $local, string $start, string $end, string $timezone): TimeRange
    {
        return new TimeRange(
            CarbonImmutable::parse($local->toDateString().' '.$start, $timezone),
            CarbonImmutable::parse($local->toDateString().' '.$end, $timezone),
        );
    }
}
