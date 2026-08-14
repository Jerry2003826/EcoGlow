<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Throwable;

/**
 * Cancels unpaid checkout drafts whose inventory hold has expired.
 */
class ReleaseExpiredHoldsCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'orders.release_expired_holds';
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $released = (new OrderService(new InventoryLedger()))->releaseExpiredHolds();
        } catch (Throwable $exception) {
            $io->err('Hold cleanup failed: ' . $exception->getMessage());

            return static::CODE_ERROR;
        }
        $io->out(sprintf('Released %d expired checkout hold(s).', $released));

        return static::CODE_SUCCESS;
    }
}
