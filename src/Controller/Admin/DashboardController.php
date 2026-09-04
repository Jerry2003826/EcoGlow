<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;

class DashboardController extends AdminController
{
    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();
        $userId = (int)$identity->getIdentifier();

        if (!$permissionService->hasPermission($userId, 'dashboard.view')) {
            throw new ForbiddenException('Forbidden');
        }

        $connection = TableRegistry::getTableLocator()
            ->get('Users')
            ->getConnection();

        $widgets = [];

        if ($permissionService->hasPermission($userId, 'orders.view')) {
            $widgets['orders'] = $connection
                ->execute(
                    "SELECT business_date, orders_total, gross_sales_cents, tax_cents
                     FROM v_business_dashboard_daily
                     WHERE business_date = DATE(
                         COALESCE(
                             CONVERT_TZ(UTC_TIMESTAMP(), 'UTC', 'Australia/Melbourne'),
                             UTC_TIMESTAMP()
                         )
                     )
                     LIMIT 1"
                )
                ->fetch('assoc') ?: [];

            $widgets['pendingShipment'] = $this->fetchTable('SalesOrders')
                ->find('pendingShipment')
                ->count();
        }

        if (
            $permissionService->hasPermission($userId, 'inventory.view') ||
            $permissionService->hasPermission($userId, 'inventory.adjust')
        ) {
            $widgets['inventory'] = $connection
                ->execute(
                    'SELECT COUNT(*) AS total
                     FROM v_low_stock_items'
                )
                ->fetch('assoc') ?: [];

            $rows = $connection
                ->execute(
                    'SELECT
                        product_name,
                        variant_name,
                        sku,
                        quantity_available
                     FROM v_low_stock_items
                     LIMIT 5'
                )
                ->fetchAll('assoc');

            $widgets['lowStockItems'] = array_map(
                static function (array $row): array {
                    $row['name'] =
                        trim(
                            (string)$row['product_name'] .
                            ' ' .
                            (string)$row['variant_name']
                        );

                    return $row;
                },
                $rows
            );
        }

        if ($permissionService->hasPermission($userId, 'messages.manage')) {
            $widgets['messages'] = $connection
                ->execute(
                    'SELECT COUNT(*) AS total
                     FROM contact_messages
                     WHERE is_read = 0'
                )
                ->fetch('assoc') ?: [];
        }

        if ($permissionService->hasPermission($userId, 'reports.financial')) {
            $widgets['financial'] = $connection
                ->execute(
                    'SELECT business_date, gross_sales_cents, tax_cents
                     FROM v_business_dashboard_daily
                     ORDER BY business_date DESC
                     LIMIT 1'
                )
                ->fetch('assoc') ?: [];

            $widgets['transactions'] = $connection
                ->execute(
                    'SELECT
                        transaction_type,
                        reference_number,
                        amount_cents,
                        occurred_at
                     FROM v_recent_transactions
                     ORDER BY occurred_at DESC
                     LIMIT 10'
                )
                ->fetchAll('assoc');
        }

        $this->set([
            'widgets' => $widgets,
        ]);
    }
}
