<?php

declare(strict_types=1);

use MHStudio\FilamentBookingEngine\Data\AvailabilityContext;
use MHStudio\FilamentBookingEngine\Data\Slot;
use MHStudio\FilamentBookingEngine\Engine\AvailabilityEngine;

it('marks every slot available when nothing is busy', function (): void {
    $context = new AvailabilityContext(
        windows: [tr('2026-01-01 09:00', '2026-01-01 11:00')],
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        now: utc('2026-01-01 00:00'),
    );

    $slots = (new AvailabilityEngine)->slots($context);

    expect($slots)->toHaveCount(2)
        ->and($slots[0]->available)->toBeTrue()
        ->and($slots[1]->available)->toBeTrue();
});

it('marks slots that overlap a busy interval as booked', function (): void {
    $context = new AvailabilityContext(
        windows: [tr('2026-01-01 09:00', '2026-01-01 12:00')],
        busy: [tr('2026-01-01 10:15', '2026-01-01 10:45')],
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        now: utc('2026-01-01 00:00'),
    );

    $slots = (new AvailabilityEngine)->slots($context);

    expect($slots[0]->available)->toBeTrue()
        ->and($slots[1]->available)->toBeFalse()
        ->and($slots[1]->reason)->toBe(Slot::REASON_BOOKED)
        ->and($slots[2]->available)->toBeTrue();
});

it('applies the after-buffer so an adjacent slot becomes booked', function (): void {
    $busy = [tr('2026-01-01 09:30', '2026-01-01 10:00')];

    $without = new AvailabilityContext(
        windows: [tr('2026-01-01 09:00', '2026-01-01 10:00')],
        busy: $busy,
        durationMinutes: 30,
        slotIntervalMinutes: 30,
        bufferAfterMinutes: 0,
        now: utc('2026-01-01 00:00'),
    );

    $with = new AvailabilityContext(
        windows: [tr('2026-01-01 09:00', '2026-01-01 10:00')],
        busy: $busy,
        durationMinutes: 30,
        slotIntervalMinutes: 30,
        bufferAfterMinutes: 15,
        now: utc('2026-01-01 00:00'),
    );

    expect((new AvailabilityEngine)->slots($without)[0]->available)->toBeTrue()
        ->and((new AvailabilityEngine)->slots($with)[0]->available)->toBeFalse();
});

it('applies the before-buffer so the first slot becomes booked', function (): void {
    $busy = [tr('2026-01-01 09:00', '2026-01-01 09:30')];

    $without = new AvailabilityContext(
        windows: [tr('2026-01-01 09:30', '2026-01-01 10:30')],
        busy: $busy,
        durationMinutes: 30,
        slotIntervalMinutes: 30,
        bufferBeforeMinutes: 0,
        now: utc('2026-01-01 00:00'),
    );

    $with = new AvailabilityContext(
        windows: [tr('2026-01-01 09:30', '2026-01-01 10:30')],
        busy: $busy,
        durationMinutes: 30,
        slotIntervalMinutes: 30,
        bufferBeforeMinutes: 15,
        now: utc('2026-01-01 00:00'),
    );

    expect((new AvailabilityEngine)->slots($without)[0]->available)->toBeTrue()
        ->and((new AvailabilityEngine)->slots($with)[0]->available)->toBeFalse();
});

it('respects the minimum notice window', function (): void {
    $context = new AvailabilityContext(
        windows: [tr('2026-01-01 09:00', '2026-01-01 12:00')],
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        minimumNoticeMinutes: 90,
        now: utc('2026-01-01 09:00'),
    );

    $slots = (new AvailabilityEngine)->slots($context);

    expect($slots[0]->reason)->toBe(Slot::REASON_NOTICE)
        ->and($slots[1]->reason)->toBe(Slot::REASON_NOTICE)
        ->and($slots[2]->available)->toBeTrue();
});

it('respects the maximum advance window', function (): void {
    $context = new AvailabilityContext(
        windows: [tr('2026-01-03 09:00', '2026-01-03 10:00')],
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        maximumAdvanceDays: 1,
        now: utc('2026-01-01 00:00'),
    );

    expect((new AvailabilityEngine)->slots($context)[0]->reason)->toBe(Slot::REASON_TOO_FAR);
});

it('marks slots that are already in the past', function (): void {
    $context = new AvailabilityContext(
        windows: [tr('2026-01-01 09:00', '2026-01-01 10:00')],
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        now: utc('2026-01-01 12:00'),
    );

    expect((new AvailabilityEngine)->slots($context)[0]->reason)->toBe(Slot::REASON_PAST);
});

it('applies reason priority: too_far wins over booked', function (): void {
    $context = new AvailabilityContext(
        windows: [tr('2026-01-05 09:00', '2026-01-05 10:00')],
        busy: [tr('2026-01-05 09:00', '2026-01-05 10:00')],
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        maximumAdvanceDays: 0,
        now: utc('2026-01-01 00:00'),
    );

    expect((new AvailabilityEngine)->slots($context)[0]->reason)->toBe(Slot::REASON_TOO_FAR);
});

it('returns only available slots from availableSlots()', function (): void {
    $context = new AvailabilityContext(
        windows: [tr('2026-01-01 09:00', '2026-01-01 12:00')],
        busy: [tr('2026-01-01 10:15', '2026-01-01 10:45')],
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        now: utc('2026-01-01 00:00'),
    );

    $available = (new AvailabilityEngine)->availableSlots($context);

    expect($available)->toHaveCount(2)
        ->and($available[0]->start()->format('H:i'))->toBe('09:00')
        ->and($available[1]->start()->format('H:i'))->toBe('11:00');
});

it('returns an empty list when there are no windows', function (): void {
    $context = new AvailabilityContext(
        durationMinutes: 60,
        slotIntervalMinutes: 60,
        now: utc('2026-01-01 00:00'),
    );

    expect((new AvailabilityEngine)->slots($context))->toBeEmpty();
});
