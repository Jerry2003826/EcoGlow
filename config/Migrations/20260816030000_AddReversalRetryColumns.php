<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Schedule a new automatic-reversal attempt after Stripe reports a hard failure.
 */
final class AddReversalRetryColumns extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('payment_refunds')) {
            return;
        }
        $table = $this->table('payment_refunds');
        if (!$table->hasColumn('attempt_count')) {
            $table->addColumn('attempt_count', 'integer', [
                'null' => false,
                'default' => 1,
                'after' => 'refund_kind',
            ]);
        }
        if (!$table->hasColumn('retry_scheduled_at')) {
            $table->addColumn('retry_scheduled_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'failure_reason',
            ]);
        }
        $table->update();
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
