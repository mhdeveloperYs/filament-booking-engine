<?php

declare(strict_types=1);

use Filament\Panel;
use MHStudio\FilamentBookingEngine\BookingEnginePlugin;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\AppointmentResource;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\ProviderResource;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\ServiceResource;
use MHStudio\FilamentBookingEngine\Filament\Widgets\UpcomingAppointmentsWidget;

it('registers its resources and widgets on a panel', function (): void {
    $panel = Panel::make()->id('admin');

    BookingEnginePlugin::make()->register($panel);

    expect($panel->getResources())
        ->toContain(ServiceResource::class)
        ->toContain(ProviderResource::class)
        ->toContain(AppointmentResource::class);

    expect($panel->getWidgets())->toContain(UpcomingAppointmentsWidget::class);
});

it('exposes the expected pages for each resource', function (): void {
    expect(ServiceResource::getPages())->toHaveKeys(['index', 'create', 'edit'])
        ->and(ProviderResource::getPages())->toHaveKeys(['index', 'create', 'edit'])
        ->and(AppointmentResource::getPages())->toHaveKeys(['index', 'create', 'edit']);
});
