<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Test\TestCase;

use App\Application;
use App\Middleware\ContentSecurityPolicyMiddleware;
use App\Middleware\HostHeaderMiddleware;
use App\Middleware\TrustedProxyMiddleware;
use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\Middleware\HttpsEnforcerMiddleware;
use Cake\Http\Middleware\SecurityHeadersMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * ApplicationTest class
 */
class ApplicationTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test bootstrap in production.
     *
     * @return void
     */
    public function testBootstrap()
    {
        Configure::write('debug', false);
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $plugins = $app->getPlugins();

        $this->assertTrue($plugins->has('Bake'), 'plugins has Bake?');
        $this->assertFalse($plugins->has('DebugKit'), 'plugins has DebugKit?');
        $this->assertTrue($plugins->has('Migrations'), 'plugins has Migrations?');
    }

    /**
     * Test bootstrap add DebugKit plugin in debug mode.
     *
     * @return void
     */
    public function testBootstrapInDebug()
    {
        Configure::write('debug', true);
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $plugins = $app->getPlugins();

        $this->assertTrue($plugins->has('DebugKit'), 'plugins has DebugKit?');
    }

    /**
     * testMiddleware
     *
     * In debug mode the HTTPS enforcer is left out entirely so the local dev
     * server stays reachable over plain HTTP.
     *
     * @return void
     */
    public function testMiddleware()
    {
        Configure::write('debug', true);
        $app = new Application(dirname(__DIR__, 2) . '/config');

        $classes = $this->middlewareClasses($app->middleware(new MiddlewareQueue()));

        $this->assertSame([
            ErrorHandlerMiddleware::class,
            TrustedProxyMiddleware::class,
            HostHeaderMiddleware::class,
            SecurityHeadersMiddleware::class,
            ContentSecurityPolicyMiddleware::class,
            AssetMiddleware::class,
            RoutingMiddleware::class,
        ], array_slice($classes, 0, 7));
        $this->assertNotContains(HttpsEnforcerMiddleware::class, $classes);
    }

    /**
     * The HTTPS enforcer is only queued outside debug mode, and it has to run
     * after the Host header is validated but before anything is routed or
     * served.
     *
     * @return void
     */
    public function testMiddlewareEnforcesHttpsOutsideDebug()
    {
        Configure::write('debug', false);
        $app = new Application(dirname(__DIR__, 2) . '/config');

        $classes = $this->middlewareClasses($app->middleware(new MiddlewareQueue()));

        $this->assertSame([
            ErrorHandlerMiddleware::class,
            TrustedProxyMiddleware::class,
            HostHeaderMiddleware::class,
            SecurityHeadersMiddleware::class,
            ContentSecurityPolicyMiddleware::class,
            HttpsEnforcerMiddleware::class,
            AssetMiddleware::class,
            RoutingMiddleware::class,
        ], array_slice($classes, 0, 8));
    }

    /**
     * Class names of every middleware in a queue, in execution order.
     *
     * @param \Cake\Http\MiddlewareQueue $queue The queue to walk.
     * @return array<int, string>
     */
    protected function middlewareClasses(MiddlewareQueue $queue): array
    {
        $classes = [];
        foreach ($queue as $middleware) {
            $classes[] = $middleware::class;
        }

        return $classes;
    }
}
