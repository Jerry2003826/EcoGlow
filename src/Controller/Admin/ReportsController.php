<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\I18n\Date;
use Cake\I18n\DateTime;

/**
 * Operating reports from the read-only views. No charts.
 */
class ReportsController extends AdminController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $preset = (string)$this->request->getQuery('preset', 'month');
        $from = (string)$this->request->getQuery('from', '');
        $to = (string)$this->request->getQuery('to', '');
        $sort = (string)$this->request->getQuery('sort', 'occurred_at');
        $direction = strtolower((string)$this->request->getQuery('direction', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        [$fromDate, $toDate, $preset] = $this->range($preset, $from, $to);
        $fromSql = $fromDate->format('Y-m-d');
        $toSql = $toDate->format('Y-m-d');

        $connection = $this->fetchTable('SalesOrders')->getConnection();

        $summary = $connection->execute(
            'SELECT
                COALESCE(SUM(orders_total), 0) AS orders_total,
                COALESCE(SUM(gross_sales_cents), 0) AS gross_sales_cents,
                COALESCE(SUM(tax_cents), 0) AS tax_cents
               FROM v_business_dashboard_daily
              WHERE business_date BETWEEN ? AND ?',
            [$fromSql, $toSql],
        )->fetch('assoc') ?: [];

        $ordersTotal = (int)($summary['orders_total'] ?? 0);
        $grossSales = (int)($summary['gross_sales_cents'] ?? 0);
        $taxCents = (int)($summary['tax_cents'] ?? 0);
        $average = $ordersTotal > 0 ? intdiv($grossSales, $ordersTotal) : 0;

        $channels = $connection->execute(
            "SELECT so.source_channel,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(
                        CASE
                            WHEN so.payment_status = 'refunded' THEN 0
                            ELSE GREATEST(0, so.grand_total_cents - COALESCE(rf.refunded_cents, 0))
                        END
                    ), 0) AS sales_cents
               FROM sales_orders so
               LEFT JOIN (
                    SELECT p.sales_order_id,
                           SUM(CASE WHEN pr.status IN ('succeeded', 'completed')
                                    THEN pr.amount_cents ELSE 0 END) AS refunded_cents
                      FROM payments p
                      INNER JOIN payment_refunds pr ON pr.payment_id = p.id
                     GROUP BY p.sales_order_id
               ) rf ON rf.sales_order_id = so.id
              WHERE so.placed_at IS NOT NULL
                AND DATE(COALESCE(CONVERT_TZ(so.placed_at, 'UTC', 'Australia/Melbourne'), so.placed_at))
                    BETWEEN ? AND ?
                AND so.status <> 'cancelled'
                AND so.payment_status <> 'refunded'
              GROUP BY so.source_channel
              ORDER BY sales_cents DESC",
            [$fromSql, $toSql],
        )->fetchAll('assoc');

        $categories = $connection->execute(
            "SELECT COALESCE(c.name, 'Uncategorised') AS category_name,
                    COUNT(soi.id) AS line_count,
                    COALESCE(SUM(
                        CASE
                            WHEN so.grand_total_cents <= 0 THEN 0
                            ELSE CAST(
                                soi.line_total_cents
                                * GREATEST(0, so.grand_total_cents - COALESCE(rf.refunded_cents, 0))
                                / so.grand_total_cents AS SIGNED
                            )
                        END
                    ), 0) AS sales_cents
               FROM sales_order_items soi
               INNER JOIN sales_orders so ON so.id = soi.sales_order_id
               LEFT JOIN products p ON p.id = soi.product_id
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN (
                    SELECT p.sales_order_id,
                           SUM(CASE WHEN pr.status IN ('succeeded', 'completed')
                                    THEN pr.amount_cents ELSE 0 END) AS refunded_cents
                      FROM payments p
                      INNER JOIN payment_refunds pr ON pr.payment_id = p.id
                     GROUP BY p.sales_order_id
               ) rf ON rf.sales_order_id = so.id
              WHERE so.placed_at IS NOT NULL
                AND DATE(COALESCE(CONVERT_TZ(so.placed_at, 'UTC', 'Australia/Melbourne'), so.placed_at))
                    BETWEEN ? AND ?
                AND so.status <> 'cancelled'
                AND so.payment_status <> 'refunded'
              GROUP BY COALESCE(c.name, 'Uncategorised')
              ORDER BY sales_cents DESC",
            [$fromSql, $toSql],
        )->fetchAll('assoc');

        $sortMap = [
            'reference' => 't.reference_number',
            'amount' => 't.amount_cents',
            'status' => 't.status',
            'occurred_at' => 't.occurred_at',
        ];
        $sortSql = $sortMap[$sort] ?? 't.occurred_at';

        $transactions = $connection->execute(
            "SELECT t.transaction_type, t.transaction_id, t.reference_number, t.amount_cents,
                    t.status, t.occurred_at
               FROM v_recent_transactions t
              WHERE DATE(COALESCE(CONVERT_TZ(t.occurred_at, 'UTC', 'Australia/Melbourne'), t.occurred_at))
                    BETWEEN ? AND ?
              ORDER BY {$sortSql} {$direction}, t.transaction_id DESC
              LIMIT 200",
            [$fromSql, $toSql],
        )->fetchAll('assoc');

        $this->set([
            'preset' => $preset,
            'from' => $fromSql,
            'to' => $toSql,
            'sort' => $sort,
            'direction' => $direction,
            'ordersTotal' => $ordersTotal,
            'grossSales' => $grossSales,
            'taxCents' => $taxCents,
            'average' => $average,
            'canFinancial' => $this->permissions->has($this->actorId(), 'reports.financial'),
            'channels' => $channels ?: [],
            'categories' => $categories ?: [],
            'transactions' => $transactions ?: [],
        ]);
    }

    /**
     * Profit, COGS and named customers. Requires reports.financial.
     *
     * @return void
     */
    public function financial(): void
    {
        $preset = (string)$this->request->getQuery('preset', 'month');
        $from = (string)$this->request->getQuery('from', '');
        $to = (string)$this->request->getQuery('to', '');
        [$fromDate, $toDate, $preset] = $this->range($preset, $from, $to);
        $fromSql = $fromDate->format('Y-m-d');
        $toSql = $toDate->format('Y-m-d');
        $connection = $this->fetchTable('SalesOrders')->getConnection();

        $profit = $connection->execute(
            "SELECT COALESCE(SUM(estimated_gross_profit_cents), 0) AS estimated_gross_profit_cents,
                    COALESCE(SUM(cogs_cents), 0) AS cogs_cents
               FROM v_order_profitability
              WHERE placed_at IS NOT NULL
                AND DATE(COALESCE(CONVERT_TZ(placed_at, 'UTC', 'Australia/Melbourne'), placed_at))
                    BETWEEN ? AND ?
                AND status <> 'cancelled'
                AND payment_status <> 'refunded'",
            [$fromSql, $toSql],
        )->fetch('assoc') ?: [];

        $transactions = $connection->execute(
            "SELECT t.transaction_type, t.transaction_id, t.reference_number, t.amount_cents,
                    t.status, t.occurred_at, t.customer_id,
                    NULLIF(TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))), '')
                        AS customer_name
               FROM v_recent_transactions t
               LEFT JOIN customers c ON c.id = t.customer_id
              WHERE DATE(COALESCE(CONVERT_TZ(t.occurred_at, 'UTC', 'Australia/Melbourne'), t.occurred_at))
                    BETWEEN ? AND ?
              ORDER BY t.occurred_at DESC, t.transaction_id DESC
              LIMIT 200",
            [$fromSql, $toSql],
        )->fetchAll('assoc');

        $this->set([
            'preset' => $preset,
            'from' => $fromSql,
            'to' => $toSql,
            'estimatedGrossProfit' => (int)($profit['estimated_gross_profit_cents'] ?? 0),
            'cogsCents' => (int)($profit['cogs_cents'] ?? 0),
            'transactions' => $transactions ?: [],
        ]);
    }

    /**
     * @param string $preset today|week|month|custom.
     * @param string $from Posted from date.
     * @param string $to Posted to date.
     * @return array{0: \Cake\I18n\Date, 1: \Cake\I18n\Date, 2: string}
     */
    private function range(string $preset, string $from, string $to): array
    {
        $today = Date::now('Australia/Melbourne');
        if ($preset === 'custom') {
            if (
                preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1
                && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1
            ) {
                return [Date::parse($from), Date::parse($to), 'custom'];
            }

            return [$today, $today, 'custom'];
        }
        if ($preset === 'today') {
            return [$today, $today, 'today'];
        }
        if ($preset === 'week') {
            $start = $today->startOfWeek();

            return [$start, $today, 'week'];
        }

        $start = DateTime::now('Australia/Melbourne')->startOfMonth();

        return [Date::parse($start->format('Y-m-d')), $today, 'month'];
    }
}
