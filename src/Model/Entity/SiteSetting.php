<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SiteSetting Entity
 *
 * @property string $setting_key
 * @property mixed $setting_value
 */
class SiteSetting extends Entity
{
    /**
     * Settings are not patched from a public form.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
