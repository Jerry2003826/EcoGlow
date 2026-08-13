<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * UserPermissionOverride Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $permission_id
 * @property string $effect
 */
class UserPermissionOverride extends Entity
{
    /**
     * Overrides are never patched from a form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
