<?php

namespace App\Helpers;

use Throwable;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CacheServerHelper
{
    const oneDayInSecond = 86400;
    const sixHoursInSecond = 21600;

    protected static function driver(): string
    {
        return config('cache.default');
    }

    protected static function supportsTags(): bool
    {
        return !in_array(self::driver(), ['file', 'array'], true);
    }

    protected static function ttl(int $seconds): DateTimeInterface
    {
        return Carbon::now()->addSeconds($seconds);
    }

    public static function isConnected(): bool
    {
        if (!config('cache.enable') || !config('cache.default')) {
            return false;
        }

        try {
            Cache::put('__cache_test__', true, self::ttl(2));
            Cache::forget('__cache_test__');
            return true;
        } catch (Throwable $ex) {
            Log::error('Cache store not available: ' . $ex->getMessage());
            return false;
        }
    }

    public static function keyGenerate(string $key): string
    {
        return Str::lower(Str::slug($key));
    }

    /* -----------------------------------------------------------------
    |  STORE
    |-----------------------------------------------------------------*/

    public static function cachedData(
        string $key,
        mixed $data,
        int $expireTime = self::oneDayInSecond,
        array $tags = []
    ): void {
        if (!self::isConnected()) {
            return;
        }

        $key = self::keyGenerate($key);

        try {
            $store = (!empty($tags) && self::supportsTags())
                ? Cache::tags($tags)
                : Cache::store();

            $store->put($key, $data, self::ttl($expireTime));

        } catch (Throwable $ex) {
            Log::error("Failed to cache '{$key}': " . $ex->getMessage());
        }
    }

    /* -----------------------------------------------------------------
    |  READ
    |-----------------------------------------------------------------*/

    public static function getCachedData(
        string $key,
        array $tags = []
    ): mixed {
        if (!self::isConnected()) {
            return null;
        }

        $key = self::keyGenerate($key);

        try {
            $store = (!empty($tags) && self::supportsTags())
                ? Cache::tags($tags)
                : Cache::store();

            return $store->get($key);

        } catch (Throwable $ex) {
            Log::error("Failed to retrieve cached '{$key}': " . $ex->getMessage());
            return null;
        }
    }

    /* -----------------------------------------------------------------
    |  DELETE
    |-----------------------------------------------------------------*/

    public static function clearCached(string $key, array $tags = []): void
    {
        if (!self::isConnected()) {
            return;
        }

        $key = self::keyGenerate($key);

        try {
            $store = (!empty($tags) && self::supportsTags())
                ? Cache::tags($tags)
                : Cache::store();

            $store->forget($key);

        } catch (Throwable $ex) {
            Log::error("Failed to clear cached '{$key}': " . $ex->getMessage());
        }
    }

    /* -----------------------------------------------------------------
    |  CLEAR BY TAG
    |-----------------------------------------------------------------*/

    public static function clearCachedByTag(string|array $tags): void
    {
        if (!self::isConnected() || !self::supportsTags()) {
            return;
        }

        try {
            Cache::tags((array) $tags)->flush();
        } catch (Throwable $ex) {
            Log::error('Cache tag flush failed: ' . $ex->getMessage());
        }
    }

    /* -----------------------------------------------------------------
    |  CLEAR ALL
    |-----------------------------------------------------------------*/

    public static function clearAllCached(): void
    {
        if (!self::isConnected()) {
            return;
        }

        try {
            Cache::flush();
        } catch (Throwable $ex) {
            Log::error('Cache flush failed: ' . $ex->getMessage());
        }
    }
}
