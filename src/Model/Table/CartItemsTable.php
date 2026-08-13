<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * CartItems Model
 *
 * @method \App\Model\Entity\CartItem newEmptyEntity()
 * @method \App\Model\Entity\CartItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class CartItemsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('cart_items');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Carts', ['foreignKey' => 'cart_id']);
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
    }
}
