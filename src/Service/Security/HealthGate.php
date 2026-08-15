<?php
declare(strict_types=1);

namespace App\Service\Security;

use App\Middleware\AbuseThrottleMiddleware;

/**
 * Authorizes /health/ready before any abuse counter is touched.
 */
final class HealthGate
{
    /**
     * @param string $configured Token from HEALTH_READY_TOKEN.
     * @param string $provided X-Health-Token header.
     * @param bool $production ProductionGuards::shouldEnforce().
     * @param string $ip Client IP for failed-auth throttling.
     * @return int|null Null when the probe may run; otherwise the HTTP status.
     */
    public static function denyStatus(
        string $configured,
        string $provided,
        bool $production,
        string $ip,
    ): ?int {
        if ($configured !== '' && hash_equals($configured, $provided)) {
            return null;
        }
        if ($configured === '') {
            return $production ? 503 : null;
        }
        if (
            RateLimitService::locked(
                AbuseThrottleMiddleware::SCOPE_HEALTH,
                $ip,
                AbuseThrottleMiddleware::MAX_HEALTH,
            )
        ) {
            return 429;
        }
        RateLimitService::hit(AbuseThrottleMiddleware::SCOPE_HEALTH, $ip);

        return 401;
    }
}
