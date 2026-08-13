<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrderNote Entity
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int|null $author_user_id
 * @property string $note_type
 * @property string $body
 * @property bool $visible_to_customer
 * @property \Cake\I18n\DateTime $created
 */
class OrderNote extends Entity
{
    /**
     * Staff may submit the note body. Author, type and timestamps are set
     * by the controller/service.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'body' => true,
    ];
}
