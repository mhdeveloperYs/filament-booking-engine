<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Tests\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use MHStudio\FilamentBookingEngine\BookingEnginePlugin;

/**
 * A minimal Filament panel for tests, registered as a provider so that Filament
 * registers its routes at boot.
 */
final class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(BookingEnginePlugin::make());
    }
}
