<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MHStudio\FilamentBookingEngine\Data\TimeRange;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Models\Concerns\UsesConfiguredTable;

/**
 * A booked interval. Times are stored in UTC; the client timezone is kept beside
 * them for display.
 */
final class Appointment extends Model
{
    use UsesConfiguredTable;

    protected $guarded = [];

    protected static function tableKey(): string
    {
        return 'appointments';
    }

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'price' => 'decimal:2',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Only active appointments occupy a slot.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [
            AppointmentStatus::Requested->value,
            AppointmentStatus::Confirmed->value,
        ]);
    }

    public function range(): TimeRange
    {
        return new TimeRange(
            CarbonImmutable::parse($this->start_at),
            CarbonImmutable::parse($this->end_at),
        );
    }
}
