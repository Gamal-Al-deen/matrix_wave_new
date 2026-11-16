<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cache helpers for public, non-user-specific read data.
 *
 * Caching is an optimization only: a cache failure falls back to the
 * database so the application keeps working with correct data.
 */
final class PublicCache
{
    /**
     * Public site settings singleton (also used for the configured currency).
     */
    public const SITE_SETTINGS = 'public:site-settings:v1';

    /**
     * Active social links rendered in the public footer.
     */
    public const SOCIAL_LINKS = 'public:social-links:v1';

    /**
     * Active categories with their publicly visible product counts.
     */
    public const ACTIVE_CATEGORIES = 'public:active-categories:v1';

    public const TTL_SITE_SETTINGS = 3600;

    public const TTL_SOCIAL_LINKS = 3600;

    public const TTL_ACTIVE_CATEGORIES = 300;

    /**
     * Return the cached value or resolve, cache, and return it.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $resolver
     * @return TValue
     */
    public static function remember(string $key, int $ttl, Closure $resolver): mixed
    {
        try {
            $cached = Cache::get($key);
        } catch (Throwable $exception) {
            self::logFailure('read', $key, $exception);

            return $resolver();
        }

        if ($cached !== null) {
            return $cached;
        }

        $value = $resolver();

        try {
            Cache::put($key, $value, $ttl);
        } catch (Throwable $exception) {
            self::logFailure('write', $key, $exception);
        }

        return $value;
    }

    /**
     * Remove one or more cache entries once the active database transaction
     * commits. Without an active transaction the entries are removed
     * immediately.
     *
     * Laravel discards committed callbacks when a transaction rolls back, so a
     * failed write can never invalidate the cache. Outside a transaction the
     * callback executes straight away, keeping write paths without an explicit
     * transaction unchanged.
     */
    public static function forgetAfterCommit(string ...$keys): void
    {
        if ($keys === []) {
            return;
        }

        $connection = DB::connection();

        if ($connection->transactionLevel() === 0) {
            self::forget(...$keys);

            return;
        }

        $connection->afterCommit(static function () use ($keys): void {
            self::forget(...$keys);
        });
    }

    /**
     * Remove one or more cache entries. Failures are logged, never thrown.
     */
    public static function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            try {
                Cache::forget($key);
            } catch (Throwable $exception) {
                self::logFailure('forget', $key, $exception);
            }
        }
    }

    private static function logFailure(string $operation, string $key, Throwable $exception): void
    {
        Log::warning('Public cache operation failed; continuing with database data.', [
            'operation' => $operation,
            'key' => $key,
            'exception' => $exception::class,
        ]);
    }
}
