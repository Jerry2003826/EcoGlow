<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Address Entity
 *
 * @property int $id
 * @property int $customer_id
 * @property string $line1
 * @property string $suburb
 * @property string $state
 * @property string $postcode
 */
class Address extends Entity
{
    /**
     * Addresses are not patched from a public form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
