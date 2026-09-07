<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Service\PermissionService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class AdminController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setLayout('admin');
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }
    }

    protected function requirePermission(string $permission): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (
            !$permissionService->hasPermission(
                (int)$identity->getIdentifier(),
                $permission
            )
        ) {
            throw new ForbiddenException('Forbidden');
        }
    }
}
