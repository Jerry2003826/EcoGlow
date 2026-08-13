<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OutboundMessage Entity
 *
 * @property int $id
 * @property string $reference_number
 * @property string $channel
 * @property string $recipient
 * @property string $status
 */
class OutboundMessage extends Entity
{
    /**
     * Queue rows are written by OutboundQueue, never from a public form.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
