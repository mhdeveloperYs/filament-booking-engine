# Architecture — MH Booking Engine

Package: `mhstudio/filament-booking-engine`
Namespace: `MHStudio\FilamentBookingEngine`
Target: **Filament v5**, Laravel 12+ (baseline), PHP 8.3+, Pest, `spatie/laravel-package-tools`.

## Layers

```
┌─────────────────────────────────────────────┐
│ Filament UI  (Resources / Pages / Widgets)  │  ← later milestone
├─────────────────────────────────────────────┤
│ Application  (BookingManager, actions)      │  ← create/reschedule/cancel lifecycle
├─────────────────────────────────────────────┤
│ Domain / Engine  (AvailabilityEngine,       │  ← PURE, framework-light, heavily tested
│   SlotGenerator, ConflictGuard)             │
├─────────────────────────────────────────────┤
│ Data  (TimeRange, Slot, AvailabilityContext)│  ← value objects
├─────────────────────────────────────────────┤
│ Persistence  (Models + migrations)          │  ← Eloquent (thin)
└─────────────────────────────────────────────┘
```

**Key design rule:** the engine is **pure** — it takes value objects in and returns value
objects out, with **no database access**. Persistence lives in the Eloquent layer that
*builds* the engine's input. This is what makes correctness testable and the domain
reusable (public booking page, API, admin, console all call the same engine).

## Directory layout

```
src/
├── BookingEnginePlugin.php           # Filament\Contracts\Plugin (UI registered later)
├── BookingEngineServiceProvider.php  # spatie package-tools
├── Data/
│   ├── TimeRange.php                 # half-open interval [start, end)
│   ├── Slot.php                      # TimeRange + availability + reason
│   └── AvailabilityContext.php       # engine input (windows, busy, rules)
├── Engine/
│   ├── SlotGenerator.php             # windows -> candidate slot ranges
│   ├── AvailabilityEngine.php        # candidates - busy - policy = slots
│   └── ConflictGuard.php             # overlap detection + assertion
├── Exceptions/
│   └── SlotUnavailableException.php
├── Enums/
│   ├── AppointmentStatus.php
│   └── Weekday.php
└── Models/
    ├── Service.php
    ├── Provider.php
    ├── AvailabilityRule.php
    ├── AvailabilityException.php
    └── Appointment.php
config/booking-engine.php
database/migrations/*.php
tests/
├── Unit/TimeRangeTest.php
├── Unit/SlotGeneratorTest.php
├── Unit/AvailabilityEngineTest.php
└── Unit/ConflictGuardTest.php
```

## Time model (the correctness core)

- **Store instants in UTC** (`start_at`, `end_at`).
- **Author availability in the provider's timezone** (clock faces per weekday).
- **Generate slots in the provider's timezone**, then convert to the customer's timezone for display.
- Intervals are **half-open**: `[start, end)`. Two ranges overlap iff `a.start < b.end && b.start < a.end`. Adjacent ranges (one ends exactly when the next starts) do **not** overlap — this is what makes back-to-back appointments legal.
- **Buffers** widen the occupied interval: a candidate `[s, e)` with `buffer_before=b`, `buffer_after=a` occupies `[s-b, e+a)` and must not overlap any busy interval.

## Data model

| Table | Purpose |
|---|---|
| `booking_services` | name, slug, duration, buffer before/after, price, currency, capacity, colour, active |
| `booking_providers` | nullable user link, name, email, **timezone**, active |
| `booking_availability_rules` | provider, weekday, start_time, end_time (clock faces) |
| `booking_availability_exceptions` | provider, date, is_closed, custom windows (json) |
| `booking_appointments` | service, provider, client, start_at/end_at (UTC), timezone, status, notes, price |

All table names are configurable via `config('booking-engine.table_prefix')`.

## Correctness invariants (non-negotiable)

1. No two active appointments for the same provider may overlap (DB partial unique index + engine check).
2. Slots are never generated past the end of an availability window.
3. DST transitions never produce a slot at a non-existent local time; slot length in real time is preserved.
4. A slot respects `minimum_notice` (can't book the past/too-soon) and `maximum_advance`.
5. Buffers are respected on both sides.

## What is deliberately OUT of milestone 1
Payments, calendar sync, AI, reminders, recurring, multi-tenant, Filament resources/pages/widgets.
The engine + models + migrations + tests only.
