<?php
declare(strict_types=1);

namespace App\Service\Inventory;

use App\Model\Entity\InventoryLocation;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around the inventory stored procedures.
 *
 * Application code never UPDATEs inventory_balances. The in-transaction
 * procedure must run inside CakePHP's Connection::transactional(); the
 * standalone wrapper that starts its own transaction is never called here.
 */
class InventoryLedger
{
    use LocatorAwareTrait;

    /**
     * Apply an on-hand and/or reserved delta. Caller must already own a
     * transaction.
     *
     * @param int $variantId Product variant.
     * @param int $locationId Inventory location.
     * @param string $movementType Ledger movement_type value.
     * @param int $onHandDelta Change to quantity_on_hand.
     * @param int $reservedDelta Change to quantity_reserved.
     * @param string|null $referenceType Optional reference type.
     * @param int|null $referenceId Optional reference id.
     * @param string|null $note Human-readable reason.
     * @param int|null $actorUserId Acting staff user.
     * @return void
     */
    public function applyInTransaction(
        int $variantId,
        int $locationId,
        string $movementType,
        int $onHandDelta,
        int $reservedDelta,
        ?string $referenceType,
        ?int $referenceId,
        ?string $note,
        ?int $actorUserId,
    ): void {
        $connection = $this->connection();
        if (!$connection->inTransaction()) {
            throw new RuntimeException(
                'sp_apply_inventory_change_in_transaction must run inside Connection::transactional().',
            );
        }

        $this->callProcedure(
            $connection,
            'CALL sp_apply_inventory_change_in_transaction(?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $variantId,
                $locationId,
                $movementType,
                $onHandDelta,
                $reservedDelta,
                $referenceType,
                $referenceId,
                $note,
                $actorUserId,
            ],
        );
    }

    /**
     * Run a mutation inside an application-owned transaction.
     *
     * @param callable $callback Work that calls applyInTransaction().
     * @return mixed
     */
    public function transactional(callable $callback): mixed
    {
        return $this->connection()->transactional($callback);
    }

    /**
     * Next document number from document_sequences.
     *
     * Prefers sp_next_document_number (rebuilt with utf8mb4_unicode_ci VARCHAR
     * parameters so CALL no longer hits "illegal mix of collations" against
     * the table). The PHP path is kept as a fallback when:
     * - the local test account lacks ALTER/CREATE ROUTINE (errno 1370) so the
     *   rebuild migration could not replace the old routine, or
     * - CALL still raises 1267 (illegal mix of collations) or 1305 (unknown
     *   procedure). Reproduce: CREATE PROCEDURE without an explicit COLLATE
     *   on a MariaDB whose server collation is utf8mb4_general_ci, then CALL
     *   it against document_sequences.document_type (utf8mb4_unicode_ci).
     *
     * @param string $documentType Sequence key, e.g. sales_order.
     * @param string $prefix Prefix stored on the sequence row.
     * @return string
     */
    public function nextDocumentNumber(string $documentType, string $prefix): string
    {
        $fromProcedure = $this->nextDocumentNumberFromProcedure($documentType, $prefix);
        if ($fromProcedure !== null) {
            return $fromProcedure;
        }

        return $this->nextDocumentNumberInPhp($documentType, $prefix);
    }

    /**
     * @param string $documentType Sequence key.
     * @param string $prefix Prefix stored on the sequence row.
     * @return string|null
     */
    private function nextDocumentNumberFromProcedure(string $documentType, string $prefix): ?string
    {
        $connection = $this->connection();
        try {
            $connection->execute('SET @eg_next_document_number = NULL');
            $this->callProcedure(
                $connection,
                'CALL sp_next_document_number(?, ?, @eg_next_document_number)',
                [$documentType, $prefix],
            );
            $row = $connection->execute(
                'SELECT @eg_next_document_number AS document_number',
            )->fetch('assoc');
            $number = is_array($row) ? trim((string)$row['document_number']) : '';

            return $number !== '' ? $number : null;
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $collationMix = str_contains($message, '1267')
                || str_contains(strtolower($message), 'illegal mix of collations');
            $missingRoutine = str_contains($message, '1305')
                || str_contains(strtolower($message), 'does not exist');
            if ($collationMix || $missingRoutine) {
                return null;
            }
            throw $exception;
        }
    }

    /**
     * Same algorithm as sp_next_document_number, used only when CALL cannot run.
     *
     * @param string $documentType Sequence key.
     * @param string $prefix Prefix stored on the sequence row.
     * @return string
     */
    private function nextDocumentNumberInPhp(string $documentType, string $prefix): string
    {
        $connection = $this->connection();
        $year = (int)DateTime::now('Australia/Melbourne')->format('Y');
        $connection->execute(
            'INSERT INTO document_sequences
                (document_type, prefix, next_value, padding, include_year, reset_annually, last_reset_year, modified)
             VALUES (?, ?, LAST_INSERT_ID(1001), 6, 1, 0, ?, UTC_TIMESTAMP(6))
             ON DUPLICATE KEY UPDATE
                prefix = ?,
                next_value = LAST_INSERT_ID(
                    CASE
                        WHEN reset_annually = 1 AND COALESCE(last_reset_year, ?) <> ? THEN 2
                        ELSE next_value + 1
                    END
                ),
                last_reset_year = ?,
                modified = UTC_TIMESTAMP(6)',
            [$documentType, $prefix, $year, $prefix, $year, $year, $year],
        );

        $idRow = $connection->execute('SELECT LAST_INSERT_ID() - 1 AS sequence_value')->fetch('assoc');
        $value = is_array($idRow) ? (int)$idRow['sequence_value'] : 0;
        $meta = $connection->execute(
            'SELECT prefix, padding, include_year FROM document_sequences WHERE document_type = ?',
            [$documentType],
        )->fetch('assoc');
        if (!is_array($meta)) {
            throw new RuntimeException('Document sequence did not return a number.');
        }

        $padding = max(1, (int)$meta['padding']);
        $number = (string)$meta['prefix'];
        if ((int)$meta['include_year'] === 1) {
            $number .= '-' . $year;
        }
        $number .= '-' . str_pad((string)$value, $padding, '0', STR_PAD_LEFT);

        return $number;
    }

    /**
     * Available quantity at a location (generated column).
     *
     * @param int $variantId Product variant.
     * @param int $locationId Inventory location.
     * @return int
     */
    public function quantityAvailable(int $variantId, int $locationId): int
    {
        $row = $this->fetchTable('InventoryBalances')->find()
            ->select(['quantity_available'])
            ->where([
                'product_variant_id' => $variantId,
                'inventory_location_id' => $locationId,
            ])
            ->first();

        return $row ? (int)$row->get('quantity_available') : 0;
    }

    /**
     * Location with the most available units for a variant, if any.
     *
     * @param int $variantId Product variant.
     * @return array{id: int, available: int}|null
     */
    public function bestLocationFor(int $variantId): ?array
    {
        $row = $this->connection()->execute(
            'SELECT inventory_location_id AS id, quantity_available AS available
               FROM inventory_balances
              WHERE product_variant_id = ?
              ORDER BY quantity_available DESC, inventory_location_id ASC
              LIMIT 1',
            [$variantId],
            ['integer'],
        )->fetch('assoc');

        if ($row === false || $row === []) {
            $location = $this->ensureDefaultLocation();

            return ['id' => (int)$location->id, 'available' => 0];
        }

        return [
            'id' => (int)$row['id'],
            'available' => (int)$row['available'],
        ];
    }

    /**
     * Create the Melbourne warehouse when the catalogue has no locations yet.
     *
     * @return \App\Model\Entity\InventoryLocation
     */
    public function ensureDefaultLocation(): InventoryLocation
    {
        $locations = $this->fetchTable('InventoryLocations');
        $existing = $locations->find()
            ->where(['is_active' => true])
            ->orderBy(['id' => 'ASC'])
            ->first();
        if ($existing) {
            return $existing;
        }

        $location = $locations->newEmptyEntity();
        $location->code = 'MEL-WH';
        $location->name = 'Melbourne warehouse';
        $location->address = [
            'line1' => 'Melbourne',
            'state' => 'VIC',
            'country_code' => 'AU',
        ];
        $location->is_active = true;

        return $locations->saveOrFail($location);
    }

    /**
     * Execute a CALL and drain extra result sets so PDO stays usable.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection Connection.
     * @param string $sql CALL statement.
     * @param array<int, mixed> $params Bound parameters.
     * @return void
     */
    private function callProcedure(ConnectionInterface $connection, string $sql, array $params): void
    {
        $statement = $connection->execute($sql, $params);
        $this->drain($statement);
    }

    /**
     * Consume leftover procedure result sets.
     *
     * @param \Cake\Database\StatementInterface $statement Statement.
     * @return void
     */
    private function drain(StatementInterface $statement): void
    {
        try {
            while ($statement->nextRowset()) {
                // Discard informational result sets from CALL.
            }
        } catch (Throwable) {
            // Drivers throw once there are no further rowsets.
        }
        $statement->closeCursor();
    }

    /**
     * Id of the inventory_movements row written by the last CALL.
     *
     * @return int|null
     */
    public function lastInsertId(): ?int
    {
        $row = $this->connection()->execute('SELECT LAST_INSERT_ID() AS id')->fetch('assoc');
        $id = is_array($row) ? (int)$row['id'] : 0;

        return $id > 0 ? $id : null;
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('InventoryBalances')->getConnection();
    }
}
