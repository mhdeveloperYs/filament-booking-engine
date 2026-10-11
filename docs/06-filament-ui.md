# Filament UI (milestone 3)

The plugin registers native Filament v5 resources and a dashboard widget.

## Register on a panel

```php
use MHStudio\FilamentBookingEngine\BookingEnginePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(BookingEnginePlugin::make());
}
```

## What ships

| Component | Purpose |
|---|---|
| `ServiceResource` | CRUD for bookable services (duration, slot interval, buffers, price, capacity) |
| `ProviderResource` | CRUD for providers + two relation managers |
| `AvailabilityRulesRelationManager` | Weekly windows per weekday (start/end clock faces, interval, active) |
| `AvailabilityExceptionsRelationManager` | Date overrides — closed day, or custom windows via a repeater |
| `AppointmentResource` | List/create/edit appointments, status filters, provider filter |
| `UpcomingAppointmentsWidget` | Dashboard stats: upcoming, today, confirmed |

All under the **Bookings** navigation group.

## Appointment actions go through the domain

The table's row actions do not touch the model directly — they call `BookingManager`,
so every change is validated, serialized and emits an event:

- **Confirm** → `BookingManager::confirm()`
- **Reschedule** (modal with a `DateTimePicker`) → `BookingManager::reschedule()`,
  catching `SlotUnavailableException` and showing a danger notification
- **Cancel** (confirmed) → `BookingManager::cancel()`

This is the payoff of building the engine first: the UI is a thin shell over a tested core.

## Testing

`tests/Feature/FilamentRenderTest.php` renders the list pages through Livewire, creates a
service through the real form, and cancels an appointment through the real table action —
so the v5 form/table/action APIs are executed, not just declared.

To render Filament in Testbench, the test case registers the Filament + Blade Icons + Livewire
service providers and a minimal `TestPanelProvider` (`tests/Filament/TestPanelProvider.php`).
