<?php
declare(strict_types=1);

use Cake\Database\Exception\QueryException;
use Migrations\BaseMigration;

/**
 * Turn on Stripe checkout and installation bookings for existing databases.
 */
final class EnableCheckoutAndServicesFlags extends BaseMigration
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $adapter = strtolower((string)$this->getAdapter()->getAdapterType());
        if (!in_array($adapter, ['mysql'], true)) {
            throw new RuntimeException('This migration targets MySQL / MariaDB; adapter was: ' . $adapter);
        }

        $path = __DIR__ . '/sql/012_enable_checkout_and_services_flags.sql';
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Unable to read SQL migration: ' . $path);
        }

        foreach (explode('-- @@STATEMENT_END@@', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || preg_match('/^--[^\n]*(?:\n--[^\n]*)*$/s', $statement)) {
                continue;
            }
            try {
                $this->execute($statement);
            } catch (QueryException $exception) {
                throw $exception;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->execute(
            "UPDATE `feature_flags` SET `enabled` = 0 WHERE `flag_key` = 'commerce.online_payments'",
        );
    }
}
