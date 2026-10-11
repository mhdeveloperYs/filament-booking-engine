# Changelog

All notable changes to `filament-booking-engine` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-10-11

First public release of the free core.

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
  details, bookable through `BookingManager`, with its own layout.
- Configurable table names, publishable config / migrations / views.
- Static analysis with Larastan (PHPStan level 6) and a `phpstan` CI workflow.
- Daylight-saving and cross-timezone test coverage.
- 71 tests (Pest) covering the engine, lifecycle, Filament UI rendering and the public flow.

### Changed

- `BookingLock` verifies the configured cache store supports locks.
- Eloquent models document their columns and relations for static analysis.

### Fixed

- The public booking wizard ships its own layout, so the route renders in an app that has no
  Livewire layout of its own.

[Unreleased]: https://github.com/mhdeveloperYs/filament-booking-engine/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/mhdeveloperYs/filament-booking-engine/releases/tag/v0.1.0
