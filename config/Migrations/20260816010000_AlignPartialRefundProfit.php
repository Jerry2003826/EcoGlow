<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Keep estimated profit on the same remaining GST as the dashboard.
 */
final class AlignPartialRefundProfit extends BaseMigration
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

        $path = __DIR__ . '/sql/011_align_partial_refund_profit.sql';
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
