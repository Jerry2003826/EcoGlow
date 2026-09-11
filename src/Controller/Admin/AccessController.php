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
        $usersTable = $this->fetchTable('Users');

        if ($this->request->is('post')) {
            $form = (string)$this->request->getData('form');

            if ($form === 'modules') {
                $allowed = array_map('strval', (array)$this->request->getData('allowed'));
                $service->saveStaffModules($allowed);
                $this->Flash->success('Staff access updated.');

                return $this->redirect(['action' => 'index']);
            }

            if ($form === 'role') {
                $targetUserId = (int)$this->request->getData('UserId');
                $role = (string)$this->request->getData('UserRole');

                if ($targetUserId === (int)$identity->getIdentifier()) {
                    $this->Flash->error('You cannot change your own role.');

                    return $this->redirect(['action' => 'index']);
                }

                if (!in_array($role, ['admin', 'staff', 'customer'], true)) {
                    $this->Flash->error('That role is not valid.');

                    return $this->redirect(['action' => 'index']);
                }

                $user = $usersTable->get($targetUserId);
                $user->UserRole = $role;
                $usersTable->saveOrFail($user);
                $this->Flash->success('Role updated for ' . (string)$user->Email . '.');

                return $this->redirect(['action' => 'index']);
            }
        }

        $modules = $service->staffModules();
        $users = $usersTable->find()
            ->select(['UserId', 'Username', 'Email', 'UserRole'])
            ->orderBy(['UserRole' => 'ASC', 'Email' => 'ASC'])
            ->all();

        $this->set(compact('modules', 'users'));
    }
}
