<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * OrderNotes Model
 *
 * @method \App\Model\Entity\OrderNote newEmptyEntity()
 * @method \App\Model\Entity\OrderNote patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\OrderNote saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class OrderNotesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('order_notes');
        $this->setDisplayField('body');
        $this->setPrimaryKey('id');
        $this->belongsTo('SalesOrders', ['foreignKey' => 'sales_order_id']);
        $this->belongsTo('Users', [
            'foreignKey' => 'author_user_id',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('body')
            ->requirePresence('body', 'create')
            ->notEmptyString('body');

        return $validator;
    }
}
