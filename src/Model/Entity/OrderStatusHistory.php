<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrderStatusHistory Entity
 *
 * @property int $id
 * @property int $sales_order_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int|null $changed_by_user_id
 * @property string|null $note
 * @property \Cake\I18n\DateTime $created
 * @property \App\Model\Entity\User|null $user
 */
class OrderStatusHistory extends Entity
{
    /**
     * History rows are written by OrderService / the status trigger.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
