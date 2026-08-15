<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\Security\RefundIntegrityPreflight;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Dirty historical refund rows must fail before unique-index DDL.
 */
class RefundIntegrityPreflightTest extends TestCase
{
    /**
     * Prospective effect_key grouping must run under ONLY_FULL_GROUP_BY.
     *
     * @return void
     */
    public function testProspectiveEffectKeyQueryAcceptsOnlyFullGroupBy(): void
    {
        $connection = $this->connection();
        $driver = $connection->getDriver();
        if (!str_contains($driver::class, 'Mysql')) {
            $this->markTestSkipped('Requires MySQL information_schema.');
        }
        $connection->execute("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY'");
        try {
            $rows = $connection->execute(
                'SELECT CONCAT(payment_id, "/", invoice_id, "/", prospective_effect_key) AS value,
                        COUNT(*) AS c
                   FROM (
                        SELECT payment_id,
                               invoice_id,
                               CASE
                                   WHEN allocation_type = \'refund\' THEN CONCAT(\'refund-\', id)
                                   ELSE \'capture\'
                               END AS prospective_effect_key
                          FROM payment_allocations
                   ) prospective
                  GROUP BY payment_id, invoice_id, prospective_effect_key
                 HAVING COUNT(*) > 1',
            )->fetchAll('assoc');
            $this->assertIsArray($rows);
        } finally {
            $connection->execute('SET SESSION sql_mode = DEFAULT');
        }
    }

    /**
     * Refreshing metadata after DDL must keep the new allocation columns.
     *
     * @return void
     */
    public function testRefreshCachedSchemaKeepsNewAllocationColumns(): void
    {
        $connection = $this->connection();
        $driver = $connection->getDriver();
        if (!str_contains($driver::class, 'Mysql')) {
            $this->markTestSkipped('Requires MySQL information_schema.');
        }
        RefundIntegrityPreflight::refreshCachedSchema($connection, 'payment_allocations');
        $schema = $connection->getSchemaCollection()->describe('payment_allocations');
        $this->assertTrue($schema->hasColumn('effect_key'));
        $this->assertTrue($schema->hasColumn('payment_refund_id'));
    }

