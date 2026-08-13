<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InventoryLocation Entity
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 */
class InventoryLocation extends Entity
{
    /**
     * Locations are not edited from a form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
