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
        'products.manage',
        'refunds.process',
    ];

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
            'products.manage' => 'Manage products',
            'refunds.process' => 'Refunds',
            'suppliers.view' => 'Suppliers',
            'reports.view' => 'Reports',
            'access.manage' => 'Access',
        ];
    }

    public function ensureTable(): void
    {
        $connection = ConnectionManager::get('default');

        $connection->execute(
            'CREATE TABLE IF NOT EXISTS StaffAccess (
                AccessId INT AUTO_INCREMENT PRIMARY KEY,
                PermissionKey VARCHAR(100) NOT NULL UNIQUE,
                ModuleLabel VARCHAR(150) NOT NULL,
                Allowed TINYINT(1) NOT NULL DEFAULT 0
            )'
        );

        $count = (int)($connection
            ->execute('SELECT COUNT(*) AS c FROM StaffAccess')
            ->fetch('assoc')['c'] ?? 0);

        if ($count === 0) {
            foreach ($this->permissionMap() as $key => $label) {
                if ($key === 'access.manage') {
                    continue;
                }

                $connection->execute(
                    'INSERT INTO StaffAccess (PermissionKey, ModuleLabel, Allowed) VALUES (?, ?, ?)',
                    [
                        $key,
                        $label,
                        in_array($key, $this->defaultAllow, true) ? 1 : 0,
                    ]
                );
            }
        }
    }

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

    public function allowedMap(int $userId): array
    {
        $map = [];

        foreach (array_keys($this->permissionMap()) as $key) {
            $map[$key] = $this->hasPermission($userId, $key);
        }

        return $map;
    }

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
}
