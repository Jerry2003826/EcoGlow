<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\SalesOrder;
use Cake\I18n\Date;

/**
 * Staff dashboard. Each widget is loaded only when the actor has that module.
 */
class DashboardController extends AdminController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $actorId = $this->actorId();
        $canOrders = $this->permissions->has($actorId, 'orders.view');
        $canInventory = $this->permissions->hasAnyOf($actorId, ['inventory.view', 'inventory.adjust']);
        $canMessages = $this->permissions->has($actorId, 'messages.manage');
        $canFinance = $this->permissions->has($actorId, 'reports.financial');

        $connection = $this->fetchTable('SalesOrders')->getConnection();
        $today = Date::now('Australia/Melbourne');
        $ordersToday = 0;
        $awaitingDispatch = 0;
        $lowStock = 0;
        $unreadMessages = 0;
        $newOrders = [];
        $unreadInbox = [];
        $lowStockItems = [];
        $recentTransactions = [];

        if ($canOrders) {
            $todayRow = $connection->execute(
                'SELECT orders_total FROM v_business_dashboard_daily
                  WHERE business_date = DATE(COALESCE(
                      CONVERT_TZ(UTC_TIMESTAMP(), \'UTC\', \'Australia/Melbourne\'),
                      UTC_TIMESTAMP()
                  ))
                  LIMIT 1',
            )->fetch('assoc');
            $ordersToday = $todayRow ? (int)$todayRow['orders_total'] : 0;
            $awaitingDispatch = $this->fetchTable('SalesOrders')->find()
                ->where(SalesOrder::awaitingDispatchConditions())
                ->count();
            $newOrders = $this->fetchTable('SalesOrders')->find()
                ->contain(['Customers'])
                ->where(SalesOrder::awaitingDispatchConditions())
                ->orderBy(['SalesOrders.placed_at' => 'DESC', 'SalesOrders.id' => 'DESC'])
                ->limit(5)
                ->all();
        }

        if ($canInventory) {
            $lowStock = (int)$connection->execute('SELECT COUNT(*) AS c FROM v_low_stock_items')
                ->fetch('assoc')['c'];
            $lowStockItems = $connection->execute(
                'SELECT sku, product_name, variant_name, quantity_available, reorder_point, location_name
                   FROM v_low_stock_items
                  ORDER BY quantity_available ASC, sku ASC
                  LIMIT 5',
            )->fetchAll('assoc');
        }

        if ($canMessages) {
            $unreadMessages = $this->fetchTable('ContactMessages')->find()
                ->where(['ContactMessages.is_read' => false])
                ->count();
            $unreadInbox = $this->fetchTable('ContactMessages')->find()
                ->where(['ContactMessages.is_read' => false])
                ->orderBy(['ContactMessages.created' => 'DESC'])
                ->limit(5)
                ->all();
        }

        if ($canFinance) {
            $recentTransactions = $connection->execute(
                "SELECT t.transaction_type, t.transaction_id, t.reference_number, t.amount_cents,
                        t.status, t.occurred_at, t.customer_id,
                        NULLIF(TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))), '')
                            AS customer_name
                   FROM v_recent_transactions t
                   LEFT JOIN customers c ON c.id = t.customer_id
                  ORDER BY t.occurred_at DESC
                  LIMIT 10",
            )->fetchAll('assoc');
        }

        $this->set(compact(
            'ordersToday',
            'awaitingDispatch',
            'lowStock',
            'unreadMessages',
            'newOrders',
            'unreadInbox',
            'lowStockItems',
            'recentTransactions',
            'today',
            'canOrders',
            'canInventory',
            'canMessages',
            'canFinance',
        ));
    }
}
