<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Second-round guards: lease owners, invoice uniqueness, MFA replay, hold index.
 */
final class AddSecondRoundSecurityGuards extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('users')) {
            $users = $this->table('users');
            if (!$users->hasColumn('mfa_last_timestep')) {
                $users->addColumn('mfa_last_timestep', 'biginteger', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('mfa_recovery_hashes')) {
                $users->addColumn('mfa_recovery_hashes', 'text', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            $users->update();
        }

        if ($this->hasTable('idempotency_records')) {
            $locks = $this->table('idempotency_records');
            if (!$locks->hasColumn('lease_owner')) {
                $locks->addColumn('lease_owner', 'string', [
                    'default' => null,
                    'limit' => 64,
                    'null' => true,
                ]);
            }
            $locks->update();
        }

        if ($this->hasTable('invoices')) {
            $invoices = $this->table('invoices');
            if (!$invoices->hasColumn('open_order_key')) {
                $invoices->addColumn('open_order_key', 'biginteger', [
                    'default' => null,
                    'null' => true,
                    'signed' => false,
                ]);
                $invoices->addIndex(['open_order_key'], [
                    'unique' => true,
                    'name' => 'uq_invoices_open_order_key',
                ]);
            }
            $invoices->update();
        }

        if ($this->hasTable('sales_orders') && !$this->hasIndex('sales_orders', 'idx_sales_orders_hold_cleanup')) {
            $this->table('sales_orders')
                ->addIndex(['status', 'payment_status', 'hold_expires_at'], [
                    'name' => 'idx_sales_orders_hold_cleanup',
                ])
                ->update();
        }

        if ($this->hasTable('payments')) {
            $this->ensureUniqueIndex(
                'payments',
                'uq_payments_provider_payment_id',
                ['provider', 'provider_payment_id'],
            );
        }

        if ($this->hasTable('sales_orders')) {
            $this->ensureUniqueIndex(
                'sales_orders',
                'uq_sales_orders_checkout_attempt_id',
                ['checkout_attempt_id'],
            );
        }

        if (!$this->hasTable('payment_allocations')) {
            $this->table('payment_allocations')
                ->addColumn('payment_id', 'biginteger', ['null' => false, 'signed' => false])
                ->addColumn('invoice_id', 'biginteger', ['null' => false, 'signed' => false])
                ->addColumn('allocation_type', 'string', ['limit' => 32, 'null' => false, 'default' => 'capture'])
                ->addColumn('amount_cents', 'biginteger', ['null' => false])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addIndex(['payment_id', 'invoice_id', 'allocation_type'], [
                    'unique' => true,
                    'name' => 'uq_payment_allocations_effect',
                ])
                ->create();
        } else {
            $allocations = $this->table('payment_allocations');
            if (!$allocations->hasColumn('allocation_type')) {
                $allocations->addColumn('allocation_type', 'string', [
                    'limit' => 32,
                    'null' => false,
                    'default' => 'capture',
                    'after' => 'invoice_id',
                ]);
                $allocations->update();
            }
            $this->ensureUniqueIndex(
                'payment_allocations',
                'uq_payment_allocations_effect',
                ['payment_id', 'invoice_id', 'allocation_type'],
            );
        }
    }

    /**
     * @param string $table Table name.
     * @param string $name Index name.
     * @param array<int, string> $columns Columns.
     * @return void
     */
    private function ensureUniqueIndex(string $table, string $name, array $columns): void
    {
        if ($this->hasIndex($table, $name)) {
            return;
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
     * @return bool
     */
    private function hasIndex(string $table, string $name): bool
    {
        $adapter = $this->getAdapter();
        if (method_exists($adapter, 'hasIndexByName')) {
            return (bool)$adapter->hasIndexByName($table, $name);
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            return false;
        }
        $statement = $adapter->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$name}' AND Non_unique = 0",
        );

        return is_object($statement)
            && method_exists($statement, 'fetch')
            && $statement->fetch() !== false;
    }

    /**
     * @return void
     */
    public function down(): void
    {
        throw new RuntimeException('Irreversible additive security migration.');
    }
}
