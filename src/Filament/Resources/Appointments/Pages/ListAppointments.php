<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\ListRecords;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\AppointmentResource;

final class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;
}
