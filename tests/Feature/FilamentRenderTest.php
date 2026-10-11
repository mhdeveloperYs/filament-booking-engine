<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Enums\Weekday;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\Pages\ListAppointments;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\Pages\ListProviders;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages\CreateService;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages\ListServices;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

it('renders the services list page', function (): void {
    Service::create(['name' => 'Consultation', 'duration_minutes' => 60]);

    Livewire::test(ListServices::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Service::all())
        ->assertSee('Consultation');
});

it('creates a service through the form', function (): void {
    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Deep tissue',
            'duration_minutes' => 45,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Service::where('name', 'Deep tissue')->exists())->toBeTrue();
});

it('renders the providers list page', function (): void {
    Provider::create(['name' => 'Dr. Amrani', 'timezone' => 'Africa/Casablanca']);

    Livewire::test(ListProviders::class)
        ->assertOk()
        ->assertSee('Dr. Amrani');
});

it('renders the appointments list page', function (): void {
    $service = Service::create(['name' => 'Consultation', 'duration_minutes' => 60]);
    $provider = Provider::create(['name' => 'Dr. Amrani']);

    Appointment::create([
        'service_id' => $service->id,
        'provider_id' => $provider->id,
        'status' => AppointmentStatus::Confirmed,
        'start_at' => '2026-03-02 09:00:00',
        'end_at' => '2026-03-02 10:00:00',
        'client_name' => 'Sara',
    ]);

    Livewire::test(ListAppointments::class)
        ->assertOk()
        ->assertSee('Sara');
});

it('cancels an appointment through the table action', function (): void {
    $service = Service::create(['name' => 'Consultation', 'duration_minutes' => 60]);
    $provider = Provider::create(['name' => 'Dr. Amrani']);

    $appointment = Appointment::create([
        'service_id' => $service->id,
        'provider_id' => $provider->id,
        'status' => AppointmentStatus::Confirmed,
        'start_at' => '2026-03-02 09:00:00',
        'end_at' => '2026-03-02 10:00:00',
        'client_name' => 'Sara',
    ]);

    Livewire::test(ListAppointments::class)
        ->callTableAction('cancel', $appointment)
        ->assertHasNoTableActionErrors();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('keeps weekday availability rules linked to a provider', function (): void {
    $provider = Provider::create(['name' => 'Dr. Amrani']);

    $provider->availabilityRules()->create([
        'weekday' => Weekday::Monday,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'is_active' => true,
    ]);

    expect($provider->availabilityRules()->count())->toBe(1);
});
