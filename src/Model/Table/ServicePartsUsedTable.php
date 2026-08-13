<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ServicePartsUsed Model
 *
 * @method \App\Model\Entity\ServicePartsUsed newEmptyEntity()
 * @method \App\Model\Entity\ServicePartsUsed saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ServicePartsUsedTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('service_parts_used');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);
        $this->belongsTo('ServiceRequests', ['foreignKey' => 'service_request_id']);
        $this->belongsTo('ServiceWorkLogs', ['foreignKey' => 'service_work_log_id']);
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
    }
}
