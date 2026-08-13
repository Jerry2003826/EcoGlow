<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * @property int $id
 * @property int|null $business_id
 * @property string $role_key
 * @property string $name
 * @property string|null $description
 * @property bool $is_system
 * @property bool $is_active
 */
class Role extends Entity
{
    /**
     * Roles are never patched from a form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
