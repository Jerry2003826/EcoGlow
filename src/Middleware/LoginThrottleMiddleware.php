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
 *
 * The same counters also back the "forgot password" throttle under a separate
 * scope (see SCOPE_PASSWORD_RESET); that flow needs no middleware because no
 * identity is established, so the controller enforces it directly.
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
     * Counter scope for failed sign-in attempts.
     *
     * @var string
     */
    public const SCOPE_LOGIN = 'login';

    /**
     * Counter scope for "forgot password" submissions.
     *
     * Reset requests are throttled with the same counters so that the flow
     * cannot be abused to mail-bomb an inbox or to probe for valid addresses;
     * a separate scope keeps it from interfering with the login lockout.
     *
     * @var string
     */
    public const SCOPE_PASSWORD_RESET = 'password_reset';

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
     * @param string $scope Counter scope, one of the SCOPE_* constants.
     * @return string
     */
    public static function throttleKey(string $ip, string $scope = self::SCOPE_LOGIN): string
    {
        return $scope . '_' . hash('sha256', $ip !== '' ? $ip : 'unknown');
    }

    /**
     * Current attempt count for a client IP.
     *
     * @param string $ip The client IP.
     * @param string $scope Counter scope, one of the SCOPE_* constants.
     * @return int
     */
    public static function attempts(string $ip, string $scope = self::SCOPE_LOGIN): int
    {
        return (int)Cache::read(self::throttleKey($ip, $scope), self::CACHE_CONFIG);
    }

    /**
     * Whether the given client IP is currently locked out.
     *
     * @param string $ip The client IP.
     * @param string $scope Counter scope, one of the SCOPE_* constants.
     * @return bool
     */
    public static function isLockedOut(string $ip, string $scope = self::SCOPE_LOGIN): bool
    {
        return self::attempts($ip, $scope) >= self::MAX_ATTEMPTS;
    }

    /**
     * Record one more attempt for a client IP.
     *
     * @param string $ip The client IP.
     * @param string $scope Counter scope, one of the SCOPE_* constants.
     * @return void
     */
    public static function registerAttempt(string $ip, string $scope = self::SCOPE_LOGIN): void
    {
        Cache::write(self::throttleKey($ip, $scope), self::attempts($ip, $scope) + 1, self::CACHE_CONFIG);
    }

    /**
     * Record one more failed sign-in attempt for a client IP.
     *
     * @param string $ip The client IP.
     * @return void
     */
    public static function registerFailure(string $ip): void
    {
        self::registerAttempt($ip, self::SCOPE_LOGIN);
    }

    /**
     * Reset the counter for a client IP (called on successful login).
     *
     * @param string $ip The client IP.
     * @param string $scope Counter scope, one of the SCOPE_* constants.
     * @return void
     */
    public static function clear(string $ip, string $scope = self::SCOPE_LOGIN): void
    {
        Cache::delete(self::throttleKey($ip, $scope), self::CACHE_CONFIG);
    }
}
