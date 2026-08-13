<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Customer Entity
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $email
 * @property string|null $phone
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $company
 * @property string $status
 * @property string $source
 */
class Customer extends Entity
{
    /**
     * Staff may submit identity fields when recording a walk-in order.
     * Status, source and timestamps are set by the service.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'first_name' => true,
        'last_name' => true,
        'email' => true,
        'phone' => true,
        'company' => true,
    ];

    /**
     * Human-readable name for lists.
     *
     * @return string
     */
    protected function _getLabel(): string
    {
        $name = trim($this->first_name . ' ' . (string)$this->last_name);
        if ($name !== '') {
            return $name;
        }
        if ($this->email) {
            return (string)$this->email;
        }
        if ($this->phone) {
            return (string)$this->phone;
        }

        return 'Customer #' . (string)$this->id;
    }
}
