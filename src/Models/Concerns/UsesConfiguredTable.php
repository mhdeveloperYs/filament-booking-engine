<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the table name from package config.
 *
 * @mixin Model
 */
trait UsesConfiguredTable
{
    abstract protected static function tableKey(): string;

    public function getTable(): string
    {
        $configured = config('booking-engine.tables.'.static::tableKey());

        return is_string($configured) && $configured !== ''
            ? $configured
            : parent::getTable();
    }
}
