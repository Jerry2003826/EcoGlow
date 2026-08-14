<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Permanent Stripe/order mismatch that needs a human.
 *
 * @property int $id
 * @property string $event_id
 * @property string|null $provider_payment_id
 * @property int|null $sales_order_id
 * @property string $reason
 * @property string|null $detail
 * @property string|null $payload_digest
 * @property \Cake\I18n\DateTime $created
 */
class PaymentReconciliationAlert extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
