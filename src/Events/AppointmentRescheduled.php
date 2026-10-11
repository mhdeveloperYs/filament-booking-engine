<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Events;

use Carbon\CarbonImmutable;
use MHStudio\FilamentBookingEngine\Models\Appointment;

final readonly class AppointmentRescheduled
{
    public function __construct(
        public Appointment $appointment,
        public CarbonImmutable $previousStart,
        public ?string $reason = null,
    ) {}
}
