<?php
// src/Controller/Admin/AccessController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class AccessController extends AdminController
{
    protected function requireAccessPermission(): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }
        if ($identity->get('UserRole') == 'staff') {
            throw new ForbiddenException('Forbidden');
        }

        $service = new PermissionService();

        if (!$service->hasPermission(
            (int)$identity->getIdentifier(),
            'access.manage'
        )) {
            throw new ForbiddenException('Forbidden');
        }
    }

    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->requireAccessPermission();

    }



    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $usersTable = $this->fetchTable('Users');

        if ($this->request->is('post')) {
            $targetUserId = (int)$this->request->getData('user_id');
            $roleKey = (string)$this->request->getData('role_key');

            if ($targetUserId === (int)$identity->getIdentifier()) {
                $this->Flash->error('You cannot change your own role.');
                return $this->redirect(['action' => 'index']);
            }

            $userRoles = $this->fetchTable('UserRoles');
            $roles = $this->fetchTable('Roles');

            $role = $roles->find()
                ->where(['role_key' => $roleKey])
                ->first();

            if ($role) {
                $userRoles->deleteAll(['user_id' => $targetUserId]);

                $entity = $userRoles->newEntity([
                    'user_id' => $targetUserId,
                    'role_id' => $role->id,
                ]);

                $userRoles->save($entity);

                $this->Flash->success('Role updated.');
            }
        }

        $users = $usersTable->find()
            ->contain(['UserRoles.Roles'])
            ->orderBy(['Users.email' => 'ASC'])
            ->all();

        $roles = $this->fetchTable('Roles')
            ->find()
            ->orderBy(['Roles.name' => 'ASC'])
            ->all();

        $this->set(compact('users', 'roles'));
    }
}
