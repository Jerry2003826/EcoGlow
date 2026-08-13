<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FeatureFlag Entity
 *
 * @property string $flag_key
 * @property bool $enabled
 */
class FeatureFlag extends Entity
{
    /**
     * Flags are toggled by seed/migration, not forms.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
