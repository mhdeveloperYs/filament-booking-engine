<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MHStudio\FilamentBookingEngine\Models\Concerns\UsesConfiguredTable;

/**
 * A date-specific override: either a full closure or custom opening windows.
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
