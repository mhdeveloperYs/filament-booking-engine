<?php

declare(strict_types=1);

use MHStudio\FilamentBookingEngine\Engine\ConflictGuard;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;

it('does not conflict with an adjacent interval', function (): void {
    $guard = new ConflictGuard;

    expect($guard->conflicts(tr('2026-01-01 09:00', '2026-01-01 10:00'), [
        tr('2026-01-01 10:00', '2026-01-01 11:00'),
    ]))->toBeFalse();
});

it('conflicts with an overlapping interval', function (): void {
    $guard = new ConflictGuard;

    expect($guard->conflicts(tr('2026-01-01 09:00', '2026-01-01 10:00'), [
        tr('2026-01-01 09:30', '2026-01-01 11:00'),
    ]))->toBeTrue();
});

it('turns an adjacent interval into a conflict when a before-buffer is applied', function (): void {
    $guard = new ConflictGuard;
    $busy = [tr('2026-01-01 09:00', '2026-01-01 09:30')];
    $candidate = tr('2026-01-01 09:30', '2026-01-01 10:00');

    expect($guard->conflicts($candidate, $busy, 0, 0))->toBeFalse()
        ->and($guard->conflicts($candidate, $busy, 15, 0))->toBeTrue();
});

it('throws SlotUnavailableException from assertAvailable on a conflict', function (): void {
    $guard = new ConflictGuard;

    expect(fn () => $guard->assertAvailable(tr('2026-01-01 09:00', '2026-01-01 10:00'), [
        tr('2026-01-01 09:30', '2026-01-01 11:00'),
    ]))->toThrow(SlotUnavailableException::class);
});

it('returns void from assertAvailable when the slot is free', function (): void {
    $guard = new ConflictGuard;

    $guard->assertAvailable(tr('2026-01-01 09:00', '2026-01-01 10:00'), [
        tr('2026-01-01 10:00', '2026-01-01 11:00'),
    ]);
})->throwsNoExceptions();
