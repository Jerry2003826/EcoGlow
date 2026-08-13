<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OutboundMessageEvent Entity
 *
 * @property int $id
 * @property int $outbound_message_id
 * @property string $event_type
 */
class OutboundMessageEvent extends Entity
{
    /**
     * Events are written by the queue consumer, never from a form.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
