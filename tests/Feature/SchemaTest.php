<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;

uses(RefreshDatabase::class);

it('runs the package migrations', function (): void {
    foreach ([
        'booking_services',
        'booking_providers',
        'booking_availability_rules',
        'booking_availability_exceptions',
        'booking_appointments',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});

it('persists a service, provider and appointment with casts', function (): void {
    $service = Service::create([
        'name' => 'Consultation',
        'duration_minutes' => 60,
        'buffer_after_minutes' => 10,
        'price' => 99.5,
    ]);

    $provider = Provider::create([
        'name' => 'Dr. Amrani',
        'timezone' => 'Africa/Casablanca',
    ]);

    $appointment = Appointment::create([
        'service_id' => $service->id,
        'provider_id' => $provider->id,
        'status' => AppointmentStatus::Confirmed,
        'start_at' => '2026-03-01 09:00:00',
        'end_at' => '2026-03-01 10:00:00',
        'client_name' => 'Sara',
        'client_email' => 'sara@example.test',
    ]);

    expect($appointment->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->service->name)->toBe('Consultation')
        ->and($appointment->provider->name)->toBe('Dr. Amrani')
        ->and($appointment->range()->durationInMinutes())->toBe(60);
});

it('excludes non-active appointments from the active scope', function (): void {
    $service = Service::create(['name' => 'Call']);
    $provider = Provider::create(['name' => 'Agent']);

    foreach ([
        [AppointmentStatus::Confirmed, '2026-03-01 09:00:00', '2026-03-01 10:00:00'],
        [AppointmentStatus::Cancelled, '2026-03-01 11:00:00', '2026-03-01 12:00:00'],
        [AppointmentStatus::NoShow, '2026-03-01 13:00:00', '2026-03-01 14:00:00'],
    ] as [$status, $start, $end]) {
        Appointment::create([
            'service_id' => $service->id,
            'provider_id' => $provider->id,
            'status' => $status,
            'start_at' => $start,
            'end_at' => $end,
            'client_name' => 'Client',
        ]);
    }

    expect(Appointment::active()->count())->toBe(1);
});
