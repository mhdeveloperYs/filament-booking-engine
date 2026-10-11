<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Widgets;

use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Models\Appointment;

final class UpcomingAppointmentsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $now = CarbonImmutable::now();

        return [
            Stat::make('Upcoming', Appointment::active()->where('start_at', '>=', $now)->count())
                ->description('Active appointments ahead'),
            Stat::make('Today', Appointment::active()
                ->whereBetween('start_at', [$now->startOfDay(), $now->endOfDay()])
                ->count())
                ->description('Active appointments today'),
            Stat::make('Confirmed', Appointment::where('status', AppointmentStatus::Confirmed)->count())
                ->description('All confirmed'),
        ];
    }
}
