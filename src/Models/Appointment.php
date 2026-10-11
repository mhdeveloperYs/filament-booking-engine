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
 * @property int $id
 * @property int $service_id
 * @property int $provider_id
 * @property AppointmentStatus $status
 * @property CarbonImmutable $start_at
 * @property CarbonImmutable $end_at
 * @property string|null $timezone
 * @property string $client_name
 * @property string|null $client_email
 * @property string|null $client_phone
 * @property string|null $notes
 * @property string|null $price
 * @property-read Service $service
 * @property-read Provider $provider
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

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
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
