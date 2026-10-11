# Public booking page (milestone 4)

The customer-facing half of the product: a Livewire wizard that turns the tested engine into a
bookable flow a visitor can use without an account.

## The flow

`service → provider → date → slot → details → confirm`

Every slot comes from the same `AvailabilityEngine` the admin panel uses, and the booking is
created through `BookingManager` — so the public path inherits availability rules, buffers,
notice/advance limits and concurrency protection for free.

## Mount it

As a route (opt-in via config):

```php
// config/booking-engine.php
'public' => [
    'route_enabled' => true,
    'route_path' => 'book',
    'route_name' => 'booking-engine.book',
    'route_middleware' => ['web'],
],
```

Or drop the component anywhere:

```blade
<livewire:booking-engine.booking-wizard />
<livewire:booking-engine.booking-wizard service="consultation" />
```

The `service` argument pins the wizard to a service slug.

## Component API

`MHStudio\FilamentBookingEngine\Livewire\BookingWizard`

| Member | Purpose |
|---|---|
| `serviceId`, `providerId`, `date` | selection state |
| `slotStart` | chosen slot (ISO-8601, UTC) |
| `clientName`, `clientEmail`, `clientPhone`, `notes`, `timezone` | customer input |
| `slots` (computed) | available `Slot[]` for the selection |
| `selectSlot($iso)` | choose a slot |
| `book()` | validate + create via `BookingManager` |
| `confirmationMessage` | set on success |

When the slot is taken between selection and submit, `book()` catches `SlotUnavailableException`,
adds an error on `slotStart` and clears the choice — no 500s, no silent double-booking.

## Views

The wizard ships its own layout (`filament-booking-engine::layouts.public`) so the route works
in any host app without one, and pulls Tailwind from a CDN for zero setup. Publish to restyle:

```bash
php artisan vendor:publish --tag=filament-booking-engine-views
```

Swap the layout for your own app layout by overriding the view, or point the component at your
own with the `#[Layout]` attribute on a subclass. The shipped markup is plain Tailwind, no build
step and no dependency on the Filament theme.

## Tests

`tests/Feature/PublicBookingTest.php`:
- the component is registered under `booking-engine.booking-wizard`
- slots are offered for a service/provider/date
- a full end-to-end booking
- a taken slot produces a `slotStart` error and no second row
- client name/email are required
