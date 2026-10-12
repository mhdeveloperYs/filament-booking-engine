# MH Booking Engine

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mhstudio/filament-booking-engine.svg?style=flat-square)](https://packagist.org/packages/mhstudio/filament-booking-engine)
[![run-tests](https://github.com/mhdeveloperYs/filament-booking-engine/actions/workflows/run-tests.yml/badge.svg)](https://github.com/mhdeveloperYs/filament-booking-engine/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/mhstudio/filament-booking-engine.svg?style=flat-square)](https://packagist.org/packages/mhstudio/filament-booking-engine)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A correctness-focused, **Filament-native** booking & appointments engine for Laravel.

Manage services, providers and availability inside your Filament panel, and let customers book
through a Livewire wizard — all backed by one tested availability engine.

## Requirements

- PHP 8.3+
- Laravel 12+
- Filament v5

## Installation

```bash
composer require mhstudio/filament-booking-engine
```

Run the migrations:

```bash
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=filament-booking-engine-config
```

## Register the plugin

```php
use MHStudio\FilamentBookingEngine\BookingEnginePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(BookingEnginePlugin::make());
}
```

This adds three resources (Services, Providers, Appointments), the availability relation
managers and a dashboard widget under the **Bookings** navigation group.

## Public booking page

Enable the route:

```php
// config/booking-engine.php
'public' => [
    'route_enabled' => true,
    'route_path' => 'book',
    'route_middleware' => ['web'],
],
```

or drop the component anywhere:

```blade
<livewire:booking-engine.booking-wizard />
<livewire:booking-engine.booking-wizard service="consultation" />
```

Publish the view to restyle it:

```bash
php artisan vendor:publish --tag=filament-booking-engine-views
```

## Using the engine directly

```php
use MHStudio\FilamentBookingEngine\Booking\BookingManager;
use MHStudio\FilamentBookingEngine\Booking\BookingRequest;
use MHStudio\FilamentBookingEngine\Data\AvailabilityContext;
use MHStudio\FilamentBookingEngine\Data\TimeRange;
use MHStudio\FilamentBookingEngine\Engine\AvailabilityEngine;

// Preview slots
$context = new AvailabilityContext(
    windows: [TimeRange::make('2026-10-12 09:00', '2026-10-12 12:00', 'Africa/Casablanca')],
    busy:    [TimeRange::make('2026-10-12 10:15', '2026-10-12 10:45', 'Africa/Casablanca')],
    durationMinutes: 60,
    slotIntervalMinutes: 30,
    bufferBeforeMinutes: 10,
    bufferAfterMinutes: 10,
);

$slots = (new AvailabilityEngine())->availableSlots($context);

// Book (validates availability, serializes, and is concurrency-safe)
$appointment = app(BookingManager::class)->create(new BookingRequest(
    service: $service,
    provider: $provider,
    start: $start,
    clientName: 'Sara',
    clientEmail: 'sara@example.test',
));
```

## Testing

```bash
composer test       # Pest
composer phpstan    # Larastan (PHPStan level 6)
composer pint       # code style
```


## Roadmap

The free core (services, availability, booking lifecycle, panel UI, public booking) is complete.
A paid **Pro** tier is planned: payments, reminders, recurring bookings, calendar sync,
multi-tenant, analytics and AI.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE](LICENSE). The future Pro tier will be commercially licensed.
