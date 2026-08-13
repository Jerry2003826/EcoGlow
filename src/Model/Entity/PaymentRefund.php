<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PaymentRefund Entity
 *
 * @property int $id
 * @property int $payment_id
 * @property int $amount_cents
 * @property string $status
 */
class PaymentRefund extends Entity
{
    /**
     * Refunds are written by RefundService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
