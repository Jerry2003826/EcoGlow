<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Bind each refund allocation to a specific payment_refunds row.
 */
final class AddRefundAllocationKeys extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('payment_allocations')) {
            return;
        }
        $table = $this->table('payment_allocations');
        if (!$table->hasColumn('payment_refund_id')) {
            $table->addColumn('payment_refund_id', 'biginteger', [
                'null' => true,
                'signed' => false,
                'after' => 'invoice_id',
            ]);
            $table->update();
        }
        if (!$table->hasColumn('effect_key')) {
            $table->addColumn('effect_key', 'string', [
                'limit' => 64,
                'null' => false,
                'default' => 'capture',
                'after' => 'allocation_type',
            ]);
            $table->update();
            $this->execute(
                "UPDATE payment_allocations
                    SET effect_key = CASE
                        WHEN allocation_type = 'refund' THEN CONCAT('refund-', id)
                        ELSE 'capture'
                    END",
            );
        }
        if (!$this->hasIndexByName('payment_allocations', 'uq_payment_allocations_effect_key')) {
            $this->table('payment_allocations')
                ->addIndex(['payment_id', 'invoice_id', 'effect_key'], [
                    'unique' => true,
                    'name' => 'uq_payment_allocations_effect_key',
                ])
                ->update();
        }
        if ($this->hasIndexByName('payment_allocations', 'uq_payment_allocations_effect')) {
            $this->table('payment_allocations')->removeIndexByName('uq_payment_allocations_effect')->update();
        }
        if ($this->hasTable('payment_refunds')) {
            $this->execute(
                "UPDATE payment_refunds SET provider_refund_id = NULL WHERE provider_refund_id = ''",
            );
            if (!$this->hasIndexByName('payment_refunds', 'uq_payment_refunds_provider_refund_id')) {
                $this->table('payment_refunds')
                    ->addIndex(['provider_refund_id'], [
                        'unique' => true,
                        'name' => 'uq_payment_refunds_provider_refund_id',
                    ])
                    ->update();
            }
        }
    }

    /**
     * @param string $table Table name.
     * @param string $name Index name.
     * @return bool
     */
    private function hasIndexByName(string $table, string $name): bool
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            return false;
        }
        $statement = $this->getAdapter()->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$name}'",
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
