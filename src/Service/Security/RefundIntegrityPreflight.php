<?php
declare(strict_types=1);

namespace App\Service\Security;

use Cake\Database\Connection;
use RuntimeException;

/**
 * Fail closed before unique refund indexes or FKs are created.
 *
 * Column presence is read from INFORMATION_SCHEMA so this never writes a
 * stale Cake metadata cache entry for payment_allocations.
 */
final class RefundIntegrityPreflight
{
    /**
     * @param \Cake\Database\Connection $connection Application or test connection.
     * @return void
     */
    public static function assert(Connection $connection): void
    {
        $duplicates = [];
        $orphans = [];
        $tables = $connection->getSchemaCollection()->listTables();
        if (in_array('payment_reconciliation_alerts', $tables, true)) {
            $duplicates = array_merge(
                $duplicates,
                self::duplicateLabels(
                    $connection,
                    'payment_reconciliation_alerts.event_id',
                    'SELECT event_id AS value, COUNT(*) AS c
                       FROM payment_reconciliation_alerts
                      GROUP BY event_id
                     HAVING COUNT(*) > 1',
                ),
            );
        }
        if (in_array('payment_refunds', $tables, true)) {
            $duplicates = array_merge(
                $duplicates,
                self::duplicateLabels(
                    $connection,
                    'payment_refunds.provider_refund_id',
                    "SELECT provider_refund_id AS value, COUNT(*) AS c
                       FROM payment_refunds
                      WHERE provider_refund_id IS NOT NULL
                        AND provider_refund_id != ''
                      GROUP BY provider_refund_id
                     HAVING COUNT(*) > 1",
                ),
            );
        }
        if (in_array('payment_allocations', $tables, true)) {
            $duplicates = array_merge(
                $duplicates,
                self::duplicateLabels(
                    $connection,
                    'payment_allocations.effect_key',
                    self::allocationDuplicateSql($connection),
                ),
            );
        }
        if (
            in_array('payment_allocations', $tables, true)
            && in_array('payment_refunds', $tables, true)
            && self::hasColumn($connection, 'payment_allocations', 'payment_refund_id')
        ) {
            $orphans = self::orphanLabels($connection);
        }
        if ($duplicates === [] && $orphans === []) {
            return;
        }
        $parts = [];
        if ($duplicates !== []) {
            $parts[] = 'duplicate keys: ' . implode(', ', $duplicates);
        }
        if ($orphans !== []) {
            $parts[] = 'orphan payment_allocations.payment_refund_id: ' . implode(', ', $orphans);
        }

        throw new RuntimeException(
            'Refund integrity preflight failed; merge or delete the conflicting rows before migrating. '
            . implode('; ', $parts),
        );
    }

    /**
     * Rewrite Cake metadata cache from the live table after DDL.
     *
     * @param \Cake\Database\Connection $connection Connection.
     * @param string ...$tables Tables that just changed.
     * @return void
     */
    public static function refreshCachedSchema(Connection $connection, string ...$tables): void
    {
        $collection = $connection->getSchemaCollection();
        $existing = $collection->listTables();
        foreach ($tables as $table) {
            if (!in_array($table, $existing, true)) {
                continue;
            }
            $collection->describe($table, ['forceRefresh' => true]);
        }
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @return string
     */
    private static function allocationDuplicateSql(Connection $connection): string
    {
        if (self::hasColumn($connection, 'payment_allocations', 'effect_key')) {
            return 'SELECT CONCAT(payment_id, "/", invoice_id, "/", effect_key) AS value,
                            COUNT(*) AS c
                       FROM payment_allocations
                      GROUP BY payment_id, invoice_id, effect_key
                     HAVING COUNT(*) > 1';
        }
        $effect = self::hasColumn($connection, 'payment_allocations', 'allocation_type')
            ? "CASE WHEN allocation_type = 'refund' THEN CONCAT('refund-', id) ELSE 'capture' END"
            : "'capture'";

        return 'SELECT CONCAT(payment_id, "/", invoice_id, "/", ' . $effect . ') AS value,
                        COUNT(*) AS c
                   FROM payment_allocations
                  GROUP BY payment_id, invoice_id, ' . $effect . '
                 HAVING COUNT(*) > 1';
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param string $label Column label.
     * @param string $sql Grouped duplicate query.
     * @return list<string>
     */
    private static function duplicateLabels(Connection $connection, string $label, string $sql): array
    {
        $rows = $connection->execute($sql)->fetchAll('assoc');
        $labels = [];
        foreach (array_slice($rows, 0, 20) as $row) {
            $labels[] = $label . '=' . (string)($row['value'] ?? '')
                . ' x' . (string)($row['c'] ?? '0');
        }

        return $labels;
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @return list<string>
     */
    private static function orphanLabels(Connection $connection): array
    {
        $rows = $connection->execute(
            'SELECT pa.id AS value
               FROM payment_allocations pa
               LEFT JOIN payment_refunds pr ON pr.id = pa.payment_refund_id
              WHERE pa.payment_refund_id IS NOT NULL
                AND pr.id IS NULL',
        )->fetchAll('assoc');
        $ids = [];
        foreach (array_slice($rows, 0, 20) as $row) {
            $ids[] = (string)($row['value'] ?? '');
        }

        return $ids;
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param string $table Table name.
     * @param string $column Column name.
     * @return bool
     */
    private static function hasColumn(Connection $connection, string $table, string $column): bool
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }
        $row = $connection->execute(
            'SELECT COUNT(*) AS c
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?',
            [$table, $column],
        )->fetch('assoc');

        return is_array($row) && (int)$row['c'] > 0;
    }
}
