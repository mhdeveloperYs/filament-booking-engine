<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use MHStudio\FilamentBookingEngine\Enums\Weekday;
use MHStudio\FilamentBookingEngine\Livewire\BookingWizard;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;

uses(RefreshDatabase::class);

/**
 * @return array{0: Service, 1: Provider}
 */
function bookingFixtures(): array
{
    $service = Service::create([
        'name' => 'Consultation',
        'duration_minutes' => 60,
        'slot_interval_minutes' => 30,
        'is_active' => true,
    ]);

    $provider = Provider::create([
        'name' => 'Dr. Amrani',
        'timezone' => 'UTC',
        'is_active' => true,
    ]);

    $provider->availabilityRules()->create([
        'weekday' => Weekday::Monday,
        'start_time' => '09:00:00',
        'end_time' => '11:00:00',
        'is_active' => true,
    ]);

    return [$service, $provider];
}

it('is registered as a Livewire component', function (): void {
    Livewire::test('booking-engine.booking-wizard')->assertOk();
});

it('offers slots for the chosen service, provider and date', function (): void {
    [$service, $provider] = bookingFixtures();

    $wizard = Livewire::test(BookingWizard::class)
        ->set('serviceId', $service->id)
        ->set('providerId', $provider->id)
        ->set('date', '2026-10-12')
        ->instance();

    // 09:00-11:00, 60 min duration, 30 min interval => 09:00, 09:30, 10:00
    expect($wizard->getSlotsProperty())->toHaveCount(3);
});

it('books an appointment end to end', function (): void {
    [$service, $provider] = bookingFixtures();

    $component = Livewire::test(BookingWizard::class)
        ->set('serviceId', $service->id)
        ->set('providerId', $provider->id)
        ->set('date', '2026-10-12');

    $firstSlot = $component->instance()->getSlotsProperty()[0]->start()->toIso8601String();

    $component
        ->call('selectSlot', $firstSlot)
        ->set('clientName', 'Sara')
        ->set('clientEmail', 'sara@example.test')
        ->call('book')
        ->assertHasNoErrors();

    expect(Appointment::count())->toBe(1)
        ->and($component->get('confirmationMessage'))->not->toBeNull();
});

it('shows an error when the chosen slot was taken meanwhile', function (): void {
    [$service, $provider] = bookingFixtures();

    $first = Livewire::test(BookingWizard::class)
        ->set('serviceId', $service->id)
        ->set('providerId', $provider->id)
        ->set('date', '2026-10-12');

    $slot = $first->instance()->getSlotsProperty()[0]->start()->toIso8601String();

    $first->call('selectSlot', $slot)
        ->set('clientName', 'Sara')
        ->set('clientEmail', 'sara@example.test')
        ->call('book')
        ->assertHasNoErrors();

    Livewire::test(BookingWizard::class)
        ->set('serviceId', $service->id)
        ->set('providerId', $provider->id)
        ->set('date', '2026-10-12')
        ->call('selectSlot', $slot)
        ->set('clientName', 'Omar')
        ->set('clientEmail', 'omar@example.test')
        ->call('book')
        ->assertHasErrors('slotStart');

    expect(Appointment::count())->toBe(1);
});

it('requires the client name and email', function (): void {
    [$service, $provider] = bookingFixtures();

    $component = Livewire::test(BookingWizard::class)
        ->set('serviceId', $service->id)
        ->set('providerId', $provider->id)
        ->set('date', '2026-10-12');

    $slot = $component->instance()->getSlotsProperty()[0]->start()->toIso8601String();

    $component
        ->call('selectSlot', $slot)
        ->call('book')
        ->assertHasErrors(['clientName', 'clientEmail']);

    expect(Appointment::count())->toBe(0);
});
