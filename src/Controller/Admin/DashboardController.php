<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\SalesOrder;
use Cake\I18n\Date;

/**
 * Staff dashboard. Numbers come from the read-only views where they exist;
 * empty states render as 0 / "None waiting" rather than errors.
 */
class DashboardController extends AdminController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $connection = $this->fetchTable('SalesOrders')->getConnection();
        $today = Date::now('Australia/Melbourne');

        // v_business_dashboard_daily groups by Melbourne calendar day via
        // CONVERT_TZ(..., 'UTC', 'Australia/Melbourne'), falling back to the
        // stored UTC timestamp if timezone tables are missing. Compare against
        // that same expression so "today" cannot drift across a day boundary.
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
            ->where(['status IN' => SalesOrder::awaitingDispatchStatuses()])
            ->count();

        $lowStock = (int)$connection->execute('SELECT COUNT(*) AS c FROM v_low_stock_items')
            ->fetch('assoc')['c'];

        $unreadMessages = $this->fetchTable('ContactMessages')->find()
            ->where(['ContactMessages.is_read' => false])
            ->count();

        $newOrders = $this->fetchTable('SalesOrders')->find()
            ->contain(['Customers'])
            ->where(['SalesOrders.status IN' => [
                SalesOrder::STATUS_CONFIRMED,
                SalesOrder::STATUS_PROCESSING,
            ]])
            ->orderBy(['SalesOrders.placed_at' => 'DESC', 'SalesOrders.id' => 'DESC'])
            ->limit(5)
            ->all();

        $unreadInbox = $this->fetchTable('ContactMessages')->find()
            ->where(['ContactMessages.is_read' => false])
            ->orderBy(['ContactMessages.created' => 'DESC'])
            ->limit(5)
            ->all();

        $lowStockItems = $connection->execute(
            'SELECT sku, product_name, variant_name, quantity_available, reorder_point, location_name
               FROM v_low_stock_items
              ORDER BY quantity_available ASC, sku ASC
              LIMIT 5',
        )->fetchAll('assoc');

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
        ));
    }
}
