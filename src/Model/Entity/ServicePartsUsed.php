<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServicePartsUsed Entity
 *
 * @property int $id
 * @property int $service_request_id
 * @property int $product_variant_id
 */
class ServicePartsUsed extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
