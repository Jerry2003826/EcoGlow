<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\DateTime;

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
        $orders = $this->fetchTable('SalesOrders');
        $expired = $orders->find()
            ->where([
                'source_channel' => SalesOrder::CHANNEL_WEB,
                'status' => SalesOrder::STATUS_DRAFT,
                'payment_status IN' => ['pending', 'failed'],
                'hold_expires_at IS NOT' => null,
                'hold_expires_at <=' => DateTime::now('UTC'),
            ])
            ->all();

        $service = new OrderService(new InventoryLedger());
        $released = 0;
        foreach ($expired as $order) {
            $service->failUnpaid($order, (int)($order->created_by_user_id ?: 0), 'Checkout hold expired');
            $released++;
        }
        $io->out(sprintf('Released %d expired checkout hold(s).', $released));

        return static::CODE_SUCCESS;
    }
}
