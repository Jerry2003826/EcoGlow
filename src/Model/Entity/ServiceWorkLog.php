<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceWorkLog Entity
 *
 * @property int $id
 * @property int $service_request_id
 * @property int $staff_user_id
 */
class ServiceWorkLog extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
