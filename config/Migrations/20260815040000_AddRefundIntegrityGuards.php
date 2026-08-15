<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Unique refund alerts, allocation FK, and unique-index shape checks.
 */
final class AddRefundIntegrityGuards extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->assertRefundIntegrityPreflight();

        if ($this->hasTable('payment_reconciliation_alerts')) {
            $this->ensureUniqueIndex(
                'payment_reconciliation_alerts',
                'uq_payment_alerts_event_id',
                ['event_id'],
            );
            if ($this->indexExists('payment_reconciliation_alerts', 'idx_payment_alerts_event_id')) {
                $this->table('payment_reconciliation_alerts')
                    ->removeIndexByName('idx_payment_alerts_event_id')
                    ->update();
            }
        }

        if ($this->hasTable('payment_allocations')) {
            $this->ensureUniqueIndex(
                'payment_allocations',
                'uq_payment_allocations_effect_key',
                ['payment_id', 'invoice_id', 'effect_key'],
            );
            $allocations = $this->table('payment_allocations');
            if (
                $allocations->hasColumn('payment_refund_id')
                && $this->hasTable('payment_refunds')
                && !$allocations->hasForeignKey('payment_refund_id')
            ) {
                $allocations
                    ->addForeignKey('payment_refund_id', 'payment_refunds', 'id', [
                        'delete' => 'RESTRICT',
                        'update' => 'CASCADE',
                        'constraint' => 'fk_payment_allocations_payment_refund',
                    ])
                    ->update();
            }
        }

        if ($this->hasTable('payment_refunds')) {
            $this->ensureUniqueIndex(
                'payment_refunds',
                'uq_payment_refunds_provider_refund_id',
                ['provider_refund_id'],
            );
        }
    }

    /**
     * Refuse to add unique indexes or FKs when historical rows would collide.
     *
     * @return void
     */
    private function assertRefundIntegrityPreflight(): void
    {
        $duplicates = [];
        if ($this->hasTable('payment_reconciliation_alerts')) {
            $duplicates = array_merge(
                $duplicates,
                $this->duplicateValues(
                    'payment_reconciliation_alerts',
                    'event_id',
                    'SELECT event_id AS value, COUNT(*) AS c
                       FROM payment_reconciliation_alerts
                      GROUP BY event_id
                     HAVING COUNT(*) > 1',
                ),
            );
        }
        if ($this->hasTable('payment_refunds')) {
            $duplicates = array_merge(
                $duplicates,
                $this->duplicateValues(
                    'payment_refunds',
                    'provider_refund_id',
                    "SELECT provider_refund_id AS value, COUNT(*) AS c
                       FROM payment_refunds
                      WHERE provider_refund_id IS NOT NULL
                        AND provider_refund_id != ''
                      GROUP BY provider_refund_id
                     HAVING COUNT(*) > 1",
                ),
            );
        }
        $orphans = [];
        if (
            $this->hasTable('payment_allocations')
            && $this->table('payment_allocations')->hasColumn('payment_refund_id')
            && $this->hasTable('payment_refunds')
        ) {
            $orphans = $this->orphanAllocationIds();
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
     * @param string $table Table name for the error label.
     * @param string $column Column name for the error label.
     * @param string $sql Grouped duplicate query returning value, c.
     * @return list<string>
     */
    private function duplicateValues(string $table, string $column, string $sql): array
    {
        $rows = $this->fetchAssoc($sql);
        $labels = [];
        foreach ($rows as $row) {
            $labels[] = $table . '.' . $column . '=' . (string)($row['value'] ?? '')
                . ' x' . (string)($row['c'] ?? '0');
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    private function orphanAllocationIds(): array
    {
        $rows = $this->fetchAssoc(
            'SELECT pa.id AS value
               FROM payment_allocations pa
               LEFT JOIN payment_refunds pr ON pr.id = pa.payment_refund_id
              WHERE pa.payment_refund_id IS NOT NULL
                AND pr.id IS NULL',
        );
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (string)($row['value'] ?? '');
        }

        return $ids;
    }

    /**
     * @param string $sql Read-only diagnostic query.
     * @return list<array<string, mixed>>
     */
    private function fetchAssoc(string $sql): array
    {
        $statement = $this->getAdapter()->query($sql);
        if (!is_object($statement) || !method_exists($statement, 'fetchAll')) {
            return [];
        }
        $rows = $statement->fetchAll('assoc');
        if (!is_array($rows)) {
            return [];
        }
        if (count($rows) > 20) {
            $rows = array_slice($rows, 0, 20);
        }

        return $rows;
    }

    /**
     * @param string $table Table name.
     * @param string $name Index name.
     * @param array<int, string> $columns Columns.
     * @return void
     */
    private function ensureUniqueIndex(string $table, string $name, array $columns): void
    {
        if (!$this->hasTable($table)) {
            return;
        }
        if ($this->uniqueIndexMatches($table, $name, $columns)) {
            return;
        }
        if ($this->indexExists($table, $name)) {
            $this->table($table)->removeIndexByName($name)->update();
        }
        $this->table($table)
            ->addIndex($columns, [
                'unique' => true,
                'name' => $name,
            ])
            ->update();
    }

    /**
     * @param string $table Table name.
     * @param string $name Index name.
     * @param array<int, string> $columns Expected columns in order.
     * @return bool
     */
    private function uniqueIndexMatches(string $table, string $name, array $columns): bool
    {
        $rows = $this->indexRows($table, $name);
        if ($rows === []) {
            return false;
        }
        usort(
            $rows,
            static function (array $left, array $right): int {
                $leftSeq = (int)($left['Seq_in_index'] ?? $left['seq_in_index'] ?? 0);
                $rightSeq = (int)($right['Seq_in_index'] ?? $right['seq_in_index'] ?? 0);

                return $leftSeq <=> $rightSeq;
            },
        );
        $actual = [];
        $unique = false;
        foreach ($rows as $row) {
            if ((int)($row['Non_unique'] ?? $row['non_unique'] ?? 1) === 0) {
                $unique = true;
            }
            $actual[] = (string)($row['Column_name'] ?? $row['column_name'] ?? '');
        }

        return $unique && $actual === $columns;
    }

    /**
     * @param string $table Table name.
     * @param string $name Index name.
     * @return bool
     */
    private function indexExists(string $table, string $name): bool
    {
        return $this->indexRows($table, $name) !== [];
    }

    /**
     * @param string $table Table name.
     * @param string $name Index name.
     * @return list<array<string, mixed>>
     */
    private function indexRows(string $table, string $name): array
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            return [];
        }
        $statement = $this->getAdapter()->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$name}'",
        );
        if (!is_object($statement) || !method_exists($statement, 'fetchAll')) {
            return [];
        }
        $rows = $statement->fetchAll('assoc');

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return void
     */
    public function down(): void
    {
        throw new RuntimeException('Irreversible additive security migration.');
    }
}
