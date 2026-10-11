<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MHStudio\FilamentBookingEngine\Enums\Weekday;
use MHStudio\FilamentBookingEngine\Models\Concerns\UsesConfiguredTable;

/** A weekly availability window, authored as a local clock face. */
final class AvailabilityRule extends Model
{
    use UsesConfiguredTable;

    protected $guarded = [];

    protected static function tableKey(): string
    {
        return 'availability_rules';
    }

    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
            'slot_interval_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
