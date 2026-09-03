<?php

declare(strict_types=1);

namespace App;

use App\Middleware\HostHeaderMiddleware;
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
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin('Authentication');
        $this->addPlugin('Authorization');
    }

    public function services(ContainerInterface $container): void
    {
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new ErrorHandlerMiddleware(
                Configure::read('Error'),
                $this
            ))
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new HostHeaderMiddleware())
            ->add(new AuthenticationMiddleware($this))
            ->add(new AuthorizationMiddleware($this, [
                'requireAuthorizationCheck' => false,
                'identityDecorator' => \App\Identity\PermissionIdentity::class,
            ]))
            ->add(new RequestAuthorizationMiddleware())
            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
            ]));

        return $middlewareQueue;
    }

    public function getAuthenticationService(
        ServerRequestInterface $request
    ): AuthenticationServiceInterface {
        $fields = [
            'username' => 'email',
            'password' => 'password',
        ];

        $service = new AuthenticationService([
            'unauthenticatedRedirect' => '/login',
            'queryParam' => 'redirect',
        ]);

        $service->loadAuthenticator('Authentication.Session');

        $service->loadAuthenticator('Authentication.Form', [
            'fields' => $fields,
            'loginUrl' => '/login',
            'identifier' => [
                'className' => 'Authentication.Password',
                'fields' => $fields,
                'resolver' => [
                    'className' => 'Authentication.Orm',
                    'userModel' => 'Users',
                    'finder' => 'activeAuth',
                ],
            ],
        ]);

        return $service;
    }

    public function getAuthorizationService(
        ServerRequestInterface $request
    ): AuthorizationServiceInterface {
        $resolver = new MapResolver();

        $resolver->map(
            ServerRequest::class,
            \App\Policy\RequestPolicy::class
        );

        return new AuthorizationService($resolver);
    }
}
