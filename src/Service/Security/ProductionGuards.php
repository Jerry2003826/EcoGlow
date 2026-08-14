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
     * @return void
     */
    public static function assert(): void
    {
        if (!self::shouldEnforce()) {
            return;
        }
        self::assertEmailTransport();
        self::assertRateLimitStore();
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
    }

    /**
     * @return void
     */
    public static function assertRateLimitStore(): void
    {
        $config = Cache::getConfig('login_throttle');
        if (!is_array($config) || !self::isRedisStore($config)) {
            throw new RuntimeException('Production login/MFA/checkout throttling must use Redis.');
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
        if (str_starts_with($url, 'redis:') || str_starts_with($url, 'rediss:')) {
            return true;
        }
        try {
            $engine = Cache::pool('login_throttle');

            return $engine instanceof RedisEngine;
        } catch (Throwable) {
            return false;
        }
    }
}
