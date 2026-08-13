<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolePermissionsFixture
 */
class RolePermissionsFixture extends TestFixture
{
    /**
     * Standard staff keys from 009_core_seed.sql.
     *
     * @var array<int, string>
     */
    private const STANDARD_KEYS = [
        'customers.view',
        'messages.manage',
        'orders.create',
        'orders.manage',
        'orders.dispatch',
        'inventory.view',
        'invoices.issue',
        'payments.record',
        'refunds.process',
        'services.manage',
        'reports.view',
    ];

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $allKeys = [
            'access.manage', 'customers.view', 'customers.edit', 'customers.sensitive.view',
            'customers.export', 'messages.manage', 'catalogue.manage', 'pricing.manage',
            'orders.create', 'orders.manage', 'orders.dispatch', 'inventory.view',
            'inventory.adjust', 'purchasing.manage', 'quotations.manage', 'quotations.approve',
            'invoices.issue', 'invoices.void', 'payments.record', 'refunds.process',
            'services.manage', 'services.dispatch', 'reports.view', 'reports.financial',
            'ai.review_actions', 'audit.view',
        ];
        $idByKey = array_flip($allKeys);
        foreach ($idByKey as $key => $index) {
            $idByKey[$key] = $index + 1;
        }

        $this->records = [];
        foreach ([1, 2] as $roleId) {
            foreach ($idByKey as $permissionId) {
                $this->records[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created' => '2026-08-06 00:00:00',
                ];
            }
        }
        foreach (self::STANDARD_KEYS as $key) {
            $this->records[] = [
                'role_id' => 3,
                'permission_id' => $idByKey[$key],
                'created' => '2026-08-06 00:00:00',
            ];
        }
        parent::init();
    }
}
