<?php
declare(strict_types=1);

use Cake\Database\Connection;
use Migrations\BaseSeed;

/**
 * Loads core RBAC, settings and feature flags, then grants master access.
 */
final class EcoGlowAuthorizationSeed extends BaseSeed
{
    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return [
            'UsersSeed',
        ];
    }

    /**
     * Run the authorization seed.
     *
     * @return void
     */
    public function run(): void
    {
        $sqlPath = dirname(__DIR__, 2) . '/database/mysql/009_core_seed.sql';
        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            throw new RuntimeException('Cannot read core seed SQL: ' . $sqlPath);
        }
        foreach (explode('-- @@STATEMENT_END@@', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement !== '' && !preg_match('/^--[^\n]*(?:\n--[^\n]*)*$/s', $statement)) {
                $this->execute($statement);
            }
        }

        $ownerEmail = trim((string)getenv('MASTER_USER_EMAIL'));
        if ($ownerEmail === '') {
            fwrite(STDERR, "MASTER_USER_EMAIL is not set; core roles/settings were seeded, but no user received master access.\n");
            return;
        }
        if (filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('MASTER_USER_EMAIL is not a valid email address.');
        }

        $connection = $this->mysqlPdo();
        $ownsTransaction = !$connection->inTransaction();
        if ($ownsTransaction) {
            $connection->beginTransaction();
        }
        try {
            $userQuery = $connection->prepare(
                'SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND deleted IS NULL LIMIT 2'
            );
            $userQuery->execute([$ownerEmail]);
            $userIds = $userQuery->fetchAll(PDO::FETCH_COLUMN);
            if (count($userIds) !== 1) {
                throw new RuntimeException(sprintf(
                    'MASTER_USER_EMAIL must identify exactly one active user; found %d for %s.',
                    count($userIds),
                    $ownerEmail
                ));
            }
            $userId = (int)$userIds[0];

            $roleQuery = $connection->query(
                "SELECT id FROM roles WHERE role_key = 'master' AND business_id IS NULL AND is_active = 1 LIMIT 1"
            );
            $roleId = $roleQuery !== false ? $roleQuery->fetchColumn() : false;
            if ($roleId === false) {
                throw new RuntimeException('The seeded global master role could not be found.');
            }

            $connection->prepare(
                "UPDATE `users` SET `role` = 'owner', `status` = 'active', `modified` = UTC_TIMESTAMP() WHERE `id` = ?"
            )->execute([$userId]);

            $connection->prepare(<<<'SQL'
INSERT INTO user_roles (user_id, role_id, business_id, starts_at, created)
SELECT ?, ?, NULL, UTC_TIMESTAMP(6), UTC_TIMESTAMP(6)
WHERE NOT EXISTS (
    SELECT 1 FROM user_roles
    WHERE user_id = ? AND role_id = ? AND business_id IS NULL AND revoked_at IS NULL
)
SQL)->execute([$userId, (int)$roleId, $userId, (int)$roleId]);

            if ($ownsTransaction) {
                $connection->commit();
            }
            fwrite(STDOUT, sprintf("Granted master access to user id %d (%s).\n", $userId, $ownerEmail));
        } catch (Throwable $e) {
            if ($ownsTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    /**
     * CakePHP 5 migrations expose Cake\Database\Connection, not PDO.
     *
     * @return \PDO
     */
    private function mysqlPdo(): PDO
    {
        $connection = $this->getAdapter()->getConnection();
        if (!$connection instanceof Connection) {
            throw new RuntimeException('EcoGlowAuthorizationSeed requires a CakePHP MySQL connection.');
        }
        $driver = $connection->getDriver();
        $method = new ReflectionMethod($driver, 'getPdo');
        $pdo = $method->invoke($driver);
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('EcoGlowAuthorizationSeed requires a MySQL PDO connection.');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
