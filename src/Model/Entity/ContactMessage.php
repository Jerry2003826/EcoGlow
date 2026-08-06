<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContactMessage Entity
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $subject
 * @property string $message
 * @property bool $is_read
 * @property \Cake\I18n\DateTime $created
 */
class ContactMessage extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Only the public contact form fields are listed. `is_read` is set by the
     * admin area and `created` by the Timestamp behavior, so leaving either
     * mass assignable would let an anonymous submitter pre-mark their own
     * message as read or backdate it.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'email' => true,
        'phone' => true,
        'subject' => true,
        'message' => true,
    ];
}
