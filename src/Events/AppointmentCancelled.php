<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Events;

use MHStudio\FilamentBookingEngine\Models\Appointment;

final readonly class AppointmentCancelled
{
    public function __construct(
        public Appointment $appointment,
        public ?string $reason = null,
    ) {}
}
