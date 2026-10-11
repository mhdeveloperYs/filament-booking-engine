<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MHStudio\FilamentBookingEngine\Models\Concerns\UsesConfiguredTable;

/** A bookable service. */
final class Service extends Model
{
    use UsesConfiguredTable;

    protected $guarded = [];

    protected static function tableKey(): string
    {
        return 'services';
    }

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'slot_interval_minutes' => 'integer',
            'buffer_before_minutes' => 'integer',
            'buffer_after_minutes' => 'integer',
            'capacity' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