    /**
     * Duplicate provider refund IDs abort before any index is created.
     *
     * @return void
     */
    public function testDirtyProviderRefundsFailBeforeUniqueIndexChanges(): void
    {
        $connection = $this->connection();
        $driver = $connection->getDriver();
        if (!str_contains($driver::class, 'Mysql')) {
            $this->markTestSkipped('Requires MySQL unique indexes.');
        }

        $token = 'preflight_' . bin2hex(random_bytes(4));
        $this->purgeLegacyRows($connection);
        $before = $this->indexNames($connection, 'payment_refunds');
        $this->dropIndexIfExists($connection, 'payment_refunds', 'uq_payment_refunds_provider_refund_id');
        $orderA = 0;
        $orderB = 0;
        $paymentA = 0;
        $paymentB = 0;
        try {
            $orderA = $this->insertOrder($connection, $token . '-A');
            $orderB = $this->insertOrder($connection, $token . '-B');
            $paymentA = $this->insertPayment($connection, $orderA, $token . '-pi-a');
            $paymentB = $this->insertPayment($connection, $orderB, $token . '-pi-b');
            $this->insertRefund($connection, $paymentA, $token, $token . '-refund-a');
            $this->insertRefund($connection, $paymentB, $token, $token . '-refund-b');
            $afterDrop = $this->indexNames($connection, 'payment_refunds');

            try {
                RefundIntegrityPreflight::assert($connection);
                $this->fail('Preflight must refuse duplicate provider_refund_id rows.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString($token, $exception->getMessage());
            }
            $this->assertSame($afterDrop, $this->indexNames($connection, 'payment_refunds'));

            $this->deletePayments($connection, $paymentA, $paymentB);
            $this->deleteOrders($connection, $orderA, $orderB);
            $paymentA = 0;
            $paymentB = 0;
            $orderA = 0;
            $orderB = 0;
            RefundIntegrityPreflight::assert($connection);
        } finally {
            $this->deletePayments($connection, $paymentA, $paymentB);
            $this->deleteOrders($connection, $orderA, $orderB);
            $this->restoreUniqueIndex(
                $connection,
                'payment_refunds',
                'uq_payment_refunds_provider_refund_id',
                ['provider_refund_id'],
                $before,
            );
        }
    }

    /**
     * Duplicate capture allocations abort before the effect_key unique index.
     *
     * @return void
     */
    public function testDirtyAllocationsFailBeforeUniqueIndexChanges(): void
    {
        $connection = $this->connection();
        $driver = $connection->getDriver();
        if (!str_contains($driver::class, 'Mysql')) {
            $this->markTestSkipped('Requires MySQL unique indexes.');
        }
        if (!$connection->getSchemaCollection()->describe('payment_allocations')->hasColumn('effect_key')) {
            $this->markTestSkipped('effect_key is not present.');
        }

        $token = 'preflight_' . bin2hex(random_bytes(4));
        $this->purgeLegacyRows($connection);
        $before = $this->indexNames($connection, 'payment_allocations');
        // InnoDB will not drop the unique index while it is the only cover
        // for payment_id / invoice_id foreign keys. Extra non-unique indexes
        // stay behind so those FKs remain valid after the unique is restored.
        $this->ensureIndex($connection, 'payment_allocations', 'idx_preflight_payment_id', ['payment_id']);
        $this->ensureIndex($connection, 'payment_allocations', 'idx_preflight_invoice_id', ['invoice_id']);
        $this->dropIndexIfExists($connection, 'payment_allocations', 'uq_payment_allocations_effect_key');
        $this->dropIndexIfExists($connection, 'payment_allocations', 'uq_payment_allocations_effect');
        $orderId = 0;
        $paymentId = 0;
        $invoiceId = 0;
        try {
            $orderId = $this->insertOrder($connection, $token . '-C');
            $paymentId = $this->insertPayment($connection, $orderId, $token . '-pi-c');
            $invoiceId = $this->insertInvoice($connection, $orderId, $token);
            $this->insertAllocation($connection, $paymentId, $invoiceId);
            $this->insertAllocation($connection, $paymentId, $invoiceId);
            $afterDrop = $this->indexNames($connection, 'payment_allocations');

            try {
                RefundIntegrityPreflight::assert($connection);
                $this->fail('Preflight must refuse duplicate allocation effect keys.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('payment_allocations.effect_key', $exception->getMessage());
            }
            $this->assertSame($afterDrop, $this->indexNames($connection, 'payment_allocations'));

            $this->deletePayments($connection, $paymentId);
            $this->deleteInvoices($connection, $invoiceId);
            $this->deleteOrders($connection, $orderId);
            $paymentId = 0;
            $invoiceId = 0;
            $orderId = 0;
            RefundIntegrityPreflight::assert($connection);
        } finally {
            $this->deletePayments($connection, $paymentId);
            $this->deleteInvoices($connection, $invoiceId);
            $this->deleteOrders($connection, $orderId);
            $this->restoreUniqueIndex(
                $connection,
                'payment_allocations',
                'uq_payment_allocations_effect_key',
                ['payment_id', 'invoice_id', 'effect_key'],
                $before,
            );
            $this->restoreUniqueIndex(
                $connection,
                'payment_allocations',
                'uq_payment_allocations_effect',
                ['payment_id', 'invoice_id', 'allocation_type'],
                $before,
            );
        }
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function connection(): Connection
    {
        $connection = ConnectionManager::get('test');
        if (!$connection instanceof Connection) {
            $this->fail('Tests require a SQL connection.');
        }

        return $connection;
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param string $table Table.
     * @return list<string>
     */
    private function indexNames(Connection $connection, string $table): array
    {
        $rows = $connection->execute('SHOW INDEX FROM `' . $table . '`')->fetchAll('assoc');
        $names = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $names[] = (string)($row['Key_name'] ?? '');
        }
        sort($names);

        return $names;
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param string $table Table.
     * @param string $name Index.
     * @param list<string> $columns Columns.
     * @return void
     */
    private function ensureIndex(
        Connection $connection,
        string $table,
        string $name,
        array $columns,
    ): void {
        if (in_array($name, $this->indexNames($connection, $table), true)) {
            return;
        }
        $connection->execute(
            'ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (`' . implode('`,`', $columns) . '`)',
        );
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param string $table Table.
     * @param string $name Index.
     * @return void
     */
    private function dropIndexIfExists(Connection $connection, string $table, string $name): void
    {
        if (!in_array($name, $this->indexNames($connection, $table), true)) {
            return;
        }
        $connection->execute('ALTER TABLE `' . $table . '` DROP INDEX `' . $name . '`');
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param string $table Table.
     * @param string $name Index.
     * @param list<string> $columns Columns.
     * @param list<string> $before Names present at the start of the test.
     * @return void
     */
    private function restoreUniqueIndex(
        Connection $connection,
        string $table,
        string $name,
        array $columns,
        array $before,
    ): void {
        if (!in_array($name, $before, true) || in_array($name, $this->indexNames($connection, $table), true)) {
            return;
        }
        $connection->execute(
            'ALTER TABLE `' . $table . '` ADD UNIQUE INDEX `' . $name . '` (`' . implode('`,`', $columns) . '`)',
        );
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @return void
     */
    private function purgeLegacyRows(Connection $connection): void
    {
        $connection->execute(
            "DELETE FROM payment_allocations
              WHERE payment_refund_id IN (
                SELECT id FROM (
                  SELECT id FROM payment_refunds
                   WHERE provider_refund_id IN ('re_dup_provider')
                      OR provider_refund_id LIKE 'preflight_%'
                ) ids
              )",
        );
        $connection->execute(
            "DELETE FROM payment_refunds
              WHERE provider_refund_id IN ('re_dup_provider')
                 OR provider_refund_id LIKE 'preflight_%'",
        );
        $connection->execute(
            "DELETE FROM payment_allocations
              WHERE payment_id IN (
                SELECT id FROM (
                  SELECT id FROM payments
                   WHERE provider_payment_id LIKE 'pi_pre_%'
                      OR provider_payment_id LIKE 'preflight_%'
                ) ids
              )",
        );
        $connection->execute(
            "DELETE FROM payment_refunds
              WHERE payment_id IN (
                SELECT id FROM (
                  SELECT id FROM payments
                   WHERE provider_payment_id LIKE 'pi_pre_%'
                      OR provider_payment_id LIKE 'preflight_%'
                ) ids
              )",
        );
        $connection->execute(
            "DELETE FROM payments
              WHERE provider_payment_id LIKE 'pi_pre_%'
                 OR provider_payment_id LIKE 'preflight_%'",
        );
        $connection->execute(
            "DELETE FROM invoices
              WHERE invoice_number LIKE 'INV-PRE-%'
                 OR invoice_number LIKE 'preflight_%'",
        );
        $connection->execute(
            "DELETE FROM sales_orders
              WHERE order_number LIKE 'SO-PRE-%'
                 OR order_number LIKE 'preflight_%'",
        );
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int ...$paymentIds Payment ids.
     * @return void
     */
    private function deletePayments(Connection $connection, int ...$paymentIds): void
    {
        $ids = array_values(array_filter($paymentIds, static fn(int $id): bool => $id > 0));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $connection->execute(
            'DELETE FROM payment_allocations WHERE payment_id IN (' . $placeholders . ')',
            $ids,
        );
        $connection->execute(
            'DELETE FROM payment_allocations
              WHERE payment_refund_id IN (
                SELECT id FROM (
                  SELECT id FROM payment_refunds WHERE payment_id IN (' . $placeholders . ')
                ) ids
              )',
            $ids,
        );
        $connection->execute(
            'DELETE FROM payment_refunds WHERE payment_id IN (' . $placeholders . ')',
            $ids,
        );
        $connection->execute(
            'DELETE FROM payments WHERE id IN (' . $placeholders . ')',
            $ids,
        );
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int ...$invoiceIds Invoice ids.
     * @return void
     */
    private function deleteInvoices(Connection $connection, int ...$invoiceIds): void
    {
        $ids = array_values(array_filter($invoiceIds, static fn(int $id): bool => $id > 0));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $connection->execute(
            'DELETE FROM payment_allocations WHERE invoice_id IN (' . $placeholders . ')',
            $ids,
        );
        $connection->execute(
            'DELETE FROM invoices WHERE id IN (' . $placeholders . ')',
            $ids,
        );
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int ...$orderIds Order ids.
     * @return void
     */
    private function deleteOrders(Connection $connection, int ...$orderIds): void
    {
        $ids = array_values(array_filter($orderIds, static fn(int $id): bool => $id > 0));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $connection->execute(
            'DELETE FROM sales_orders WHERE id IN (' . $placeholders . ')',
            $ids,
        );
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param string $orderNumber Unique order number.
     * @return int
     */
    private function insertOrder(Connection $connection, string $orderNumber): int
    {
        $connection->execute(
            "INSERT INTO sales_orders (order_number, status, payment_status, fulfilment_method, currency)
             VALUES (?, 'confirmed', 'paid', 'shipping', 'AUD')",
            [$orderNumber],
        );

        return (int)$connection->getDriver()->lastInsertId();
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int $orderId Order id.
     * @param string $intentId Stripe id.
     * @return int
     */
    private function insertPayment(Connection $connection, int $orderId, string $intentId): int
    {
        $connection->execute(
            "INSERT INTO payments (sales_order_id, provider, provider_payment_id, method, status, amount_cents, currency)
             VALUES (?, 'stripe', ?, 'card', 'captured', 1000, 'AUD')",
            [$orderId, $intentId],
        );

        return (int)$connection->getDriver()->lastInsertId();
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int $paymentId Payment id.
     * @param string $providerRefundId Stripe refund id.
     * @param string $key Idempotency key.
     * @return void
     */
    private function insertRefund(
        Connection $connection,
        int $paymentId,
        string $providerRefundId,
        string $key,
    ): void {
        $connection->execute(
            "INSERT INTO payment_refunds (payment_id, provider_refund_id, idempotency_key, status, amount_cents)
             VALUES (?, ?, ?, 'pending', 500)",
            [$paymentId, $providerRefundId, $key],
        );
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int $orderId Order id.
     * @param string $token Unique prefix.
     * @return int
     */
    private function insertInvoice(Connection $connection, int $orderId, string $token): int
    {
        $connection->execute(
            "INSERT INTO invoices (invoice_number, sales_order_id, status, currency, grand_total_cents)
             VALUES (?, ?, 'issued', 'AUD', 1000)",
            [$token . '-inv', $orderId],
        );

        return (int)$connection->getDriver()->lastInsertId();
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int $paymentId Payment id.
     * @param int $invoiceId Invoice id.
     * @return void
     */
    private function insertAllocation(
        Connection $connection,
        int $paymentId,
        int $invoiceId,
    ): void {
        $connection->execute(
            'INSERT INTO payment_allocations (payment_id, invoice_id, amount_cents, allocation_type, effect_key)
             VALUES (?, ?, 500, ?, ?)',
            [$paymentId, $invoiceId, 'capture', 'capture'],
        );
    }
}
