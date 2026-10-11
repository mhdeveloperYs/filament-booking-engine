<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Data;

use Carbon\CarbonImmutable;

/**
 * The complete, framework-free input to the AvailabilityEngine.
 *
 * The persistence layer is responsible for turning models (rules, exceptions,
 * existing appointments) into these value objects. The engine itself never
 * touches the database, which is what makes it deterministic and testable.
 */
final readonly class AvailabilityContext
{
    /**
     * @param  TimeRange[]  $windows  Availability windows (in the provider timezone)
     * @param  TimeRange[]  $busy  Already-occupied intervals (past, buffers applied by engine)
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
