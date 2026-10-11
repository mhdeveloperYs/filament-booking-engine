<?php

declare(strict_types=1);

use MHStudio\FilamentBookingEngine\Engine\SlotGenerator;

/**
 * Daylight-saving behaviour of the pure engine. Europe/London 2026:
 * spring-forward on 2026-03-29 (23h day), fall-back on 2026-10-25 (25h day).
 */
it('keeps real duration on a spring-forward day', function (): void {
    // 00:00-04:00 local is only three real hours: 01:00-02:00 does not exist.
    $window = tr('2026-03-29 00:00', '2026-03-29 04:00', 'Europe/London');

    expect($window->durationInMinutes())->toBe(180);

    $slots = (new SlotGenerator)->generate([$window], 60, 60);

    expect($slots)->toHaveCount(3)
        ->and($slots[0]->start->utc()->format('Y-m-d H:i'))->toBe('2026-03-29 00:00')
        ->and($slots[2]->start->utc()->format('Y-m-d H:i'))->toBe('2026-03-29 02:00')
        ->and($slots[0]->durationInMinutes())->toBe(60)
        ->and($slots[2]->durationInMinutes())->toBe(60);
});

it('never emits duplicate instants on a spring-forward day', function (): void {
    $window = tr('2026-03-29 00:00', '2026-03-29 04:00', 'Europe/London');

    $starts = array_map(
        static fn ($slot): int => $slot->start->getTimestamp(),
        (new SlotGenerator)->generate([$window], 30, 30),
    );

    expect(count(array_unique($starts)))->toBe(count($starts));
});

it('spans the extra hour on a fall-back day', function (): void {
    // 00:00-04:00 local is five real hours: 01:00-02:00 happens twice.
    $window = tr('2026-10-25 00:00', '2026-10-25 04:00', 'Europe/London');

    expect($window->durationInMinutes())->toBe(300);

    $slots = (new SlotGenerator)->generate([$window], 60, 60);

    expect($slots)->toHaveCount(5)
        ->and($slots[0]->start->utc()->format('Y-m-d H:i'))->toBe('2026-10-24 23:00')
        ->and($slots[4]->start->utc()->format('Y-m-d H:i'))->toBe('2026-10-25 03:00');
});

it('collapses a window that starts inside the skipped hour', function (): void {
    // 01:30 and 02:30 local both resolve to 01:30 UTC on a spring-forward day.
    $window = tr('2026-03-29 01:30', '2026-03-29 02:30', 'Europe/London');

    expect($window->durationInMinutes())->toBe(0)
        ->and((new SlotGenerator)->generate([$window], 60, 60))->toBeEmpty();
});
