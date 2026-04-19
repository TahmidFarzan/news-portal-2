<?php
namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CacheServerHelper
{
    protected static function driver(): string
    {
        return config('cache.default');
    }

    protected static function supportsTags(): bool
    {
        return ! in_array(self::driver(), ['file', 'array'], true);
    }

    public static function isConnected(): bool
    {
        if (! config('cache.enable') || ! config('cache.default')) {
            return false;
        }

        try {
            Cache::put('__cache_test__', true, 1);
            Cache::forget('__cache_test__');
            return true;
        } catch (Exception $ex) {
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
        int $expireTime = 86400,
        array $tags = []
    ): void {
        if (! self::isConnected()) {
            return;
        }

        $key = self::keyGenerate($key);

        try {
            if (! empty($tags) && self::supportsTags()) {
                Cache::tags($tags)->put($key, $data, $expireTime);
            } else {
                Cache::put($key, $data, $expireTime);
            }
        } catch (Exception $ex) {
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
        if (! self::isConnected()) {
            return null;
        }

        $key = self::keyGenerate($key);

        try {
            if (! empty($tags) && self::supportsTags()) {
                return Cache::tags($tags)->get($key);
            }

            return Cache::get($key);
        } catch (Exception $ex) {
            Log::error("Failed to retrieve cached '{$key}': " . $ex->getMessage());
            return null;
        }
    }

    /* -----------------------------------------------------------------
     |  DELETE
     |-----------------------------------------------------------------*/

    public static function clearCached(string $key, array $tags = []): void
    {
        if (! self::isConnected()) {
            return;
        }

        $key = self::keyGenerate($key);

        try {
            if (! empty($tags) && self::supportsTags()) {
                Cache::tags($tags)->forget($key);
            } else {
                Cache::forget($key);
            }
        } catch (Exception $ex) {
            Log::error("Failed to clear cached '{$key}': " . $ex->getMessage());
        }
    }

    /* -----------------------------------------------------------------
     |  CLEAR BY TAG (PRIMARY STRATEGY)
     |-----------------------------------------------------------------*/

    public static function clearCachedByTag(string | array $tags): void
    {
        if (! self::isConnected() || ! self::supportsTags()) {
            return;
        }

        try {
            Cache::tags((array) $tags)->flush();
        } catch (Exception $ex) {
            Log::error('Cache tag flush failed: ' . $ex->getMessage());
        }
    }

    /* -----------------------------------------------------------------
     |  CLEAR ALL
     |-----------------------------------------------------------------*/

    public static function clearAllCached(): void
    {
        if (! self::isConnected()) {
            return;
        }

        try {
            Cache::flush();
        } catch (Exception $ex) {
            Log::error('Cache flush failed: ' . $ex->getMessage());
        }
    }
}
