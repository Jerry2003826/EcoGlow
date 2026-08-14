<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Trust proxy headers only when the immediate peer is explicitly allowlisted.
 *
 * The web server/proxy must also overwrite client-supplied forwarded headers.
 * This class intentionally accepts exact IP addresses only; network ranges
 * belong in the firewall or reverse-proxy configuration.
 */
final class TrustedProxyMiddleware implements MiddlewareInterface
{
    /**
     * @param array<int, string> $trustedProxies Exact proxy source IPs.
     */
    public function __construct(private array $trustedProxies)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$request instanceof ServerRequest) {
            return $handler->handle($request);
        }

        $server = $request->getServerParams();
        $remoteAddress = trim((string)($server['REMOTE_ADDR'] ?? ''));
        $trusted = $remoteAddress !== ''
            && in_array($remoteAddress, $this->trustedProxies, true);

        if ($trusted) {
            $request->setTrustedProxies($this->trustedProxies);

            return $handler->handle($request);
        }

        $headers = [
            'Forwarded',
            'X-Forwarded-For',
            'X-Forwarded-Host',
            'X-Forwarded-Port',
            'X-Forwarded-Proto',
            'X-Real-IP',
            'Client-IP',
        ];
        foreach ($headers as $header) {
            $request = $request->withoutHeader($header);
        }

        return $handler->handle($request);
    }
}
