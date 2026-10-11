<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use MHStudio\FilamentBookingEngine\Booking\BookingManager;
use MHStudio\FilamentBookingEngine\Concurrency\BookingLock;
use MHStudio\FilamentBookingEngine\Engine\ConflictGuard;
use MHStudio\FilamentBookingEngine\Livewire\BookingWizard;
use MHStudio\FilamentBookingEngine\Schedule\ScheduleResolver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class BookingEngineServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-booking-engine';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('booking-engine')
            ->hasViews()
            ->hasMigrations([
                'create_booking_services_table',
                'create_booking_providers_table',
                'create_booking_availability_rules_table',
                'create_booking_availability_exceptions_table',
                'create_booking_appointments_table',
            ])
            ->runsMigrations();
    }

    public function packageBooted(): void
    {
        Livewire::component('booking-engine.booking-wizard', BookingWizard::class);

        if (config('booking-engine.public.route_enabled', false)) {
            Route::middleware(config('booking-engine.public.route_middleware', ['web']))
                ->get(
                    config('booking-engine.public.route_path', 'book'),
                    BookingWizard::class,
                )
                ->name(config('booking-engine.public.route_name', 'booking-engine.book'));
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ConflictGuard::class);
        $this->app->singleton(ScheduleResolver::class);

        $this->app->singleton(BookingLock::class, static fn (): BookingLock => new BookingLock(
            store: config('booking-engine.lock.store'),
            ttlSeconds: (int) config('booking-engine.lock.ttl_seconds', 10),
            waitSeconds: (int) config('booking-engine.lock.wait_seconds', 5),
        ));

        $this->app->singleton(BookingManager::class);
    }
}
