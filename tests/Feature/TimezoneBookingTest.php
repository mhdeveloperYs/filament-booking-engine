<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MHStudio\FilamentBookingEngine\Booking\BookingManager;
use MHStudio\FilamentBookingEngine\Booking\BookingRequest;
use MHStudio\FilamentBookingEngine\Data\AvailabilityContext;
use MHStudio\FilamentBookingEngine\Engine\AvailabilityEngine;
use MHStudio\FilamentBookingEngine\Enums\Weekday;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;
use MHStudio\FilamentBookingEngine\Schedule\ScheduleResolver;

uses(RefreshDatabase::class);

function tzProvider(string $timezone = 'Europe/London'): Provider
{
    return Provider::create(['name' => 'Dr. Amrani', 'timezone' => $timezone, 'is_active' => true]);
}

function tzService(array $attributes = []): Service
{
    return Service::create(array_merge([
        'name' => 'Consultation',
        'duration_minutes' => 60,
        'slot_interval_minutes' => 60,
        'is_active' => true,
    ], $attributes));
}

it('shortens the availability window on a spring-forward day', function (): void {
    $provider = tzProvider();
    $provider->availabilityRules()->create([
        'weekday' => Weekday::Sunday,
        'start_time' => '00:00:00',
        'end_time' => '04:00:00',
        'is_active' => true,
    ]);

    $windows = (new ScheduleResolver)->windowsFor($provider, CarbonImmutable::parse('2026-03-29 09:00', 'Europe/London'));

    expect($windows)->toHaveCount(1)
        ->and($windows[0]->durationInMinutes())->toBe(180);
});

it('lengthens the availability window on a fall-back day', function (): void {
    $provider = tzProvider();
    $provider->availabilityRules()->create([
        'weekday' => Weekday::Sunday,
        'start_time' => '00:00:00',
        'end_time' => '04:00:00',
        'is_active' => true,
    ]);

    $windows = (new ScheduleResolver)->windowsFor($provider, CarbonImmutable::parse('2026-10-25 09:00', 'Europe/London'));

    expect($windows)->toHaveCount(1)
        ->and($windows[0]->durationInMinutes())->toBe(300);
});

it('offers distinct real-duration slots across a spring-forward window', function (): void {
    $provider = tzProvider();
    $provider->availabilityRules()->create([
        'weekday' => Weekday::Sunday,
        'start_time' => '00:00:00',
        'end_time' => '04:00:00',
        'is_active' => true,
    ]);

    $windows = (new ScheduleResolver)->windowsFor($provider, CarbonImmutable::parse('2026-03-29 09:00', 'Europe/London'));

    $context = new AvailabilityContext(
        windows: $windows,
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        now: CarbonImmutable::parse('2026-03-01 00:00', 'UTC'),
        timezone: 'Europe/London',
    );

    $slots = (new AvailabilityEngine)->availableSlots($context);

    $starts = array_map(static fn ($slot): int => $slot->start()->getTimestamp(), $slots);

    expect($slots)->toHaveCount(3)
        ->and(count(array_unique($starts)))->toBe(3);

    foreach ($slots as $slot) {
        expect($slot->range->durationInMinutes())->toBe(60);
    }
});

it('stores the booking in UTC when the provider timezone is not UTC', function (): void {
    $provider = tzProvider();
    $provider->availabilityRules()->create([
        'weekday' => Weekday::Wednesday,
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
        'is_active' => true,
    ]);
    $service = tzService();

    // 09:00 in London during BST (UTC+1) is 08:00 UTC.
    $appointment = app(BookingManager::class)->create(new BookingRequest(
        service: $service,
        provider: $provider,
        start: CarbonImmutable::parse('2026-07-01 09:00', 'Europe/London'),
        clientName: 'Sara',
        timezone: 'Europe/London',
    ));

    expect($appointment->start_at->utc()->format('H:i'))->toBe('08:00')
        ->and($appointment->end_at->utc()->format('H:i'))->toBe('09:00')
        ->and($appointment->timezone)->toBe('Europe/London');
});

it('rejects a booking outside the window in the provider timezone', function (): void {
    $provider = tzProvider();
    $provider->availabilityRules()->create([
        'weekday' => Weekday::Wednesday,
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
        'is_active' => true,
    ]);
    $service = tzService();

    // 07:00 London is before the 08:00 window.
    expect(fn () => app(BookingManager::class)->create(new BookingRequest(
        service: $service,
        provider: $provider,
        start: CarbonImmutable::parse('2026-07-01 07:00', 'Europe/London'),
        clientName: 'Sara',
    )))->toThrow(SlotUnavailableException::class);

    expect(Appointment::count())->toBe(0);
});
