<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Capture-effect uniqueness and unique-index verification.
 */
final class AddThirdRoundSecurityGuards extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('payment_effects')) {
            $this->table('payment_effects')
                ->addColumn('provider', 'string', ['limit' => 32, 'null' => false])
                ->addColumn('provider_payment_id', 'string', ['limit' => 191, 'null' => false])
                ->addColumn('effect_type', 'string', ['limit' => 32, 'null' => false])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addIndex(['provider', 'provider_payment_id', 'effect_type'], [
                    'unique' => true,
                    'name' => 'uq_payment_effects_provider_effect',
                ])
                ->create();
        }

        $this->ensureUniqueIndex(
            'payments',
            'uq_payments_provider_payment_id',
            ['provider', 'provider_payment_id'],
        );
        $this->ensureUniqueIndex(
            'sales_orders',
            'uq_sales_orders_checkout_attempt_id',
            ['checkout_attempt_id'],
        );
        $this->ensureUniqueIndex(
            'payment_allocations',
            'uq_payment_allocations_effect',
            ['payment_id', 'invoice_id', 'allocation_type'],
        );
        $this->ensureUniqueIndex(
            'invoices',
            'uq_invoices_open_order_key',
            ['open_order_key'],
        );
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
        $this->assertNoDuplicates($table, $columns);
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
        $safeTable = $this->safeIdent($table);
        $safeName = $this->safeIdent($name);
        $statement = $this->getAdapter()->query(
            "SHOW INDEX FROM `{$safeTable}` WHERE Key_name = '{$safeName}'",
        );
        if (!is_object($statement) || !method_exists($statement, 'fetchAll')) {
            return [];
        }

        $rows = $statement->fetchAll('assoc');

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param string $table Table name.
     * @param array<int, string> $columns Columns.
     * @return void
     */
    private function assertNoDuplicates(string $table, array $columns): void
    {
        $safeTable = $this->safeIdent($table);
        $idents = array_map(
            fn(string $column): string => '`' . $this->safeIdent($column) . '`',
            $columns,
        );
        $group = implode(', ', $idents);
        $notNull = implode(' AND ', array_map(
            static fn(string $ident): string => $ident . ' IS NOT NULL',
            $idents,
        ));
        $sql = "SELECT COUNT(*) AS c FROM (
                    SELECT 1 FROM `{$safeTable}`
                    WHERE {$notNull}
                    GROUP BY {$group}
                    HAVING COUNT(*) > 1
                ) duplicates";
        $statement = $this->getAdapter()->query($sql);
        $count = 0;
        if (is_object($statement) && method_exists($statement, 'fetchColumn')) {
            $count = (int)($statement->fetchColumn(0) ?: 0);
        }
        if ($count > 0) {
            throw new RuntimeException(
                "Cannot add unique index on {$table}: duplicate rows exist.",
            );
        }
    }

    /**
     * @param string $name Identifier.
     * @return string
     */
    private function safeIdent(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Invalid SQL identifier.');
        }

        return $name;
    }

    /**
     * @return void
     */
    public function down(): void
    {
        throw new RuntimeException('Irreversible additive security migration.');
    }
}
