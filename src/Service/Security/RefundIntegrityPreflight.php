<?php
declare(strict_types=1);

namespace App\Service\Security;

use Cake\Database\Connection;
use RuntimeException;

/**
 * Fail closed before unique refund indexes or FKs are created.
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
        if (
            in_array('payment_allocations', $tables, true)
            && self::hasColumn($connection, 'payment_allocations', 'effect_key')
        ) {
            $duplicates = array_merge(
                $duplicates,
                self::duplicateLabels(
                    $connection,
                    'payment_allocations.effect_key',
                    'SELECT CONCAT(payment_id, "/", invoice_id, "/", effect_key) AS value,
                            COUNT(*) AS c
                       FROM payment_allocations
                      GROUP BY payment_id, invoice_id, effect_key
                     HAVING COUNT(*) > 1',
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
        $schema = $connection->getSchemaCollection()->describe($table);

        return $schema->hasColumn($column);
    }
}
