<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        /*
         * Here, we are connecting '/' (base path) to a controller called 'Pages',
         * its action called 'display', and we pass a param to select the view file
         * to use (in this case, templates/Pages/home.php)...
         */
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

        /*
         * ...and connect the rest of 'Pages' controller's URLs.
         */
        /*
         * Storefront. These are static templates under templates/Pages for now:
         * there is no products table, so PagesController::display renders each
         * one and the placeholder catalogue lives in the template. Connected
         * explicitly rather than left to `/pages/*` so the public URLs are the
         * ones the finished shop will keep, and so the templates can link to
         * each other without a `/pages/` prefix that later has to be unpicked.
         *
         * `/shop/product` grows a slug segment (`/shop/product/*`) once there
         * is a record to look up.
         */
        $builder->connect('/shop', ['controller' => 'Shop', 'action' => 'index']);
        $builder->connect('/shop/product', ['controller' => 'Shop', 'action' => 'product']);
        $builder->connect(
            '/shop/product/{slug}',
            ['controller' => 'Shop', 'action' => 'product'],
        )
            ->setPass(['slug'])
            ->setPatterns(['slug' => '[a-z0-9-]+']);
        $builder->connect('/cart', ['controller' => 'Carts', 'action' => 'index']);
        $builder->connect('/cart/add', ['controller' => 'Carts', 'action' => 'add']);
        $builder->connect('/cart/update', ['controller' => 'Carts', 'action' => 'update']);
        $builder->connect('/cart/remove', ['controller' => 'Carts', 'action' => 'remove']);
        $builder->connect('/cart/save-later', ['controller' => 'Carts', 'action' => 'saveLater']);
        $builder->connect('/cart/move-to-cart', ['controller' => 'Carts', 'action' => 'moveToCart']);
        $builder->connect('/checkout', ['controller' => 'Checkout', 'action' => 'index']);
        $builder->connect('/checkout/confirmation/{id}', ['controller' => 'Checkout', 'action' => 'confirmation'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\d+']);
        $builder->connect('/webhooks/stripe', ['controller' => 'Webhooks', 'action' => 'stripe']);
        $builder->connect('/services/book', ['controller' => 'Services', 'action' => 'book']);
        $builder->connect('/register', ['controller' => 'Users', 'action' => 'register']);
        $builder->connect('/account/login', ['controller' => 'Users', 'action' => 'customerLogin']);
        $builder->connect('/account', ['controller' => 'Account', 'action' => 'index']);
        $builder->connect('/account/addresses', ['controller' => 'Account', 'action' => 'addresses']);
        $builder->connect('/account/addresses/add', ['controller' => 'Account', 'action' => 'addAddress']);
        $builder->connect('/account/addresses/delete/{id}', ['controller' => 'Account', 'action' => 'deleteAddress'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\d+']);
        $builder->connect('/account/orders', ['controller' => 'Account', 'action' => 'orders']);
        $builder->connect('/account/orders/{id}', ['controller' => 'Account', 'action' => 'order'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\d+']);
        $builder->connect('/account/bookings', ['controller' => 'Account', 'action' => 'bookings']);
        $builder->connect('/account/bookings/{id}', ['controller' => 'Account', 'action' => 'booking'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\d+']);

        /*
         * Public contact form.
         */
        $builder->connect('/contact', ['controller' => 'Contact', 'action' => 'index']);

        /*
         * Admin authentication.
         */
        $builder->connect('/login', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/login/mfa', ['controller' => 'Users', 'action' => 'mfa']);
        $builder->connect('/login/mfa-setup', ['controller' => 'Users', 'action' => 'mfaSetup']);
        $builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/verify-email/*', ['controller' => 'Users', 'action' => 'verifyEmail']);
        $builder->connect('/account/confirm-email/*', ['controller' => 'Account', 'action' => 'confirmEmail']);
        $builder->connect('/account/resend-verification', ['controller' => 'Account', 'action' => 'resendVerification']);

        /*
         * Self-service password reset. The trailing `*` carries the token
         * from the emailed link as a passed argument.
         */
        $builder->connect('/forgot-password', ['controller' => 'Users', 'action' => 'forgotPassword']);
        $builder->connect('/reset-password/*', ['controller' => 'Users', 'action' => 'resetPassword']);

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * It is NOT recommended to use fallback routes after your initial prototyping phase!
         * See https://book.cakephp.org/5/en/development/routing.html#fallbacks-method for more information
         */
    });

    /*
     * Admin area. All routes here require an authenticated user.
     */
    $routes->prefix('Admin', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $builder->connect(
            '/coming-soon/{module}',
            ['controller' => 'ComingSoon', 'action' => 'index'],
        )
            ->setPass(['module'])
            ->setPatterns(['module' => '[a-z0-9-]+']);
        $builder->fallbacks(DashedRoute::class);
    });

    /*
     * If you need a different set of middleware or none at all,
     * open new scope and define routes there.
     *
     * ```
     * $routes->scope('/api', function (RouteBuilder $builder): void {
     *     // No $builder->applyMiddleware() here.
     *
     *     // Parse specified extensions from URLs
     *     // $builder->setExtensions(['json', 'xml']);
     *
     *     // Connect API actions here.
     * });
     * ```
     */
};
