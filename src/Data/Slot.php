<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Data;

use Carbon\CarbonImmutable;

/** A candidate slot and its availability verdict. */
final readonly class Slot
{
    public const REASON_PAST = 'past';

    public const REASON_NOTICE = 'notice';

    public const REASON_TOO_FAR = 'too_far';

    public const REASON_BOOKED = 'booked';

    public function __construct(
        public TimeRange $range,
        public bool $available,
        public ?string $reason = null,
    ) {}

    public function start(): CarbonImmutable
    {
        return $this->range->start;
    }

    public function end(): CarbonImmutable
    {
        return $this->range->end;
    }

    /**
     * @return array{start: string, end: string, available: bool, reason: string|null}
     */
    public function toArray(): array
    {
        return [
            'start' => $this->start()->toIso8601String(),
            'end' => $this->end()->toIso8601String(),
            'available' => $this->available,
            'reason' => $this->reason,
        ];
    }
}
