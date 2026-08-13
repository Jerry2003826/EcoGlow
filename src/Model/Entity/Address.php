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
     * Customer address form fields. customer_id is set() by the controller.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'label' => true,
        'recipient_name' => true,
        'company' => true,
        'line1' => true,
        'line2' => true,
        'suburb' => true,
        'state' => true,
        'postcode' => true,
        'country_code' => true,
        'phone' => true,
        'is_default_shipping' => true,
        'is_default_billing' => true,
    ];
}
