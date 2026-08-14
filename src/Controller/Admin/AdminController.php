<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Service\Authorization\PermissionService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Shared admin layout, identity extras and permission helper.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AdminController extends AppController
{
    /**
     * @var \App\Service\Authorization\PermissionService
     */
    protected PermissionService $permissions;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->permissions = new PermissionService();
        $this->viewBuilder()->setLayout('admin');
        $this->viewBuilder()->addHelper('Money');
    }

    /**
     * @inheritDoc
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $identity = $this->request->getAttribute('identity');
        $adminUserEmail = '';
        $adminRoleNames = [];
        if ($identity !== null) {
            $adminUserEmail = (string)$identity['email'];
            $roles = $this->permissions->rolesFor((int)$identity->getIdentifier());
            $adminRoleNames = array_values(array_filter(array_map(
                static fn(array $role): string => (string)$role['name'],
                $roles,
            )));
        }

        $this->set(compact('adminUserEmail', 'adminRoleNames'));
        $this->set('adminNav', $this->adminNavigation());
        $this->set('adminCurrent', [
            'controller' => (string)$this->request->getParam('controller'),
            'action' => (string)$this->request->getParam('action'),
            'pass' => $this->request->getParam('pass') ?? [],
        ]);
    }

    /**
     * Sidebar groups. Later batches keep the same keys and swap coming-soon
     * URLs for real controllers without restyling the shell.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function adminNavigation(): array
    {
        return [
            [
                'label' => 'Operations',
                'items' => [
                    $this->navItem('Dashboard', 'Dashboard'),
                    $this->navItem('Orders', 'Orders'),
                    $this->navItem('Appointments', 'Appointments'),
                ],
            ],
            [
                'label' => 'Catalogue',
                'items' => [
                    $this->comingSoonItem('Products', 'products'),
                    $this->navItem('Inventory', 'Inventory'),
                ],
            ],
            [
                'label' => 'Customers',
                'items' => [
                    $this->navItem('Customers', 'Customers'),
                    $this->navItem('Messages', 'ContactMessages'),
                ],
            ],
            [
                'label' => 'Finance',
                'items' => [
                    $this->navItem('Invoices', 'Invoices'),
                    $this->comingSoonItem('Quotations', 'quotations'),
                    $this->navItem('Reports', 'Reports'),
                ],
            ],
            [
                'label' => 'Settings',
                'items' => [
                    $this->navItem('Users & roles', 'Users'),
                    $this->comingSoonItem('Feature flags', 'feature-flags'),
                ],
            ],
        ];
    }

    /**
     * Live admin destination.
     *
     * @param string $label Sidebar label.
     * @param string $controller Controller name.
     * @param string $action Action name.
     * @return array<string, mixed>
     */
    private function navItem(string $label, string $controller, string $action = 'index'): array
    {
        return [
            'label' => $label,
            'controller' => $controller,
            'action' => $action,
            'url' => ['controller' => $controller, 'action' => $action],
        ];
    }

    /**
     * Later-batch destination that must not be a dead link.
     *
     * @param string $label Sidebar label.
     * @param string $module Coming-soon module key.
     * @return array<string, mixed>
     */
    private function comingSoonItem(string $label, string $module): array
    {
        return [
            'label' => $label,
            'controller' => 'ComingSoon',
            'module' => $module,
            'url' => ['controller' => 'ComingSoon', 'action' => 'index', $module],
        ];
    }

    /**
     * Authenticated staff user id.
     *
     * @return int
     */
    protected function actorId(): int
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            throw new ForbiddenException();
        }

        return (int)$identity->getIdentifier();
    }

    /**
     * Extra permission gate used when one action covers two keys
     * (status dispatch vs manage).
     *
     * @param string $permissionKey Existing permissions.permission_key.
     * @return void
     */
    protected function requirePermission(string $permissionKey): void
    {
        if (!$this->permissions->has($this->actorId(), $permissionKey)) {
            throw new ForbiddenException(__('You do not have permission to do that.'));
        }
    }

    /**
     * Whether the actor may see unmasked customer email and phone.
     *
     * customers.view_contact is not seeded, so customers.view is the gate.
     *
     * @return bool
     */
    protected function canViewCustomerContact(): bool
    {
        return $this->permissions->has($this->actorId(), 'customers.view');
    }

    /**
     * Count rows grouped by a single column.
     *
     * @param string $table Table name.
     * @param string $field Column to group by.
     * @param array<string, mixed> $where Optional extra conditions.
     * @return array<string, int>
     */
    protected function countByField(string $table, string $field, array $where = []): array
    {
        $query = $this->fetchTable($table)->find();
        if ($where !== []) {
            $query->where($where);
        }
        $rows = $query
            ->select([$field, 'c' => $query->func()->count('*')])
            ->groupBy([$field])
            ->enableHydration(false)
            ->all();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string)$row[$field]] = (int)$row['c'];
        }

        return $counts;
    }
}
