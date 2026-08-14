<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\Security\RateLimitService;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Blocks locked-out register / contact / checkout POSTs before work starts.
 */
final class AbuseThrottleMiddleware implements MiddlewareInterface
{
    public const SCOPE_REGISTER = 'register';

    public const SCOPE_CONTACT = 'contact';

    public const SCOPE_CHECKOUT = 'checkout';

    public const MAX_REGISTER = 5;

    public const MAX_CONTACT = 5;

    public const MAX_CHECKOUT_IP = 20;

    public const MAX_CHECKOUT_USER = 8;

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return $handler->handle($request);
        }

        $path = rtrim($request->getUri()->getPath(), '/') ?: '/';
        $ip = $this->clientIp($request);

        if ($path === '/register') {
            if (RateLimitService::locked(self::SCOPE_REGISTER, $ip, self::MAX_REGISTER)) {
                return $this->reject('/register', 429);
            }
            RateLimitService::hit(self::SCOPE_REGISTER, $ip);
        }
        if ($path === '/contact') {
            if (RateLimitService::locked(self::SCOPE_CONTACT, $ip, self::MAX_CONTACT)) {
                return $this->reject('/contact', 429);
            }
            RateLimitService::hit(self::SCOPE_CONTACT, $ip);
        }
        if ($path === '/checkout') {
            if (RateLimitService::locked(self::SCOPE_CHECKOUT, $ip, self::MAX_CHECKOUT_IP)) {
                return $this->reject('/checkout', 429);
            }
            RateLimitService::hit(self::SCOPE_CHECKOUT, $ip);
        }

        return $handler->handle($request);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request.
     * @return string
     */
    private function clientIp(ServerRequestInterface $request): string
    {
        if ($request instanceof ServerRequest) {
            return $request->clientIp() ?: 'unknown';
        }

        $params = $request->getServerParams();

        return (string)($params['REMOTE_ADDR'] ?? 'unknown') ?: 'unknown';
    }

    /**
     * @param string $location Redirect target.
     * @param int $status HTTP status.
     * @return \Cake\Http\Response
     */
    private function reject(string $location, int $status): Response
    {
        return (new Response())
            ->withStatus($status)
            ->withHeader('Location', $location)
            ->withHeader('Retry-After', '900');
    }
}
