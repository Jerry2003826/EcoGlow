<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Browser CSP. Report-Only until CSP_ENFORCE=true after a collection window.
 */
final class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);
        $policy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://js.stripe.com https://www.google.com https://www.gstatic.com",
            'frame-src https://js.stripe.com https://hooks.stripe.com https://www.google.com',
            "connect-src 'self' https://api.stripe.com https://www.google.com",
            "img-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            'report-uri /csp-report',
        ]);
        $header = filter_var(env('CSP_ENFORCE', false), FILTER_VALIDATE_BOOLEAN)
            && !Configure::read('debug')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        return $response->withHeader($header, $policy);
    }
}
