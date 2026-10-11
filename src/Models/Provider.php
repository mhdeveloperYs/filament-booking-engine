<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MHStudio\FilamentBookingEngine\Models\Concerns\UsesConfiguredTable;

/**
 * A provider of appointments (staff member, room, resource). Optionally linked
 * to the host application's user model.
 */
final class Provider extends Model
{
    use UsesConfiguredTable;

    protected $guarded = [];

    protected static function tableKey(): string
    {
        return 'providers';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * The timezone this provider's clock faces are authored in.
     */
    public function bookingTimezone(): string
    {
        return $this->timezone
            ?: (config('booking-engine.timezone') ?: (string) config('app.timezone', 'UTC'));
    }
}
