<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use MHStudio\FilamentBookingEngine\Data\TimeRange;
use MHStudio\FilamentBookingEngine\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Parse a moment as a UTC CarbonImmutable (deterministic across machines).
 */
function utc(string $moment): CarbonImmutable
{
    return CarbonImmutable::parse($moment, 'UTC');
}

/**
 * Build a {@see TimeRange} from two "Y-m-d H:i" strings in a given timezone.
 */
function tr(string $start, string $end, string $timezone = 'UTC'): TimeRange
{
    return TimeRange::make($start, $end, $timezone);
}
