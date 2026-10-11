<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MHStudio\FilamentBookingEngine\Enums\Weekday;
use MHStudio\FilamentBookingEngine\Models\Concerns\UsesConfiguredTable;

/**
 * @property int $id
 * @property int $provider_id
 * @property Weekday $weekday
 * @property string $start_time
 * @property string $end_time
 * @property int|null $slot_interval_minutes
 * @property bool $is_active
 */
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

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
