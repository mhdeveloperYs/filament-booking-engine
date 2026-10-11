# Booking lifecycle & concurrency (milestone 2)

`Booking\BookingManager` is the only public write path for appointments. Direct status
flips are discouraged because they skip validation and events.

## API

```php
use MHStudio\FilamentBookingEngine\Booking\BookingManager;
use MHStudio\FilamentBookingEngine\Booking\BookingRequest;

$manager = app(BookingManager::class);

$appointment = $manager->create(new BookingRequest(
    service: $service,
    provider: $provider,
    start: CarbonImmutable::parse('2026-03-02 09:00', 'UTC'),
    clientName: 'Sara',
    clientEmail: 'sara@example.test',
));

$manager->reschedule($appointment, $newStart, reason: 'Customer request');
$manager->confirm($appointment);
$manager->cancel($appointment, reason: 'Changed plans');
$manager->complete($appointment);
$manager->markNoShow($appointment);
```

## What `create()` does, in order

1. Computes `[start, end)` from the service duration.
2. **Availability check** — the range must fit inside one of the provider's windows
   (weekly rules, overridden by a date exception).
3. **Serialize** — acquires a cache lock keyed by `provider + day` (`Concurrency\BookingLock`).
   Scoped to a day because slots overlap; per-slot locks would let 09:00 and 09:15 race.
4. **Transaction** — locks the provider row (`lockForUpdate`) and runs `ConflictGuard` against
   the provider's active appointments, each widened by the owning service's buffers.
5. Inserts the appointment.
6. Dispatches `AppointmentCreated`.

`reschedule()` is the same flow, excluding the appointment being moved from the busy set, and
dispatches `AppointmentRescheduled`. Status changes dispatch `AppointmentStatusChanged`
(and `AppointmentCancelled` for cancellation).

## Concurrency model (defence in depth)

| Layer | Mechanism | Protects against |
|---|---|---|
| 1 | `BookingLock` (cache lock, provider+day) | Two web requests at once |
| 2 | DB transaction + `Provider` row `lockForUpdate` | Concurrent transactions |
| 3 | `ConflictGuard` overlap check | Any remaining overlap |
| 4 (planned) | Partial unique index | Worst case at the storage layer |

In tests the default cache is the `array` store (implements `LockProvider`); production should
point `booking-engine.lock.store` at a shared store (Redis/database) when running multiple
app servers.

## Buffer semantics

An appointment occupies `[start − service.buffer_before, end + service.buffer_after)`.
`ScheduleResolver::busyFor()` widens every existing appointment by **its own** service buffers,
and `ConflictGuard` widens the candidate by **its** buffers. The two expansions are applied once
each, so buffer policy is symmetric and never double-counted.

## Events

| Event | When |
|---|---|
| `AppointmentCreated` | after a successful create |
| `AppointmentRescheduled` | after a move (carries `previousStart`) |
| `AppointmentStatusChanged` | after any status transition |
| `AppointmentCancelled` | after a cancellation (carries `reason`) |

## Tests

`tests/Feature/BookingManagerTest.php` covers create/reschedule/cancel, availability and buffer
enforcement, slot release on cancel, event dispatch, and the `ScheduleResolver` window/busy logic.
