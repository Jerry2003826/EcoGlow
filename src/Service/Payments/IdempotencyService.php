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

    /**
     * Insert the key. Returns false when another worker already claimed it
     * and finished. Reclaims stale in-flight rows.
     *
     * @param string $scope Scope, e.g. stripe_webhook.
     * @param string $key Provider event id.
     * @return bool True when this caller should process the event.
     */
    public function claim(string $scope, string $key): bool
    {
        return (bool)$this->connection()->transactional(function () use ($scope, $key) {
            $table = $this->fetchTable('IdempotencyRecords');
            $existing = $table->find()
                ->where(['scope' => $scope, 'idempotency_key' => $key])
                ->first();

            if ($existing === null) {
                $row = $table->newEmptyEntity();
                $row->set('scope', $scope);
                $row->set('idempotency_key', $key);
                $row->set('expires_at', DateTime::now('UTC')->addDays(7));
                $row->set('locked_until', DateTime::now('UTC')->addMinutes(2));
                try {
                    $table->saveOrFail($row);
                } catch (Throwable $exception) {
                    if (!$this->isDuplicateKey($exception)) {
                        throw $exception;
                    }

                    return false;
                }

                return true;
            }

            if ($existing->get('completed_at') !== null) {
                return false;
            }

            $lockedUntil = $existing->get('locked_until');
            if ($lockedUntil instanceof DateTime && $lockedUntil->greaterThan(DateTime::now('UTC'))) {
                return false;
            }

            $existing->set('locked_until', DateTime::now('UTC')->addMinutes(2));
            $table->saveOrFail($existing);

            return true;
        });
    }

    /**
     * @param string $scope Scope.
     * @param string $key Event id.
     * @param int $status HTTP status stored for replays.
     * @param array<string, mixed> $body Response body snapshot.
     * @return void
     */
    public function complete(string $scope, string $key, int $status, array $body): void
    {
        $table = $this->fetchTable('IdempotencyRecords');
        $row = $table->find()
            ->where(['scope' => $scope, 'idempotency_key' => $key])
            ->first();
        if ($row === null) {
            return;
        }
        $row->set('response_status', $status);
        $row->set('response_body', $body);
        $row->set('completed_at', DateTime::now('UTC'));
        $table->saveOrFail($row);
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
                || str_contains($previous->getMessage(), 'Duplicate');
        }

        return str_contains($exception->getMessage(), 'Duplicate')
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
