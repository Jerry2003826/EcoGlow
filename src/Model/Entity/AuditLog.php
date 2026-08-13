<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AuditLog Entity
 *
 * @property int $id
 * @property string $action
 * @property string $entity_type
 */
class AuditLog extends Entity
{
    /**
     * Audit rows are never patched from a form.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
