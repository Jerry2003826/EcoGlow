<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use App\Middleware\HostHeaderMiddleware;
use App\Middleware\LoginThrottleMiddleware;
use App\Policy\RequestPolicy;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use Authorization\Policy\MapResolver;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Middleware\HttpsEnforcerMiddleware;
use Cake\Http\Middleware\SecurityHeadersMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\TableContainer;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 *
 * @extends \Cake\Http\BaseApplication<\App\Application>
 */
class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        // By default, does not allow fallback classes.
        FactoryLocator::add(
            'Table',
            (new TableLocator())->allowFallbackClass(false),
        );
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))

            // Validate Host header to prevent Host Header Injection attacks.
            // In production, ensures App.fullBaseUrl is configured and validates
            // the incoming Host header against it.
            ->add(new HostHeaderMiddleware())

            // Send hardening response headers on every response. X-Frame-Options
            // mitigates clickjacking of the admin's postLink delete buttons and
            // nosniff stops MIME-type confusion attacks.
            ->add((new SecurityHeadersMiddleware())
                ->setReferrerPolicy()
                ->setXFrameOptions('sameorigin')
                ->noSniff());

        // Force HTTPS in production only; enabling it locally would redirect
        // the dev server at http://localhost:8765 away. It sits behind
        // HostHeaderMiddleware so only a validated Host can be redirected to,
        // and behind SecurityHeadersMiddleware so the 301 still carries the
        // hardening headers.
        if (!Configure::read('debug')) {
            $middlewareQueue->add(new HttpsEnforcerMiddleware([
                // cPanel terminates TLS at its reverse proxy, so PHP only ever
                // sees plain HTTP and would redirect forever. An empty list
                // trusts X-Forwarded-Proto without pinning proxy IPs, which are
                // not fixed on shared hosting; ServerRequest::clientIp() then
                // reads the last X-Forwarded-For entry, the one the fronting
                // proxy appends, so LoginThrottleMiddleware cannot be bypassed
                // with a forged header.
                'trustedProxies' => [],
                // Sibling subdomains on the shared host are not ours to pin,
                // and preload is effectively irreversible.
                'hsts' => [
                    'maxAge' => 31536000,
                    'includeSubDomains' => false,
                    'preload' => false,
                ],
            ]));
        }

        $middlewareQueue
            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance.
            // See https://github.com/CakeDC/cakephp-cached-routing
            ->add(new RoutingMiddleware($this))

            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData()
            // https://book.cakephp.org/5/en/controllers/middleware.html#body-parser-middleware
            ->add(new BodyParserMiddleware())

            // Cross Site Request Forgery (CSRF) Protection Middleware
            // https://book.cakephp.org/5/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
            // `secure` is tied to debug so the cookie is HTTPS-only in
            // production while still working over plain HTTP in local dev.
            // Stripe webhooks have no CSRF cookie; skip that one path only.
            ->add((new CsrfProtectionMiddleware([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !Configure::read('debug'),
            ]))->skipCheckCallback(function (ServerRequestInterface $request): bool {
                return rtrim($request->getUri()->getPath(), '/') === '/webhooks/stripe';
            }))

            // Block locked-out login attempts *before* authentication so a
            // correct password cannot be persisted to the session during a
            // brute-force lockout window.
            ->add(new LoginThrottleMiddleware())

            // Authentication middleware, after routing so that URL params
            // are available to the authentication service.
            ->add(new AuthenticationMiddleware($this))

            // Authorization after authentication so the identity can be decorated.
            ->add(new AuthorizationMiddleware($this, [
                'requireAuthorizationCheck' => true,
                'unauthorizedHandler' => 'Authorization.Exception',
            ]))
            ->add(new RequestAuthorizationMiddleware());

        return $middlewareQueue;
    }

    /**
     * Returns an authentication service instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $fields = [
            'username' => 'email',
            'password' => 'password',
        ];

        $path = $request->getUri()->getPath();
        $customerArea = str_starts_with($path, '/account')
            || str_starts_with($path, '/checkout')
            || str_starts_with($path, '/services')
            || $path === '/register';
        $loginUrl = $customerArea ? '/account/login' : '/login';
        $authenticationService = new AuthenticationService([
            'unauthenticatedRedirect' => $loginUrl,
            'queryParam' => 'redirect',
            'authenticators' => [
                'Authentication.Session',
                'Authentication.Form' => [
                    'fields' => $fields,
                    // DefaultUrlChecker compares a single path. Pin it to the
                    // login form that belongs to this request so POST /register
                    // (which also has email + password) cannot authenticate.
                    'loginUrl' => $loginUrl,
                    'identifier' => [
                        'className' => 'Authentication.Password',
                        'fields' => $fields,
                    ],
                ],
            ],
        ]);

        return $authenticationService;
    }

    /**
     * Returns an authorization service that resolves request policies from the database.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        $resolver = new MapResolver([
            ServerRequest::class => RequestPolicy::class,
        ]);

        return new AuthorizationService($resolver);
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        // Allow your Tables to be dependency injected
        $container->delegate(new TableContainer());
    }

    /**
     * Register custom event listeners here
     *
     * @param \Cake\Event\EventManagerInterface $eventManager The Event Manager to update.
     * @return \Cake\Event\EventManagerInterface
     * @link https://book.cakephp.org/5/en/core-libraries/events.html#registering-listeners
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        // $eventManager->on(new SomeCustomListenerClass());

        return $eventManager;
    }
}
