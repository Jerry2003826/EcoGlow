<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * SavedCartItems Model
 *
 * @method \App\Model\Entity\SavedCartItem newEmptyEntity()
 * @method \App\Model\Entity\SavedCartItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class SavedCartItemsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('saved_cart_items');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Customers', ['foreignKey' => 'customer_id']);
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
        $this->belongsTo('Carts', ['foreignKey' => 'saved_from_cart_id']);
    }
}
