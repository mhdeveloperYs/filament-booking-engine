<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Data;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * A half-open time interval: [start, end).
 *
 * Half-open semantics are the whole point: two ranges that merely touch at an
 * edge (09:00-10:00 and 10:00-11:00) do NOT overlap, which is what makes
 * back-to-back appointments legal. Every overlap decision in the engine flows
 * through this class.
 */
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

    /**
     * Strict, half-open overlap test.
     */
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

    /**
     * Widen the interval on both sides. Used to apply buffers before overlap checks.
     */
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
