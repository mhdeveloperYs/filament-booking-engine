<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Concurrency;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;

/**
 * Serializes bookings per provider and day.
 *
 * Day-scoped rather than slot-scoped: candidate slots overlap (a 60 minute
 * service on a 15 minute grid), so per-slot locks would not prevent races.
 */
final class BookingLock
{
    public function __construct(
        private readonly ?string $store = null,
        private readonly int $ttlSeconds = 10,
        private readonly int $waitSeconds = 5,
    ) {}

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function run(string|int $providerId, CarbonInterface $day, Closure $callback): mixed
    {
        $key = sprintf('booking-engine:lock:provider:%s:%s', $providerId, $day->toDateString());

        $store = Cache::store($this->store)->getStore();

        if (! $store instanceof LockProvider) {
            throw new SlotUnavailableException('The configured cache store does not support locks.');
        }

        try {
            return $store->lock($key, $this->ttlSeconds)
                ->block($this->waitSeconds, $callback);
        } catch (LockTimeoutException $exception) {
            throw new SlotUnavailableException(
                'Could not acquire the booking lock for this provider. Please try again.',
                previous: $exception,
            );
        }
    }
}
