<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * RolePermission Entity
 *
 * @property int $role_id
 * @property int $permission_id
 */
class RolePermission extends Entity
{
    /**
     * Join rows are never patched from a form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
