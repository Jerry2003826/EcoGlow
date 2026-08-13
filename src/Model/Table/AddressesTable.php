<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

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
}
