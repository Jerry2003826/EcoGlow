<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PermissionsFixture — keys copied from database/mysql/009_core_seed.sql.
 */
class PermissionsFixture extends TestFixture
{
    /**
     * Seeded permission keys in insert order.
     *
     * @var array<int, array{key: string, module: string, name: string, risk: string}>
     */
    private const PERMISSIONS = [
        ['key' => 'access.manage', 'module' => 'access', 'name' => 'Manage staff access and roles', 'risk' => 'critical'],
        ['key' => 'customers.view', 'module' => 'customers', 'name' => 'View customer records', 'risk' => 'normal'],
        ['key' => 'customers.edit', 'module' => 'customers', 'name' => 'Edit customer records', 'risk' => 'high'],
        ['key' => 'customers.sensitive.view', 'module' => 'customers', 'name' => 'View age/date-of-birth fields', 'risk' => 'critical'],
        ['key' => 'customers.export', 'module' => 'customers', 'name' => 'Export customer data', 'risk' => 'critical'],
        ['key' => 'messages.manage', 'module' => 'messages', 'name' => 'Read, assign, reply to and close enquiries', 'risk' => 'normal'],
        ['key' => 'catalogue.manage', 'module' => 'catalogue', 'name' => 'Manage categories, products, variants and media', 'risk' => 'normal'],
        ['key' => 'pricing.manage', 'module' => 'pricing', 'name' => 'Manage prices, promotions and trade pricing', 'risk' => 'high'],
        ['key' => 'orders.create', 'module' => 'orders', 'name' => 'Create web/manual-channel orders', 'risk' => 'normal'],
        ['key' => 'orders.manage', 'module' => 'orders', 'name' => 'Update orders and delivery dates', 'risk' => 'high'],
        ['key' => 'orders.dispatch', 'module' => 'orders', 'name' => 'Pack and dispatch orders', 'risk' => 'normal'],
        ['key' => 'inventory.view', 'module' => 'inventory', 'name' => 'View inventory', 'risk' => 'low'],
        ['key' => 'inventory.adjust', 'module' => 'inventory', 'name' => 'Adjust stock', 'risk' => 'critical'],
        ['key' => 'purchasing.manage', 'module' => 'purchasing', 'name' => 'Manage suppliers and purchase orders', 'risk' => 'high'],
        ['key' => 'quotations.manage', 'module' => 'quotations', 'name' => 'Create and send quotations', 'risk' => 'normal'],
        ['key' => 'quotations.approve', 'module' => 'quotations', 'name' => 'Approve quotations', 'risk' => 'high'],
        ['key' => 'invoices.issue', 'module' => 'invoices', 'name' => 'Issue and send invoices', 'risk' => 'high'],
        ['key' => 'invoices.void', 'module' => 'invoices', 'name' => 'Void invoices and credit notes', 'risk' => 'critical'],
        ['key' => 'payments.record', 'module' => 'payments', 'name' => 'Record payments and deposits', 'risk' => 'high'],
        ['key' => 'refunds.process', 'module' => 'payments', 'name' => 'Process refunds', 'risk' => 'critical'],
        ['key' => 'services.manage', 'module' => 'services', 'name' => 'Manage installation/repair requests', 'risk' => 'normal'],
        ['key' => 'services.dispatch', 'module' => 'services', 'name' => 'Assign and schedule technicians', 'risk' => 'high'],
        ['key' => 'reports.view', 'module' => 'reports', 'name' => 'View operating reports', 'risk' => 'normal'],
        ['key' => 'reports.financial', 'module' => 'reports', 'name' => 'View financial and profit reports', 'risk' => 'high'],
        ['key' => 'ai.review_actions', 'module' => 'ai', 'name' => 'Approve/reject AI action requests', 'risk' => 'critical'],
        ['key' => 'audit.view', 'module' => 'audit', 'name' => 'View audit logs', 'risk' => 'high'],
        ['key' => 'orders.view', 'module' => 'orders', 'name' => 'View sales orders', 'risk' => 'normal'],
    ];

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        foreach (self::PERMISSIONS as $index => $permission) {
            $this->records[] = [
                'id' => $index + 1,
                'permission_key' => $permission['key'],
                'module' => $permission['module'],
                'name' => $permission['name'],
                'risk_level' => $permission['risk'],
                'created' => '2026-08-06 00:00:00',
            ];
        }
        parent::init();
    }
}
