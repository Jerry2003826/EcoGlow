<?php
declare(strict_types=1);

use Cake\Database\Exception\QueryException;
use Migrations\BaseMigration;

/**
 * Rebuild sp_next_document_number with explicit unicode collations.
 */
final class RebuildNextDocumentNumberRoutine extends BaseMigration
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

        $path = __DIR__ . '/sql/010_rebuild_next_document_number.sql';
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
                $isDrop = (bool)preg_match('/DROP\s+PROCEDURE\s+IF\s+EXISTS/i', $statement);
                $isCreate = (bool)preg_match('/CREATE\s+PROCEDURE/i', $statement);
                $denied = str_contains($exception->getMessage(), '1370');
                $exists = str_contains($exception->getMessage(), '1304')
                    || str_contains(strtolower($exception->getMessage()), 'already exists');
                if (($isDrop && $denied) || ($isCreate && ($denied || $exists))) {
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
        throw new RuntimeException('Irreversible additive migration: restore the routine from a backup.');
    }
}
