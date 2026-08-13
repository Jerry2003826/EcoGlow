<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Permission Entity
 *
 * @property int $id
 * @property string $permission_key
 * @property string $module
 * @property string $name
 */
class Permission extends Entity
{
    /**
     * Permissions are never patched from a form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
