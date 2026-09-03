<?php

declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->connect('/login', [
        'controller' => 'Users',
        'action' => 'login',
    ]);

    $routes->connect('/logout', [
        'controller' => 'Users',
        'action' => 'logout',
    ]);

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', [
            'controller' => 'Pages',
            'action' => 'display',
            'home',
        ]);

        $builder->fallbacks();
    });

    $routes->prefix('Admin', function (RouteBuilder $builder): void {
        $builder->connect('/', [
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);

        $builder->connect(
            '/coming-soon/{module}',
            [
                'controller' => 'ComingSoon',
                'action' => 'index',
            ]
        )
        ->setPatterns([
            'module' => '[a-z0-9\-]+',
        ])
        ->setPass(['module']);

        $builder->fallbacks();
    });
};
