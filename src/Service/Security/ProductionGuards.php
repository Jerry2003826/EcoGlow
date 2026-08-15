<?php
declare(strict_types=1);

namespace App\Service\Security;

use Cake\Cache\Cache;
use Cake\Cache\Engine\RedisEngine;
use Cake\Core\Configure;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\TransportFactory;
use RuntimeException;
use Throwable;

/**
 * Production bootstrap checks against the engines Cake actually registered.
 *
 * Configure::consume() removes Cache/EmailTransport from Configure during
 * bootstrap, so these guards must read Cache::getConfig() and
 * TransportFactory::getConfig() instead.
 */
final class ProductionGuards
{
    /**
     * @var bool
     */
    private static bool $probed = false;

    /**
     * @return bool
     */
    public static function shouldEnforce(): bool
    {
        if (Configure::read('debug')) {
            return false;
        }
        if (defined('PHPUNIT_COMPOSER_INSTALL') && getenv('ECOGLOW_TEST_PRODUCTION_GUARDS') !== '1') {
            return false;
        }

        return true;
    }

    /**
     * Cheap per-request checks. The Redis write probe runs once per process.
     *
     * @return void
     */
    public static function assert(): void
    {
        if (!self::shouldEnforce()) {
            return;
        }
        self::assertEmailTransport();
        self::assertRateLimitStore(false);
    }

    /**
     * Deployment / readiness probe. Always talks to Redis.
     *
     * @return void
     */
    public static function assertReady(): void
    {
        self::assertEmailTransport();
        self::assertRateLimitStore(true);
    }

    /**
     * @return void
     */
    public static function assertEmailTransport(): void
    {
        $config = TransportFactory::getConfig('default');
        $class = is_array($config) ? (string)($config['className'] ?? '') : '';
        if (in_array($class, [DebugTransport::class, 'Debug'], true)) {
            throw new RuntimeException('Production must not use the Debug email transport.');
        }
        $host = strtolower((string)($config['host'] ?? ''));
        $local = in_array($host, ['', 'localhost', '127.0.0.1', '::1'], true);
        $tls = (bool)($config['tls'] ?? false);
        if (!$local && !$tls) {
            throw new RuntimeException('Production SMTP must use TLS.');
        }
    }

    /**
     * @param bool $forceProbe Always run the Redis write probe.
     * @return void
     */
    public static function assertRateLimitStore(bool $forceProbe = false): void
    {
        $config = Cache::getConfig('login_throttle');
        if (!is_array($config)) {
            throw new RuntimeException('Production login/MFA/checkout throttling must use Redis.');
        }
        if (($config['fallback'] ?? true) !== false) {
            throw new RuntimeException('Production login_throttle must set fallback=false.');
        }
        self::assertRedisNetwork($config);
        try {
            $engine = Cache::pool('login_throttle');
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Production throttling requires a working Redis cache engine.',
                0,
                $exception,
            );
        }
        if (!$engine instanceof RedisEngine) {
            throw new RuntimeException('Production throttling requires a working Redis cache engine.');
        }
        if ($forceProbe || !self::$probed) {
            self::probeRateLimitStore();
            self::$probed = true;
        }
    }

    /**
     * @param array<string, mixed> $config Registered cache config.
     * @return bool
     */
    public static function isRedisStore(array $config): bool
    {
        $class = (string)($config['className'] ?? '');
        $allowed = [RedisEngine::class, 'Redis', 'Cake\\Cache\\Engine\\RedisEngine'];
        if (in_array($class, $allowed, true)) {
            return true;
        }
        $url = (string)($config['url'] ?? '');

        return str_starts_with($url, 'redis:') || str_starts_with($url, 'rediss:');
    }

    /**
     * @return void
     */
    public static function probeRateLimitStore(): void
    {
        $key = 'prod_guard_' . bin2hex(random_bytes(8));
        try {
            if (!Cache::write($key, 1, 'login_throttle')) {
                throw new RuntimeException('Redis probe write failed.');
            }
            $incremented = Cache::increment($key, 1, 'login_throttle');
            if ($incremented !== 2) {
                throw new RuntimeException('Redis probe increment failed.');
            }
            if ((int)Cache::read($key, 'login_throttle') !== 2) {
                throw new RuntimeException('Redis probe read failed.');
            }
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException && str_contains($exception->getMessage(), 'Redis probe')) {
                throw $exception;
            }
            throw new RuntimeException(
                'Production throttling requires a working Redis cache engine.',
                0,
                $exception,
            );
        } finally {
            Cache::delete($key, 'login_throttle');
        }
    }

    /**
     * @param array<string, mixed> $config Registered cache config.
     * @return void
     */
    private static function assertRedisNetwork(array $config): void
    {
        $url = (string)($config['url'] ?? '');
        $host = strtolower((string)($config['host'] ?? ''));
        $scheme = 'redis';
        $password = (string)($config['password'] ?? '');
        if ($url !== '') {
            $parts = parse_url($url);
            $host = strtolower((string)($parts['host'] ?? $host));
            $scheme = strtolower((string)($parts['scheme'] ?? 'redis'));
            $password = (string)($parts['pass'] ?? $password);
        }
        if ($host === '' || self::isPrivateHost($host)) {
            return;
        }
        if ($scheme !== 'rediss') {
            throw new RuntimeException('Public Redis must use rediss://.');
        }
        if ($password === '') {
            throw new RuntimeException('Public Redis must require a password.');
        }
    }

    /**
     * @param string $host Hostname or IP.
     * @return bool
     */
    private static function isPrivateHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }
        if (str_starts_with($host, '10.') || str_starts_with($host, '192.168.')) {
            return true;
        }

        return (bool)preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host);
    }
}
