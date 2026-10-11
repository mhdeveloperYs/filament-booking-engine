<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine;

use Filament\Contracts\Plugin;
use Filament\Panel;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\AppointmentResource;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\ProviderResource;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\ServiceResource;
use MHStudio\FilamentBookingEngine\Filament\Widgets\UpcomingAppointmentsWidget;

/**
 * Filament panel plugin.
 */
final class BookingEnginePlugin implements Plugin
{
    public function getId(): string
    {
        return 'booking-engine';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ServiceResource::class,
                ProviderResource::class,
                AppointmentResource::class,
            ])
            ->widgets([
                UpcomingAppointmentsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        /** @var static $plugin */
        $plugin = app(self::class);

        return $plugin;
    }
}
