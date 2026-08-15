<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Keep duplicate-capture reversals out of recognised sales refunds.
 */
final class ClassifyRefundKinds extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $adapter = strtolower((string)$this->getAdapter()->getAdapterType());
        if (!in_array($adapter, ['mysql'], true)) {
            throw new RuntimeException('This migration targets MySQL 8 only; adapter was: ' . $adapter);
        }

        if ($this->hasTable('payment_refunds') && !$this->table('payment_refunds')->hasColumn('refund_kind')) {
            $this->table('payment_refunds')
                ->addColumn('refund_kind', 'string', [
                    'limit' => 40,
                    'null' => false,
                    'default' => 'customer_refund',
                    'after' => 'status',
                ])
                ->update();
        }
        if ($this->hasTable('payment_refunds') && $this->table('payment_refunds')->hasColumn('refund_kind')) {
            $this->execute(
                "UPDATE payment_refunds
                    SET refund_kind = 'duplicate_capture_reversal'
                  WHERE reason = 'Unexpected Stripe capture'",
            );
        }

        $path = __DIR__ . '/sql/012_classify_refund_kinds.sql';
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Unable to read SQL migration: ' . $path);
        }

        foreach (explode('-- @@STATEMENT_END@@', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || preg_match('/^--[^\n]*(?:\n--[^\n]*)*$/s', $statement)) {
                continue;
            }
            $this->execute($statement);
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        throw new RuntimeException(
            'Irreversible additive migration: use a backup or the development-only rollback script.',
        );
    }
}
