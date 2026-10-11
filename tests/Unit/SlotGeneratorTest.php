<?php

declare(strict_types=1);

use MHStudio\FilamentBookingEngine\Engine\SlotGenerator;

function generator(): SlotGenerator
{
    return new SlotGenerator;
}

it('generates slots across a window without overflowing the end', function (): void {
    $slots = generator()->generate([tr('2026-01-01 09:00', '2026-01-01 12:00')], 60, 30);

    expect($slots)->toHaveCount(5)
        ->and($slots[0]->start->format('H:i'))->toBe('09:00')
        ->and($slots[4]->start->format('H:i'))->toBe('11:00')
        ->and($slots[4]->end->format('H:i'))->toBe('12:00');
});

it('generates exactly one slot when the duration equals the window', function (): void {
    $slots = generator()->generate([tr('2026-01-01 09:00', '2026-01-01 12:00')], 180, 30);

    expect($slots)->toHaveCount(1);
});

it('generates no slots when the duration exceeds the window', function (): void {
    expect(generator()->generate([tr('2026-01-01 09:00', '2026-01-01 12:00')], 240, 30))->toBeEmpty();
});

it('generates slots for multiple windows without crossing the gap', function (): void {
    $slots = generator()->generate([
        tr('2026-01-01 09:00', '2026-01-01 10:00'),
        tr('2026-01-01 14:00', '2026-01-01 15:00'),
    ], 60, 30);

    expect($slots)->toHaveCount(2)
        ->and($slots[0]->start->format('H:i'))->toBe('09:00')
        ->and($slots[1]->start->format('H:i'))->toBe('14:00');
});

it('emits non-overlapping slots when the interval exceeds the duration', function (): void {
    $slots = generator()->generate([tr('2026-01-01 09:00', '2026-01-01 12:00')], 30, 60);

    expect($slots)->toHaveCount(3)
        ->and($slots[0]->start->format('H:i'))->toBe('09:00')
        ->and($slots[1]->start->format('H:i'))->toBe('10:00')
        ->and($slots[2]->start->format('H:i'))->toBe('11:00');
});

it('emits overlapping candidates when the interval is smaller than the duration', function (): void {
    $slots = generator()->generate([tr('2026-01-01 09:00', '2026-01-01 11:00')], 60, 15);

    expect($slots)->toHaveCount(5)
        ->and($slots[0]->start->format('H:i'))->toBe('09:00')
        ->and($slots[1]->start->format('H:i'))->toBe('09:15');
});

it('returns nothing for a zero or negative duration', function (): void {
    expect(generator()->generate([tr('2026-01-01 09:00', '2026-01-01 12:00')], 0, 30))->toBeEmpty()
        ->and(generator()->generate([tr('2026-01-01 09:00', '2026-01-01 12:00')], -10, 30))->toBeEmpty();
});

it('falls back to the duration when the interval is not positive', function (): void {
    $slots = generator()->generate([tr('2026-01-01 09:00', '2026-01-01 12:00')], 60, 0);

    expect($slots)->toHaveCount(3)
        ->and($slots[0]->start->format('H:i'))->toBe('09:00')
        ->and($slots[1]->start->format('H:i'))->toBe('10:00')
        ->and($slots[2]->start->format('H:i'))->toBe('11:00');
});

it('sorts slots by start time regardless of window order', function (): void {
    $slots = generator()->generate([
        tr('2026-01-01 14:00', '2026-01-01 15:00'),
        tr('2026-01-01 09:00', '2026-01-01 10:00'),
    ], 60, 30);

    expect($slots[0]->start->format('H:i'))->toBe('09:00')
        ->and($slots[1]->start->format('H:i'))->toBe('14:00');
});
