<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceType Entity
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 */
class ServiceType extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
