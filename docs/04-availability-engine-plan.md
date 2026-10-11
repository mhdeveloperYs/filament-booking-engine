# Availability Engine — implementation plan & test cases

## Components

### `SlotGenerator`
`generate(TimeRange[] $windows, int $durationMinutes, int $intervalMinutes): TimeRange[]`
- For each window, step a cursor from `window.start` by `intervalMinutes`.
- Emit `[cursor, cursor + duration)` only while `cursor + duration <= window.end`.
- Skip zero/negative duration. Guard against interval <= 0 (fall back to duration).

### `AvailabilityEngine`
`slots(AvailabilityContext $ctx): Slot[]`
1. `$candidates = SlotGenerator->generate(windows, duration, interval)`.
2. Compute `$earliest = now + minimumNoticeMinutes`, `$latest = now + maximumAdvanceDays`.
3. For each candidate, assign the **first** applicable reason (priority order):
   - `past`      → `end <= now`
   - `notice`    → `start < earliest`
   - `too_far`   → `latest !== null && start > latest`
   - `booked`   → buffered range overlaps any busy interval
   - else → available (`reason = null`)
4. Return all slots (available + unavailable). `availableSlots()` filters.

### `ConflictGuard`
- `overlaps(TimeRange $a, TimeRange $b): bool`
- `conflicts(TimeRange $candidate, iterable $busy, int $before = 0, int $after = 0): bool`
- `assertAvailable(TimeRange $candidate, iterable $busy, int $before = 0, int $after = 0): void` → throws `SlotUnavailableException`.

> The DB-level atomic guard (advisory lock on provider+day / `SELECT ... FOR UPDATE` + partial
> unique index) arrives with `BookingManager` in the next milestone. This milestone implements
> the pure decision function it will defer to.

## Test cases

### TimeRangeTest
- [x] `overlaps()` true when ranges intersect
- [x] `overlaps()` false when adjacent (touching edges)
- [x] `overlaps()` false when disjoint
- [x] `overlaps()` true when one contains the other
- [x] `overlaps()` true when identical
- [x] `contains()` boundary is inclusive of start, exclusive of end
- [x] `durationInMinutes()` correct
- [x] `expand(before, after)` widens both sides
- [x] `make()` accepts strings and CarbonImmutable

### SlotGeneratorTest
- [x] single window 09:00–12:00, 60min duration, 30min interval → 09:00, 09:30, 10:00, 10:30, 11:00 (last full slot), no 11:30 overflow
- [x] duration equal to window length → exactly one slot
- [x] duration longer than window → zero slots
- [x] two windows (morning + afternoon) → slots for both, none across the gap
- [x] interval larger than duration → non-overlapping slots
- [x] interval smaller than duration → overlapping candidate slots are still emitted
- [x] zero/negative duration → empty
- [x] interval <= 0 → falls back to duration
- [x] unsorted windows → output sorted by start

### AvailabilityEngineTest
- [x] all slots available when no busy intervals and no notice
- [x] busy interval marks overlapping slots `booked`
- [x] buffer_before marks the previous slot unavailable
- [x] buffer_after marks the next slot unavailable
- [x] buffer only affects the edges needed (non-overlapping slots stay available)
- [x] `minimum_notice` marks too-soon slots `notice`
- [x] `maximum_advance` marks too-far slots `too_far`
- [x] past slots marked `past`
- [x] reason priority: booked beats too_far when both applied (documented order)
- [x] `availableSlots()` returns only available
- [x] empty windows → empty result
- [x] context `now` is injectable (deterministic tests)

### ConflictGuardTest
- [x] adjacent appointment does not conflict
- [x] overlapping appointment conflicts
- [x] buffer before/after turns an adjacent appointment into a conflict
- [x] `assertAvailable` throws `SlotUnavailableException` on conflict
- [x] `assertAvailable` returns void on success

## DST / timezone test cases (done — `tests/Unit/DstTest.php`, `tests/Feature/TimezoneBookingTest.php`)
- [x] Spring-forward day: the 00:00-04:00 local window is 180 real minutes; no duplicate instants
- [x] Fall-back day: the 00:00-04:00 local window is 300 real minutes
- [x] A window starting inside the skipped hour collapses to zero length
- [x] Provider tz ≠ UTC → booked and stored in UTC

## Definition of done (milestone 1)
- Engine + value objects implemented, no DB access in engine.
- All unit tests above green.
- Models + migrations scaffolded and migrate cleanly.
- Provider/plugin registered; UI intentionally empty.
