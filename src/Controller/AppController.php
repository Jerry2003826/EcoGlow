<?php
// src/Controller/AppController.php

declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $prefix = (string)$this->request->getParam('prefix');
        $controller = (string)$this->request->getParam('controller');

        if (
            $prefix !== 'Admin' &&
            in_array(
                $controller,
                [
                    'Products',
                    'Cart',
                    'Checkout',
                    'Orders',
                    'Pages',
                    'Enquiries',
                ],
                true
            )
        ) {
            $this->Authentication->allowUnauthenticated([
                'display',
                'index',
                'receipt',
                'add',
            ]);
        }
    }
}
