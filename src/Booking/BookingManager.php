<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Booking;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use MHStudio\FilamentBookingEngine\Concurrency\BookingLock;
use MHStudio\FilamentBookingEngine\Data\TimeRange;
use MHStudio\FilamentBookingEngine\Engine\ConflictGuard;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Events\AppointmentCancelled;
use MHStudio\FilamentBookingEngine\Events\AppointmentCreated;
use MHStudio\FilamentBookingEngine\Events\AppointmentRescheduled;
use MHStudio\FilamentBookingEngine\Events\AppointmentStatusChanged;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Schedule\ScheduleResolver;

/**
 * The application-layer front door to an appointment's lifecycle.
 *
 * Every write goes through here so that:
 *  - the slot is validated against availability and existing bookings;
 *  - the operation is serialized (cache lock) and transactional (row lock);
 *  - the matching domain event is dispatched exactly once.
 *
 * Callers should never flip a status directly: doing so would skip the guard and
 * the event.
 */
final class BookingManager
{
    private readonly BookingLock $lock;

    public function __construct(
        private readonly ConflictGuard $guard = new ConflictGuard,
        private readonly ScheduleResolver $schedule = new ScheduleResolver,
        ?BookingLock $lock = null,
    ) {
        $this->lock = $lock ?? new BookingLock(
            store: config('booking-engine.lock.store'),
            ttlSeconds: (int) config('booking-engine.lock.ttl_seconds', 10),
            waitSeconds: (int) config('booking-engine.lock.wait_seconds', 5),
        );
    }

    public function create(BookingRequest $request): Appointment
    {
        $service = $request->service;
        $provider = $request->provider;
        $start = $request->start->utc();
        $end = $start->addMinutes((int) $service->duration_minutes);

        if ($request->enforceAvailability) {
            $this->assertWithinAvailability($provider, $start, $end);
        }

        $appointment = $this->lock->run(
            $provider->getKey(),
            $start,
            fn (): Appointment => DB::transaction(function () use ($request, $service, $provider, $start, $end): Appointment {
                $this->lockProviderRow($provider);

                $this->guard->assertAvailable(
                    new TimeRange($start, $end),
                    $this->schedule->busyFor($provider, $start, $end),
                    (int) $service->buffer_before_minutes,
                    (int) $service->buffer_after_minutes,
                );

                return Appointment::create([
                    'service_id' => $service->getKey(),
                    'provider_id' => $provider->getKey(),
                    'status' => $request->status,
                    'start_at' => $start,
                    'end_at' => $end,
                    'timezone' => $request->timezone,
                    'client_name' => $request->clientName,
                    'client_email' => $request->clientEmail,
                    'client_phone' => $request->clientPhone,
                    'notes' => $request->notes,
                    'price' => $service->price,
                ]);
            }),
        );

        event(new AppointmentCreated($appointment));

        return $appointment;
    }

    public function reschedule(
        Appointment $appointment,
        CarbonImmutable $newStart,
        ?string $reason = null,
        bool $enforceAvailability = true,
    ): Appointment {
        $service = $appointment->service;
        $provider = $appointment->provider;
        $newStart = $newStart->utc();
        $newEnd = $newStart->addMinutes((int) $service->duration_minutes);
        $previousStart = CarbonImmutable::parse($appointment->start_at);

        if ($enforceAvailability) {
            $this->assertWithinAvailability($provider, $newStart, $newEnd);
        }

        $updated = $this->lock->run(
            $provider->getKey(),
            $newStart,
            fn (): Appointment => DB::transaction(function () use ($appointment, $service, $provider, $newStart, $newEnd): Appointment {
                $this->lockProviderRow($provider);

                $this->guard->assertAvailable(
                    new TimeRange($newStart, $newEnd),
                    $this->schedule->busyFor($provider, $newStart, $newEnd, excludeAppointmentId: $appointment->getKey()),
                    (int) $service->buffer_before_minutes,
                    (int) $service->buffer_after_minutes,
                );

                $appointment->update([
                    'start_at' => $newStart,
                    'end_at' => $newEnd,
                ]);

                return $appointment->refresh();
            }),
        );

        event(new AppointmentRescheduled($updated, $previousStart, $reason));

        return $updated;
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        $appointment = $this->transition($appointment, AppointmentStatus::Cancelled);

        event(new AppointmentCancelled($appointment, $reason));

        return $appointment;
    }

    public function confirm(Appointment $appointment): Appointment
    {
        return $this->transition($appointment, AppointmentStatus::Confirmed);
    }

    public function complete(Appointment $appointment): Appointment
    {
        return $this->transition($appointment, AppointmentStatus::Completed);
    }

    public function markNoShow(Appointment $appointment): Appointment
    {
        return $this->transition($appointment, AppointmentStatus::NoShow);
    }

    public function isWithinAvailability(Provider $provider, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        foreach ($this->schedule->windowsFor($provider, $start) as $window) {
            if ($window->start->lessThanOrEqualTo($start) && $window->end->greaterThanOrEqualTo($end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws SlotUnavailableException
     */
    private function assertWithinAvailability(Provider $provider, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (! $this->isWithinAvailability($provider, $start, $end)) {
            throw new SlotUnavailableException(sprintf(
                'The requested time [%s, %s) is outside the provider availability.',
                $start->toIso8601String(),
                $end->toIso8601String(),
            ));
        }
    }

    private function transition(Appointment $appointment, AppointmentStatus $status): Appointment
    {
        $previous = $appointment->status;

        if ($previous === $status) {
            return $appointment;
        }

        $appointment->update(['status' => $status]);
        $appointment->refresh();

        event(new AppointmentStatusChanged($appointment, $previous, $status));

        return $appointment;
    }

    private function lockProviderRow(Provider $provider): void
    {
        Provider::query()
            ->whereKey($provider->getKey())
            ->lockForUpdate()
            ->first();
    }
}
