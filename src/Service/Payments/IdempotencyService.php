<?php
declare(strict_types=1);

namespace App\Service\Payments;

use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use PDOException;
use Throwable;

/**
 * Claims a unique (scope, key) row so duplicate Stripe deliveries run once.
 */
class IdempotencyService
{
    use LocatorAwareTrait;

    public const SCOPE_STRIPE_WEBHOOK = 'stripe_webhook';

    public const ACQUIRED = 'acquired';

    public const IN_FLIGHT = 'in_flight';

    public const COMPLETED = 'completed';

    /**
     * @param string $scope Scope, e.g. stripe_webhook.
     * @param string $key Provider event id.
     * @return bool True when this caller should process the event.
     */
    public function claim(string $scope, string $key): bool
    {
        return $this->claimStatus($scope, $key)['status'] === self::ACQUIRED;
    }

    /**
     * @param string $scope Scope, e.g. stripe_webhook.
     * @param string $key Provider event id.
     * @return array{status: string, owner: string}
     */
    public function claimStatus(string $scope, string $key): array
    {
        $owner = bin2hex(random_bytes(16));

        return $this->connection()->transactional(function () use ($scope, $key, $owner) {
            $table = $this->fetchTable('IdempotencyRecords');
            $existing = $table->find()
                ->where(['scope' => $scope, 'idempotency_key' => $key])
                ->first();

            if ($existing === null) {
                $row = $table->newEmptyEntity();
                $row->set('scope', $scope);
                $row->set('idempotency_key', $key);
                $row->set('lease_owner', $owner);
                $row->set('expires_at', DateTime::now('UTC')->addDays(7));
                $row->set('locked_until', DateTime::now('UTC')->addMinutes(2));
                try {
                    $table->saveOrFail($row);
                } catch (Throwable $exception) {
                    if (!$this->isDuplicateKey($exception)) {
                        throw $exception;
                    }

                    return ['status' => self::IN_FLIGHT, 'owner' => ''];
                }

                return ['status' => self::ACQUIRED, 'owner' => $owner];
            }

            if ($existing->get('completed_at') !== null) {
                return ['status' => self::COMPLETED, 'owner' => (string)$existing->get('lease_owner')];
            }

            $lockedUntil = $existing->get('locked_until');
            if ($lockedUntil instanceof DateTime && $lockedUntil->greaterThan(DateTime::now('UTC'))) {
                return ['status' => self::IN_FLIGHT, 'owner' => ''];
            }

            $statement = $this->connection()->execute(
                'UPDATE idempotency_records
                    SET locked_until = ?, lease_owner = ?
                  WHERE scope = ?
                    AND idempotency_key = ?
                    AND completed_at IS NULL
                    AND locked_until <= ?',
                [
                    DateTime::now('UTC')->addMinutes(2)->format('Y-m-d H:i:s'),
                    $owner,
                    $scope,
                    $key,
                    DateTime::now('UTC')->format('Y-m-d H:i:s'),
                ],
            );

            if ($statement->rowCount() === 1) {
                return ['status' => self::ACQUIRED, 'owner' => $owner];
            }

            return ['status' => self::IN_FLIGHT, 'owner' => ''];
        });
    }

    /**
     * @param string $scope Scope.
     * @param string $key Event id.
     * @param int $status HTTP status stored for replays.
     * @param array<string, mixed> $body Response body snapshot.
     * @param string $owner Lease owner from claimStatus().
     * @return void
     */
    public function complete(string $scope, string $key, int $status, array $body, string $owner = ''): void
    {
        $sql = 'UPDATE idempotency_records
                   SET response_status = ?, response_body = ?, completed_at = ?
                 WHERE scope = ?
                   AND idempotency_key = ?
                   AND completed_at IS NULL';
        $params = [
            $status,
            json_encode($body, JSON_THROW_ON_ERROR),
            DateTime::now('UTC')->format('Y-m-d H:i:s'),
            $scope,
            $key,
        ];
        if ($owner !== '') {
            $sql .= ' AND lease_owner = ?';
            $params[] = $owner;
        }
        $this->connection()->execute($sql, $params);
    }

    /**
     * Drop an unfinished lock so Stripe can retry a transient failure.
     *
     * @param string $scope Scope.
     * @param string $key Event id.
     * @param string $owner Lease owner from claimStatus().
     * @return void
     */
    public function release(string $scope, string $key, string $owner = ''): void
    {
        $sql = 'UPDATE idempotency_records
                SET locked_until = ?
              WHERE scope = ?
                AND idempotency_key = ?
                AND completed_at IS NULL';
        $params = [DateTime::now('UTC')->format('Y-m-d H:i:s'), $scope, $key];
        if ($owner !== '') {
            $sql .= ' AND lease_owner = ?';
            $params[] = $owner;
        }
        $this->connection()->execute($sql, $params);
    }

    /**
     * @param \Throwable $exception Persistence error.
     * @return bool
     */
    private function isDuplicateKey(Throwable $exception): bool
    {
        $previous = $exception->getPrevious();
        if ($previous instanceof PDOException) {
            return (int)($previous->errorInfo[1] ?? 0) === 1062
                || str_contains($previous->getMessage(), 'Duplicate')
                || str_contains($previous->getMessage(), 'UNIQUE');
        }

        return str_contains($exception->getMessage(), 'Duplicate')
            || str_contains($exception->getMessage(), 'UNIQUE')
            || str_contains($exception->getMessage(), '1062');
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('IdempotencyRecords')->getConnection();
    }
}
