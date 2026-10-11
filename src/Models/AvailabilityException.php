<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use MHStudio\FilamentBookingEngine\Models\Concerns\UsesConfiguredTable;

/**
 * @property int $id
 * @property int $provider_id
 * @property Carbon $date
 * @property bool $is_closed
 * @property array<int, array{start?: string, end?: string}>|null $windows
 * @property string|null $reason
 */
final class AvailabilityException extends Model
{
    use UsesConfiguredTable;

    protected $guarded = [];

    protected static function tableKey(): string
    {
        return 'availability_exceptions';
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
            'windows' => 'array',
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
