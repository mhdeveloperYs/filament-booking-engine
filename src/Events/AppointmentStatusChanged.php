<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Events;

use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Models\Appointment;

final readonly class AppointmentStatusChanged
{
    public function __construct(
        public Appointment $appointment,
        public AppointmentStatus $previous,
        public AppointmentStatus $current,
    ) {}
}
