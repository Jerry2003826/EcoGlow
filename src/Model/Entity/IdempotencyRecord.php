<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * IdempotencyRecord Entity
 *
 * @property int $id
 * @property string $scope
 * @property string $idempotency_key
 */
class IdempotencyRecord extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
