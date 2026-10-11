<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Data;

use Carbon\CarbonImmutable;

/** Input for the availability engine. */
final readonly class AvailabilityContext
{
    /**
     * @param  TimeRange[]  $windows  Availability windows, in the provider timezone
     * @param  TimeRange[]  $busy  Occupied intervals
     */
    public function __construct(
        public array $windows = [],
        public array $busy = [],
        public int $durationMinutes = 30,
        public int $slotIntervalMinutes = 15,
        public int $bufferBeforeMinutes = 0,
        public int $bufferAfterMinutes = 0,
        public int $minimumNoticeMinutes = 0,
        public ?int $maximumAdvanceDays = null,
        public ?CarbonImmutable $now = null,
        public string $timezone = 'UTC',
    ) {}
}
