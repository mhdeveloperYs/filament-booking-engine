<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\CreateRecord;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\AppointmentResource;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
