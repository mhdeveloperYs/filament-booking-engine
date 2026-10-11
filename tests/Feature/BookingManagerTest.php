<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use MHStudio\FilamentBookingEngine\Booking\BookingManager;
use MHStudio\FilamentBookingEngine\Booking\BookingRequest;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Enums\Weekday;
use MHStudio\FilamentBookingEngine\Events\AppointmentCancelled;
use MHStudio\FilamentBookingEngine\Events\AppointmentCreated;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use MHStudio\FilamentBookingEngine\Models\AvailabilityException;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;
use MHStudio\FilamentBookingEngine\Schedule\ScheduleResolver;

uses(RefreshDatabase::class);

// 2026-03-02 is a Monday.
const MONDAY = '2026-03-02';

function bookingService(array $attributes = []): Service
{
    return Service::create(array_merge([
        'name' => 'Consultation',
        'duration_minutes' => 60,
    ], $attributes));
}

function bookingProvider(array $attributes = []): Provider
{
    return Provider::create(array_merge([
        'name' => 'Dr. Amrani',
        'timezone' => 'UTC',
    ], $attributes));
}

function openOnMonday(Provider $provider, string $start = '09:00:00', string $end = '12:00:00'): void
{
    $provider->availabilityRules()->create([
        'weekday' => Weekday::Monday,
        'start_time' => $start,
        'end_time' => $end,
        'is_active' => true,
    ]);
}

function manager(): BookingManager
{
    return app(BookingManager::class);
}

function book(Service $service, Provider $provider, string $start): Appointment
{
    return manager()->create(new BookingRequest(
        service: $service,
        provider: $provider,
        start: utc($start),
        clientName: 'Sara',
        clientEmail: 'sara@example.test',
        timezone: 'UTC',
    ));
}

it('creates an appointment inside the availability window', function (): void {
    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    $appointment = book($service, $provider, MONDAY.' 09:00');

    expect($appointment->exists)->toBeTrue()
        ->and($appointment->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->start_at->format('H:i'))->toBe('09:00')
        ->and($appointment->end_at->format('H:i'))->toBe('10:00')
        ->and(Appointment::count())->toBe(1);
});

it('rejects a booking outside the availability window', function (): void {
    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    expect(fn () => book($service, $provider, MONDAY.' 13:00'))
        ->toThrow(SlotUnavailableException::class);

    expect(Appointment::count())->toBe(0);
});

it('rejects a booking that overlaps an existing appointment', function (): void {
    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    book($service, $provider, MONDAY.' 09:00');

    expect(fn () => book($service, $provider, MONDAY.' 09:30'))
        ->toThrow(SlotUnavailableException::class);

    expect(Appointment::count())->toBe(1);
});

it('allows back-to-back bookings when there is no buffer', function (): void {
    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    book($service, $provider, MONDAY.' 09:00');
    book($service, $provider, MONDAY.' 10:00');

    expect(Appointment::count())->toBe(2);
});

it('enforces the service buffer between bookings', function (): void {
    $service = bookingService(['buffer_after_minutes' => 15]);
    $provider = bookingProvider();
    openOnMonday($provider);

    book($service, $provider, MONDAY.' 09:00');

    expect(fn () => book($service, $provider, MONDAY.' 10:00'))
        ->toThrow(SlotUnavailableException::class);
});

it('reschedules an appointment to a free slot', function (): void {
    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    $appointment = book($service, $provider, MONDAY.' 09:00');

    $moved = manager()->reschedule($appointment, utc(MONDAY.' 10:00'));

    expect($moved->start_at->format('H:i'))->toBe('10:00')
        ->and($moved->end_at->format('H:i'))->toBe('11:00');
});

it('rejects a reschedule into an occupied slot', function (): void {
    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    book($service, $provider, MONDAY.' 09:00');
    $second = book($service, $provider, MONDAY.' 10:00');

    expect(fn () => manager()->reschedule($second, utc(MONDAY.' 09:30')))
        ->toThrow(SlotUnavailableException::class);
});

it('frees the slot when an appointment is cancelled', function (): void {
    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    $appointment = book($service, $provider, MONDAY.' 09:00');
    manager()->cancel($appointment, 'Changed plans');

    $rebooked = book($service, $provider, MONDAY.' 09:00');

    expect($rebooked->start_at->format('H:i'))->toBe('09:00')
        ->and(Appointment::active()->count())->toBe(1)
        ->and(Appointment::count())->toBe(2);
});

it('dispatches events on create and cancel', function (): void {
    Event::fake([AppointmentCreated::class, AppointmentCancelled::class]);

    $service = bookingService();
    $provider = bookingProvider();
    openOnMonday($provider);

    $appointment = book($service, $provider, MONDAY.' 09:00');
    manager()->cancel($appointment);

    Event::assertDispatched(AppointmentCreated::class);
    Event::assertDispatched(AppointmentCancelled::class);
});

it('builds availability windows from weekly rules', function (): void {
    $provider = bookingProvider();
    openOnMonday($provider, '09:00:00', '12:00:00');

    $windows = (new ScheduleResolver)->windowsFor($provider, utc(MONDAY.' 09:00'));

    expect($windows)->toHaveCount(1)
        ->and($windows[0]->start->format('H:i'))->toBe('09:00')
        ->and($windows[0]->end->format('H:i'))->toBe('12:00');
});

it('treats a closed date exception as no availability', function (): void {
    $provider = bookingProvider();
    openOnMonday($provider);

    AvailabilityException::create([
        'provider_id' => $provider->id,
        'date' => MONDAY,
        'is_closed' => true,
    ]);

    expect((new ScheduleResolver)->windowsFor($provider, utc(MONDAY.' 09:00')))->toBeEmpty();
});

it('replaces availability with a custom-window exception', function (): void {
    $provider = bookingProvider();
    openOnMonday($provider);

    AvailabilityException::create([
        'provider_id' => $provider->id,
        'date' => MONDAY,
        'is_closed' => false,
        'windows' => [['start' => '10:00:00', 'end' => '11:00:00']],
    ]);

    $windows = (new ScheduleResolver)->windowsFor($provider, utc(MONDAY.' 09:00'));

    expect($windows)->toHaveCount(1)
        ->and($windows[0]->start->format('H:i'))->toBe('10:00')
        ->and($windows[0]->end->format('H:i'))->toBe('11:00');
});

it('expands busy intervals by the owning service buffers', function (): void {
    $service = bookingService(['buffer_before_minutes' => 10, 'buffer_after_minutes' => 20]);
    $provider = bookingProvider();
    openOnMonday($provider);

    book($service, $provider, MONDAY.' 09:00');

    $busy = (new ScheduleResolver)->busyFor(
        $provider,
        utc(MONDAY.' 09:00'),
        utc(MONDAY.' 10:00'),
    );

    expect($busy)->toHaveCount(1)
        ->and($busy[0]->start->format('H:i'))->toBe('08:50')
        ->and($busy[0]->end->format('H:i'))->toBe('10:20');
});
