<?php
declare(strict_types=1);

namespace App\Service\Security;

use Cake\Cache\Cache;
use Throwable;

/**
 * Composite abuse counters with an exclusive lock around the cache write.
 *
 * Redis/APCu increment is used when the engine supports it. File cache is
 * not atomic, so a lock file serialises the read-increment-write.
 */
final class RateLimitService
{
    public const CACHE_CONFIG = 'login_throttle';

    /**
     * @param string $scope Counter family.
     * @param string $subject IP, email, or user id.
     * @return string
     */
    public static function key(string $scope, string $subject): string
    {
        return $scope . '_' . hash('sha256', $subject !== '' ? $subject : 'unknown');
    }

    /**
     * @param string $scope Counter family.
     * @param string $subject IP, email, or user id.
     * @return int
     */
    public static function hits(string $scope, string $subject): int
    {
        try {
            return (int)Cache::read(self::key($scope, $subject), self::CACHE_CONFIG);
        } catch (Throwable) {
            return PHP_INT_MAX;
        }
    }

    /**
     * @param string $scope Counter family.
     * @param string $subject IP, email, or user id.
     * @return int New total.
     */
    public static function hit(string $scope, string $subject): int
    {
        $key = self::key($scope, $subject);
        try {
            $incremented = Cache::increment($key, 1, self::CACHE_CONFIG);
            if (is_int($incremented) && $incremented > 0) {
                return $incremented;
            }
        } catch (Throwable) {
            // FileEngine and similar stores cannot increment atomically.
        }

        try {
            $lockPath = CACHE . 'rate_' . $key . '.lock';
            $handle = fopen($lockPath, 'c+');
            if ($handle === false) {
                return PHP_INT_MAX;
            }

            try {
                flock($handle, LOCK_EX);
                $next = (int)Cache::read($key, self::CACHE_CONFIG) + 1;
                if (!Cache::write($key, $next, self::CACHE_CONFIG)) {
                    return PHP_INT_MAX;
                }

                return $next;
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        } catch (Throwable) {
            return PHP_INT_MAX;
        }
    }

    /**
     * @param string $scope Counter family.
     * @param string $subject IP, email, or user id.
     * @param int $max Inclusive threshold.
     * @return bool
     */
    public static function locked(string $scope, string $subject, int $max): bool
    {
        return self::hits($scope, $subject) >= $max;
    }

    /**
     * @param string $scope Counter family.
     * @param string $subject IP, email, or user id.
     * @return void
     */
    public static function clear(string $scope, string $subject): void
    {
        Cache::delete(self::key($scope, $subject), self::CACHE_CONFIG);
    }

    /**
     * @param string $email Raw email.
     * @return string
     */
    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
