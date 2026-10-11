<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    /** Statuses that occupy a slot. */
    public function isActive(): bool
    {
        return match ($this) {
            self::Requested, self::Confirmed => true,
            self::Cancelled, self::Completed, self::NoShow => false,
        };
    }
}
