<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContactMessageEvent Entity
 *
 * @property int $id
 * @property int $contact_message_id
 * @property string $channel
 * @property string $direction
 * @property string $body
 */
class ContactMessageEvent extends Entity
{
    /**
     * Events are written by MessageService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
