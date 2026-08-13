<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Addresses Model
 *
 * @method \App\Model\Entity\Address newEmptyEntity()
 */
class AddressesTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('addresses');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Customers', ['foreignKey' => 'customer_id']);
        $this->mapJsonColumns(['metadata']);
    }

    /**
     * @inheritDoc
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('recipient_name')
            ->maxLength('recipient_name', 200)
            ->requirePresence('recipient_name', 'create')
            ->notEmptyString('recipient_name', __('Please enter the recipient name.'));

        $validator
            ->scalar('line1')
            ->maxLength('line1', 200)
            ->requirePresence('line1', 'create')
            ->notEmptyString('line1', __('Please enter the first address line.'));

        $validator
            ->scalar('suburb')
            ->maxLength('suburb', 120)
            ->requirePresence('suburb', 'create')
            ->notEmptyString('suburb', __('Please enter the suburb.'));

        $validator
            ->scalar('state')
            ->maxLength('state', 80)
            ->requirePresence('state', 'create')
            ->notEmptyString('state', __('Please enter the state.'));

        $validator
            ->scalar('postcode')
            ->maxLength('postcode', 20)
            ->requirePresence('postcode', 'create')
            ->notEmptyString('postcode', __('Please enter the postcode.'));

        return $validator;
    }
}
