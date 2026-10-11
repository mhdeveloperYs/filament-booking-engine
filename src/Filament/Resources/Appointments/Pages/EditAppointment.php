<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\Pages;

use Filament\Resources\Pages\EditRecord;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\AppointmentResource;

final class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;
}
