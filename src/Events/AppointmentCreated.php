<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Events;

use MHStudio\FilamentBookingEngine\Models\Appointment;

final readonly class AppointmentCreated
{
    public function __construct(
        public Appointment $appointment,
    ) {}
}
