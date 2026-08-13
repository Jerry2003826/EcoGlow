<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CustomerInteraction Entity
 *
 * @property int $id
 * @property int $customer_id
 * @property string $channel
 * @property string $interaction_type
 */
class CustomerInteraction extends Entity
{
    /**
     * Interactions are written by services, not public forms.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
