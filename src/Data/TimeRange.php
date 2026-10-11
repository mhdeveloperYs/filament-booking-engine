<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Data;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/** Half-open interval: [start, end). Adjacent ranges do not overlap. */
final readonly class TimeRange
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {
        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('TimeRange end must not be before start.');
        }
    }

    public static function make(
        string|CarbonImmutable $start,
        string|CarbonImmutable $end,
        ?string $timezone = null,
    ): self {
        return new self(
            $start instanceof CarbonImmutable ? $start : CarbonImmutable::parse($start, $timezone),
            $end instanceof CarbonImmutable ? $end : CarbonImmutable::parse($end, $timezone),
        );
    }

    public static function fromMinutes(
        string|CarbonImmutable $start,
        int $minutes,
        ?string $timezone = null,
    ): self {
        $startAt = $start instanceof CarbonImmutable ? $start : CarbonImmutable::parse($start, $timezone);

        return new self($startAt, $startAt->addMinutes($minutes));
    }

    public function durationInMinutes(): int
    {
        return (int) $this->start->diffInMinutes($this->end);
    }

    public function overlaps(self $other): bool
    {
        return $this->start->lessThan($other->end)
            && $other->start->lessThan($this->end);
    }

    public function contains(CarbonImmutable $moment): bool
    {
        return $moment->greaterThanOrEqualTo($this->start)
            && $moment->lessThan($this->end);
    }

    /** Widen by buffers before an overlap check. */
    public function expand(int $beforeMinutes = 0, int $afterMinutes = 0): self
    {
        return new self(
            $this->start->subMinutes(max(0, $beforeMinutes)),
            $this->end->addMinutes(max(0, $afterMinutes)),
        );
    }

    public function equals(self $other): bool
    {
        return $this->start->equalTo($other->start)
            && $this->end->equalTo($other->end);
    }

    /**
     * @return array{start: string, end: string}
     */
    public function toArray(): array
    {
        return [
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
        ];
    }
}
