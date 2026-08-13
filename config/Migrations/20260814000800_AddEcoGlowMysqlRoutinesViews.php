<?php
declare(strict_types=1);

use Cake\Database\Exception\QueryException;
use Migrations\BaseMigration;

final class AddEcoGlowMysqlRoutinesViews extends BaseMigration
{
    public function up(): void
    {
        $adapter = strtolower((string)$this->getAdapter()->getAdapterType());
        if (!in_array($adapter, ['mysql'], true)) {
            throw new RuntimeException('This migration targets MySQL 8 only; adapter was: ' . $adapter);
        }

        $path = __DIR__ . '/sql/008_routines_views.sql';
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
                // MariaDB requires ALTER ROUTINE for DROP PROCEDURE IF EXISTS even
                // when the routine does not exist. Restricted test accounts on
                // test_% databases often have CREATE ROUTINE but not ALTER ROUTINE.
                if (
                    !preg_match('/DROP\s+PROCEDURE\s+IF\s+EXISTS/i', $statement)
                    || !str_contains($exception->getMessage(), '1370')
                ) {
                    throw $exception;
                }
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible additive migration: use a backup or the development-only rollback script.');
    }
}
