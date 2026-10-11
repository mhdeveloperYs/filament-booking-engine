<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Booking;

use Carbon\CarbonImmutable;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;

/**
 * Input for a new booking. Everything the manager needs to validate and persist.
 */
final readonly class BookingRequest
{
    public function __construct(
        public Service $service,
        public Provider $provider,
        public CarbonImmutable $start,
        public string $clientName,
        public ?string $clientEmail = null,
        public ?string $clientPhone = null,
        public ?string $notes = null,
        public string $timezone = 'UTC',
        public AppointmentStatus $status = AppointmentStatus::Confirmed,
        public bool $enforceAvailability = true,
    ) {}
}
