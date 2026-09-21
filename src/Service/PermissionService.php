<?php
// src/Service/PermissionService.php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

class PermissionService
{
    protected array $defaultAllow = [
        'dashboard.view',
        'orders.view',
        'customers.view',
        'invoices.issue',
        'inventory.view',
        'inventory.adjust',
        'messages.manage',
        'quotations.manage',
        'shipments.view',
        'payments.view',
        'products.view',
        'refunds.process',
    ];

    /**
     * Return the permission key labels.
     *
     * @return array<string, string>
     */
    public function permissionMap(): array
    {
        return [
            'dashboard.view' => 'Dashboard',
            'orders.view' => 'Orders',
            'customers.view' => 'Customers',
            'invoices.issue' => 'Invoices',
            'inventory.view' => 'Inventory',
            'inventory.adjust' => 'Edit stock',
            'messages.manage' => 'Messages',
            'quotations.manage' => 'Quotations',
            'shipments.view' => 'Shipments',
            'payments.view' => 'Payments',
            'products.view' => 'Products',
            'refunds.process' => 'Refunds',
            'suppliers.view' => 'Suppliers',
            'reports.view' => 'Reports',
            'access.manage' => 'Access',
            'content.manage' => 'Landing page content',
        ];
    }

    /**
     * Create staff access tables when they are missing.
     *
     * @return void
     */
    public function ensureTable(): void
    {
        $connection = ConnectionManager::get('default');

        $connection->execute(
            'CREATE TABLE IF NOT EXISTS StaffAccess (
                AccessId INT AUTO_INCREMENT PRIMARY KEY,
                PermissionKey VARCHAR(100) NOT NULL UNIQUE,
                ModuleLabel VARCHAR(150) NOT NULL,
                Allowed TINYINT(1) NOT NULL DEFAULT 0
            )',
        );

