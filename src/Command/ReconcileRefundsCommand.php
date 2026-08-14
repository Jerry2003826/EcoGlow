<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\Payments\PaymentGatewayFactory;
use App\Service\Payments\RefundService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

/**
 * Re-reads pending Stripe refunds so a lost webhook cannot leave stock wrong.
 */
class ReconcileRefundsCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'payments.reconcile_refunds';
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $service = new RefundService(
            new OrderService(new InventoryLedger()),
            PaymentGatewayFactory::create(),
        );
        $updated = $service->reconcilePending();
        $io->out(sprintf('Reconciled %d pending refund(s).', $updated));

        return static::CODE_SUCCESS;
    }
}
