<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * IdempotencyRecords Model
 *
 * @method \App\Model\Entity\IdempotencyRecord newEmptyEntity()
 * @method \App\Model\Entity\IdempotencyRecord saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class IdempotencyRecordsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('idempotency_records');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);
        $this->mapJsonColumns(['response_body']);
    }
}
