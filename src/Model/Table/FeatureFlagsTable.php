<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * FeatureFlags Model
 *
 * @method \App\Model\Entity\FeatureFlag newEmptyEntity()
 */
class FeatureFlagsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('feature_flags');
        $this->setPrimaryKey('flag_key');
        $this->setDisplayField('flag_key');
        $this->mapJsonColumns(['rules']);
    }
}
