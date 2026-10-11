<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    | Every table is configurable so the package can live beside an existing
    | schema without collisions.
    */
    'tables' => [
        'services' => 'booking_services',
        'providers' => 'booking_providers',
        'availability_rules' => 'booking_availability_rules',
        'availability_exceptions' => 'booking_availability_exceptions',
        'appointments' => 'booking_appointments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default booking policy
    |--------------------------------------------------------------------------
    | These are fallbacks used when a Service / Provider does not override them.
    */
    'slot_interval_minutes' => 15,

    // How soon before an appointment a customer may still book it.
    'minimum_notice_minutes' => 0,

    // How far ahead booking is allowed. `null` disables the limit.
    'maximum_advance_days' => 90,

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    | Availability is authored as local clock faces. This is the fallback zone
    | when a provider does not define its own. `null` means config('app.timezone').
    */
    'timezone' => null,

    /*
    |--------------------------------------------------------------------------
    | Concurrency
    |--------------------------------------------------------------------------
    | Bookings are serialized per provider + day with a cache lock before the
    | database transaction runs. `store` of null uses the default cache store.
    */
    'lock' => [
        'store' => null,
        'ttl_seconds' => 10,
        'wait_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public booking page
    |--------------------------------------------------------------------------
    | An optional customer-facing route that mounts the Livewire booking wizard.
    */
    'public' => [
        'route_enabled' => false,
        'route_path' => 'book',
        'route_name' => 'booking-engine.book',
        'route_middleware' => ['web'],
    ],

];
