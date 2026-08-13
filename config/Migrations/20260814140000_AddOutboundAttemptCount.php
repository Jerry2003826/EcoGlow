<?php
declare(strict_types=1);

use Cake\Database\Exception\QueryException;
use Migrations\BaseMigration;

/**
 * Persist retry counts on outbound_messages so the consumer can stop.
 */
final class AddOutboundAttemptCount extends BaseMigration
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

        $path = __DIR__ . '/sql/011_outbound_attempt_count.sql';
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
                if (str_contains(strtolower($exception->getMessage()), 'duplicate column')) {
                    continue;
                }
                throw $exception;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->execute('ALTER TABLE `outbound_messages` DROP COLUMN `attempt_count`');
    }
}
