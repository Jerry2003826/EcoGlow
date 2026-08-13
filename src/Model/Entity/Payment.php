<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Payment Entity
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int $amount_cents
 * @property string $status
 */
class Payment extends Entity
{
    /**
     * Payments are recorded by InvoiceService / staff actions.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
