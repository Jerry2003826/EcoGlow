<?php
// src/Controller/Admin/AccessController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class AccessController extends AdminController
{
    /**
     * Restrict this page to admin users.
     *
     * @param \Cake\Event\EventInterface $event Filter event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
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

    /**
     * Show and save global staff modules and per-user overrides.
     *
     * @return \Cake\Http\Response|null|void
     */
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

            if ($form === 'user') {
                $userId = (int)$this->request->getData('userId');
                $allowed = array_map('strval', (array)$this->request->getData('allowed'));

                $staffUser = $this->fetchTable('Users')
                    ->find()
                    ->where([
                        'UserId' => $userId,
                        'UserRole' => 'staff',
                        'Status' => 'active',
                    ])
                    ->first();

                if ($staffUser) {
                    $service->saveUserOverrides($userId, $allowed);
                    $this->Flash->success('Per-staff access updated.');
                } else {
                    $this->Flash->error('That staff account could not be updated.');
                }

                return $this->redirect(['action' => 'index']);
            }
        }

        $modules = $service->staffModules();

        $staffUsers = $this->fetchTable('Users')
            ->find()
            ->where([
                'UserRole' => 'staff',
                'Status' => 'active',
            ])
            ->orderBy([
                'Username' => 'ASC',
                'Email' => 'ASC',
            ])
            ->all();

        $staffOverrides = [];
        foreach ($staffUsers as $staffUser) {
            $staffOverrides[(int)$staffUser->UserId] = $service->userOverrides((int)$staffUser->UserId);
        }

        $permissionOptions = [];
        foreach ($service->permissionMap() as $key => $label) {
            if ($key === 'access.manage' || $key === 'dashboard.view') {
                continue;
            }

            $permissionOptions[$key] = $label;
        }

        $this->set(compact('modules', 'staffUsers', 'staffOverrides', 'permissionOptions'));
    }
}
