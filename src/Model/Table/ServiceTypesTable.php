<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ServiceTypes Model
 *
 * @method \App\Model\Entity\ServiceType newEmptyEntity()
 */
class ServiceTypesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('service_types');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');
        $this->addBehavior('Timestamp');
        $this->hasMany('ServiceRequests', ['foreignKey' => 'service_type_id']);
    }
}
