# Changelog

All notable changes to `filament-booking-engine` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Static analysis with Larastan (PHPStan level 6) and a `phpstan` CI workflow.
- Daylight-saving and cross-timezone tests (spring-forward, fall-back, non-UTC provider).

### Changed

- `BookingLock` now verifies the configured cache store supports locks.
- Eloquent models document their columns and relations for static analysis.

### Fixed

- The public booking wizard now ships its own layout, so the route renders in an app that has no
  Livewire layout of its own (found via a real-app smoke test).

## [0.1.0] - 2026-10-11

First public preview of the free core.

### Added

- **Domain engine (pure, framework-free):** `TimeRange`, `Slot`, `AvailabilityContext`,
  `SlotGenerator`, `AvailabilityEngine`, `ConflictGuard`.
- **Booking lifecycle:** `BookingManager` (`create`, `reschedule`, `cancel`, `confirm`,
  `complete`, `markNoShow`) with availability validation, buffers, notice/advance limits.
- **Concurrency protection (defence in depth):** per provider+day cache lock (`BookingLock`),
  transactional provider row lock, and overlap guard.
- **`ScheduleResolver`:** bridges weekly availability rules, date exceptions and existing
  appointments into engine value objects.
- **Events:** `AppointmentCreated`, `AppointmentRescheduled`, `AppointmentCancelled`,
  `AppointmentStatusChanged`.
- **Filament v5 UI:** `ServiceResource`, `ProviderResource` (with weekly-availability and
  date-exception relation managers), `AppointmentResource` with lifecycle actions, and an
  `UpcomingAppointmentsWidget`.
- **Public booking wizard** (`Livewire\BookingWizard`): service → provider → date → slot →
  details, bookable through `BookingManager`.
- Configurable table names, publishable config / migrations / views.
- 62 tests (Pest) covering the engine, lifecycle, Filament UI rendering and the public flow.

[Unreleased]: https://github.com/mhstudio/filament-booking-engine/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/mhstudio/filament-booking-engine/releases/tag/v0.1.0