        $connection->execute(
            'CREATE TABLE IF NOT EXISTS StaffUserAccess (
                UserAccessId INT AUTO_INCREMENT PRIMARY KEY,
                UserId INT NOT NULL,
                PermissionKey VARCHAR(100) NOT NULL,
                Allowed TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE KEY uq_user_permission (UserId, PermissionKey),
                CONSTRAINT fk_staff_user_access_user FOREIGN KEY (UserId)
                    REFERENCES `User`(`UserId`) ON DELETE CASCADE
            )',
        );

        $count = (int)($connection
            ->execute('SELECT COUNT(*) AS c FROM StaffAccess')
            ->fetch('assoc')['c'] ?? 0);

        if ($count === 0) {
            foreach ($this->permissionMap() as $key => $label) {
                if ($key === 'access.manage' || $key === 'content.manage') {
                    continue;
                }

                $connection->execute(
                    'INSERT INTO StaffAccess (PermissionKey, ModuleLabel, Allowed) VALUES (?, ?, ?)',
                    [
                        $key,
                        $label,
                        in_array($key, $this->defaultAllow, true) ? 1 : 0,
                    ],
                );
            }
        }
    }

    /**
     * Check whether a user is allowed a permission key.
     *
     * Staff users can have a per-user StaffUserAccess row that overrides the
     * global StaffAccess switch. access.manage stays admin-only.
     *
     * @param int $userId User id.
     * @param string $permission Permission key.
     * @return bool
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $user = TableRegistry::getTableLocator()
            ->get('Users')
            ->find()
            ->where(['UserId' => $userId])
            ->first();

        if (!$user) {
            return false;
        }

        if ($user->UserRole === 'admin') {
            return true;
        }

        if ($user->UserRole !== 'staff' || $permission === 'access.manage') {
            return false;
        }

        if ($permission === 'dashboard.view') {
            return true;
        }

        $this->ensureTable();

        $override = ConnectionManager::get('default')
            ->execute(
                'SELECT Allowed FROM StaffUserAccess WHERE UserId = ? AND PermissionKey = ?',
                [$userId, $permission],
            )
            ->fetch('assoc');

        if ($override) {
            return (int)$override['Allowed'] === 1;
        }

        $row = TableRegistry::getTableLocator()
            ->get('StaffAccesses')
            ->find()
            ->where(['PermissionKey' => $permission])
            ->first();

        if ($row) {
            return (int)$row->Allowed === 1;
        }

        return in_array($permission, $this->defaultAllow, true);
    }

    /**
     * Return whether each mapped permission is allowed for a user.
     *
     * @param int $userId User id.
     * @return array<string, bool>
     */
    public function allowedMap(int $userId): array
    {
        $map = [];

        foreach (array_keys($this->permissionMap()) as $key) {
            $map[$key] = $this->hasPermission($userId, $key);
        }

        return $map;
    }

    /**
     * Return global staff module rows, excluding the dashboard key.
     *
     * @return array<\App\Model\Entity\StaffAccess>
     */
    public function staffModules(): array
    {
        $this->ensureTable();

        $modules = [];

        foreach (
            TableRegistry::getTableLocator()
                ->get('StaffAccesses')
                ->find()
                ->orderBy(['ModuleLabel' => 'ASC'])
                ->all() as $row
        ) {
            if ($row->PermissionKey === 'dashboard.view') {
                continue;
            }

            $modules[] = $row;
        }

        return $modules;
    }

    /**
     * Return globally allowed staff permission keys.
     *
     * @return array<int, string>
     */
    public function staffAllowedKeys(): array
    {
        $keys = [];

        foreach ($this->staffModules() as $row) {
            if ((int)$row->Allowed === 1) {
                $keys[] = $row->PermissionKey;
            }
        }

        return $keys;
    }

    /**
     * Save the global staff module switches.
     *
     * @param array<int, string> $allowed Allowed permission keys.
     * @return void
     */
    public function saveStaffModules(array $allowed): void
    {
        $this->ensureTable();

        $table = TableRegistry::getTableLocator()->get('StaffAccesses');

        foreach ($table->find()->all() as $row) {
            if ($row->PermissionKey === 'dashboard.view') {
                $row->Allowed = 1;
                $table->save($row);
                continue;
            }

            $row->Allowed = in_array($row->PermissionKey, $allowed, true) ? 1 : 0;
            $table->save($row);
        }
    }

    /**
     * Return per-user permission overrides for one staff member.
     *
     * @param int $userId User id.
     * @return array<string, bool>
     */
    public function userOverrides(int $userId): array
    {
        $this->ensureTable();

        $rows = ConnectionManager::get('default')
            ->execute(
                'SELECT PermissionKey, Allowed FROM StaffUserAccess WHERE UserId = ?',
                [$userId],
            )
            ->fetchAll('assoc');

        $map = [];
        foreach ($rows as $row) {
            $map[(string)$row['PermissionKey']] = (int)$row['Allowed'] === 1;
        }

        return $map;
    }

    /**
     * Write per-user overrides for every assignable permission key.
     *
     * Keys present in $allowedKeys are stored as allowed. Missing keys are
     * stored as denied. access.manage and dashboard.view are never written.
     *
     * @param int $userId User id.
     * @param array<int, string> $allowedKeys Allowed permission keys.
     * @return void
     */
    public function saveUserOverrides(int $userId, array $allowedKeys): void
    {
        $this->ensureTable();

        $allowedKeys = array_map('strval', $allowedKeys);
        $connection = ConnectionManager::get('default');

        foreach (array_keys($this->permissionMap()) as $key) {
            if ($key === 'access.manage' || $key === 'dashboard.view') {
                continue;
            }

            $connection->execute(
                'INSERT INTO StaffUserAccess (UserId, PermissionKey, Allowed)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE Allowed = VALUES(Allowed)',
                [
                    $userId,
                    $key,
                    in_array($key, $allowedKeys, true) ? 1 : 0,
                ],
            );
        }
    }
}
