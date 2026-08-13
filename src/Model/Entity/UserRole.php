<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * UserRole Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property \Cake\I18n\DateTime|null $revoked_at
 */
class UserRole extends Entity
{
    /**
     * Assignments are never patched from a form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
