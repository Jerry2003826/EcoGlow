<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Cache\Cache;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Throttles repeated failed admin login attempts.
 *
 * Enforcement has to happen *before* the authentication middleware: that
 * middleware persists a successful identity to the session after the
 * controller returns, so a controller-level check cannot actually stop a
 * correct password from logging in during a lockout. By short-circuiting the
 * login POST here, authentication never runs while locked out, so every
 * attempt (right or wrong password) is refused for the lockout window.
 *
 * Failure counting lives in App\Controller\UsersController, which knows the
 * authentication result; this middleware only reads the counter to decide
 * whether to block. Both share the helpers below.
 */
class LoginThrottleMiddleware implements MiddlewareInterface
{
    /**
     * Failed attempts (per client IP) allowed before the form locks.
     *
     * @var int
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Cache config backing the throttle. Its duration is the lockout window.
     *
     * @var string
     */
    public const CACHE_CONFIG = 'login_throttle';

    /**
     * Short-circuit locked-out login submissions before authentication runs.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isLoginPost($request) && self::isLockedOut($this->clientIp($request))) {
            // Bounce back to the login form (GET), where the controller shows
            // the lockout message. Authentication is never invoked.
            return (new Response())
                ->withStatus(302)
                ->withHeader('Location', '/login');
        }

        return $handler->handle($request);
    }

    /**
     * Whether this request is a submission of the login form.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return bool
     */
    protected function isLoginPost(ServerRequestInterface $request): bool
    {
        return strtoupper($request->getMethod()) === 'POST'
            && rtrim($request->getUri()->getPath(), '/') === '/login';
    }

    /**
     * Resolve the client IP from the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return string
     */
    protected function clientIp(ServerRequestInterface $request): string
    {
        if ($request instanceof ServerRequest) {
            return $request->clientIp() ?: 'unknown';
        }

        $params = $request->getServerParams();

        return (string)($params['REMOTE_ADDR'] ?? 'unknown') ?: 'unknown';
    }

    /**
     * Cache key for a client IP (hashed so it is a safe key).
     *
     * @param string $ip The client IP.
     * @return string
     */
    public static function throttleKey(string $ip): string
    {
        return 'login_' . hash('sha256', $ip !== '' ? $ip : 'unknown');
    }

    /**
     * Current failed-attempt count for a client IP.
     *
     * @param string $ip The client IP.
     * @return int
     */
    public static function attempts(string $ip): int
    {
        return (int)Cache::read(self::throttleKey($ip), self::CACHE_CONFIG);
    }

    /**
     * Whether the given client IP is currently locked out.
     *
     * @param string $ip The client IP.
     * @return bool
     */
    public static function isLockedOut(string $ip): bool
    {
        return self::attempts($ip) >= self::MAX_ATTEMPTS;
    }

    /**
     * Record one more failed attempt for a client IP.
     *
     * @param string $ip The client IP.
     * @return void
     */
    public static function registerFailure(string $ip): void
    {
        Cache::write(self::throttleKey($ip), self::attempts($ip) + 1, self::CACHE_CONFIG);
    }

    /**
     * Reset the counter for a client IP (called on successful login).
     *
     * @param string $ip The client IP.
     * @return void
     */
    public static function clear(string $ip): void
    {
        Cache::delete(self::throttleKey($ip), self::CACHE_CONFIG);
    }
}
