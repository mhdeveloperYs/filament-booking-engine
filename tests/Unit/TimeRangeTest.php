<?php

declare(strict_types=1);

use MHStudio\FilamentBookingEngine\Data\TimeRange;

it('detects overlap when ranges intersect', function (): void {
    expect(
        tr('2026-01-01 09:00', '2026-01-01 10:00')->overlaps(tr('2026-01-01 09:30', '2026-01-01 10:30')),
    )->toBeTrue();
});

it('does not overlap when ranges are adjacent', function (): void {
    expect(
        tr('2026-01-01 09:00', '2026-01-01 10:00')->overlaps(tr('2026-01-01 10:00', '2026-01-01 11:00')),
    )->toBeFalse();
});

it('does not overlap when ranges are disjoint', function (): void {
    expect(
        tr('2026-01-01 09:00', '2026-01-01 10:00')->overlaps(tr('2026-01-01 11:00', '2026-01-01 12:00')),
    )->toBeFalse();
});

it('overlaps when one range contains the other', function (): void {
    expect(
        tr('2026-01-01 09:00', '2026-01-01 12:00')->overlaps(tr('2026-01-01 10:00', '2026-01-01 11:00')),
    )->toBeTrue();
});

it('overlaps when ranges are identical', function (): void {
    $range = tr('2026-01-01 09:00', '2026-01-01 10:00');

    expect($range->overlaps($range))->toBeTrue();
});

it('treats contains() as inclusive of start and exclusive of end', function (): void {
    $range = tr('2026-01-01 09:00', '2026-01-01 10:00');

    expect($range->contains(utc('2026-01-01 09:00')))->toBeTrue()
        ->and($range->contains(utc('2026-01-01 09:30')))->toBeTrue()
        ->and($range->contains(utc('2026-01-01 10:00')))->toBeFalse();
});

it('computes the duration in minutes', function (): void {
    expect(tr('2026-01-01 09:00', '2026-01-01 10:30')->durationInMinutes())->toBe(90);
});

it('expands a range by the given buffers', function (): void {
    $expanded = tr('2026-01-01 09:00', '2026-01-01 10:00')->expand(15, 30);

    expect($expanded->start->format('H:i'))->toBe('08:45')
        ->and($expanded->end->format('H:i'))->toBe('10:30');
});

it('rejects a range whose end precedes its start', function (): void {
    new TimeRange(utc('2026-01-01 10:00'), utc('2026-01-01 09:00'));
})->throws(InvalidArgumentException::class);
