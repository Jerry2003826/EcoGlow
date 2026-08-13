<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrderAddress Entity
 *
 * @property int $id
 * @property int $sales_order_id
 * @property string $address_type
 * @property string $recipient_name
 * @property string $line1
 * @property string $suburb
 * @property string $state
 * @property string $postcode
 */
class OrderAddress extends Entity
{
    /**
     * Snapshots are written by OrderService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
