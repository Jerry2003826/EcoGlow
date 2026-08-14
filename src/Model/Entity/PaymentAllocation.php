<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * One capture/refund effect applied to an invoice.
 *
 * @property int $id
 * @property int $payment_id
 * @property int $invoice_id
 * @property string $allocation_type
 * @property int $amount_cents
 */
class PaymentAllocation extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
