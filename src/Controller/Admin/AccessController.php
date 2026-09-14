<?php
// src/Controller/Admin/AccessController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class AccessController extends AdminController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return;
        }

        if ((string)$identity->get('UserRole') !== 'admin') {
            throw new ForbiddenException('Forbidden');
        }
    }

    public function index()
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return;
        }

        $service = new PermissionService();
        $service->ensureTable();

        if ($this->request->is('post')) {
            $form = (string)$this->request->getData('form');

            if ($form === 'modules') {
                $allowed = array_map('strval', (array)$this->request->getData('allowed'));
                $service->saveStaffModules($allowed);
                $this->Flash->success('Staff access updated.');

                return $this->redirect(['action' => 'index']);
            }
        }

        $modules = $service->staffModules();

        $this->set(compact('modules'));
    }
}
