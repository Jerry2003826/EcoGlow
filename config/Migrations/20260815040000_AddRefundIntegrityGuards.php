<?php
declare(strict_types=1);

use App\Service\Security\RefundIntegrityPreflight;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
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
        RefundIntegrityPreflight::assert($this->cakeConnection());

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
        RefundIntegrityPreflight::refreshCachedSchema(
            $this->cakeConnection(),
            'payment_allocations',
            'payment_refunds',
            'payment_reconciliation_alerts',
        );
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function cakeConnection(): Connection
    {
        $connection = ConnectionManager::get('default');
        if (!$connection instanceof Connection) {
            throw new \RuntimeException('Refund preflight requires a SQL connection.');
        }

        return $connection;
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
        throw new \RuntimeException('Irreversible additive security migration.');
    }
}
