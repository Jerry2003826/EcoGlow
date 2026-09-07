<?php
// config/routes.php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

    $routes->connect('/login', ['controller' => 'Users', 'action' => 'login']);
    $routes->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);

    $routes->connect('/shop', ['controller' => 'Products', 'action' => 'index']);
    $routes->connect('/products', ['controller' => 'Products', 'action' => 'index']);
    $routes->connect('/cart', ['controller' => 'Cart', 'action' => 'index']);
    $routes->connect('/checkout', ['controller' => 'Checkout', 'action' => 'index']);

    $routes->connect(
        '/orders/receipt/{id}',
        ['controller' => 'Orders', 'action' => 'receipt']
    )->setPass(['id']);

    $routes->connect('/services', ['controller' => 'Pages', 'action' => 'display', 'services']);
    $routes->connect('/about', ['controller' => 'Pages', 'action' => 'display', 'about']);

    $routes->prefix('Admin', function (RouteBuilder $routes): void {
        $routes->connect('/', [
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);

        $routes->connect('/orders', [
            'controller' => 'Orders',
            'action' => 'index',
        ]);

        foreach ([
            'refunds',
            'reports',
            'access',
        ] as $module) {
            $routes->connect(
                '/' . $module,
                [
                    'controller' => 'ComingSoon',
                    'action' => 'index',
                    'module' => $module,
                ]
            )->setPass(['module']);
        }

        $routes->connect(
            '/coming-soon/{module}',
            [
                'controller' => 'ComingSoon',
                'action' => 'index',
            ]
        )->setPatterns([
            'module' => '[a-z0-9\-]+',
        ])->setPass(['module']);

        $routes->fallbacks();
    });

    $routes->fallbacks();
};
