<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Concurrency;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;

/**
 * Serializes booking operations for a provider on a given day.
 *
 * The lock is the first line of defence against double-booking races. It is
 * intentionally scoped to a *day* rather than a slot: bookable slots overlap
 * (a 60-minute service on a 15-minute grid), so per-slot locks would let two
 * overlapping requests race through independently.
 *
 * The database transaction inside the callback is the second line of defence
 * (row lock + ConflictGuard).
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

        try {
            return Cache::store($this->store)
                ->lock($key, $this->ttlSeconds)
                ->block($this->waitSeconds, $callback);
        } catch (LockTimeoutException $exception) {
            throw new SlotUnavailableException(
                'Could not acquire the booking lock for this provider. Please try again.',
                previous: $exception,
            );
        }
    }
}
