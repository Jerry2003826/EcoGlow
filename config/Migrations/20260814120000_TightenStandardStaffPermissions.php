<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Restrict standard_staff to the six operational keys the PO specified.
 */
final class TightenStandardStaffPermissions extends BaseMigration
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

        $path = __DIR__ . '/sql/009_tighten_standard_staff.sql';
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
     * @inheritDoc
     */
    public function down(): void
    {
        throw new RuntimeException('Irreversible additive migration: restore role_permissions from a backup.');
    }
}
