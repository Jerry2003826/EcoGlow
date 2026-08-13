<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * CustomerInteractions Model
 *
 * @method \App\Model\Entity\CustomerInteraction newEmptyEntity()
 */
class CustomerInteractionsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('customer_interactions');
        $this->setPrimaryKey('id');
        $this->belongsTo('Customers', ['foreignKey' => 'customer_id']);
        $this->belongsTo('Users', ['foreignKey' => 'actor_user_id']);
        $this->mapJsonColumns(['metadata']);
    }
}
