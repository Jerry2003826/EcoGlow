<?php
// src/Model/Table/CartItemsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CartItemsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Cart_Item');
        $this->setPrimaryKey('CartItemId');

        $this->belongsTo('ShoppingCarts', [
            'className' => 'ShoppingCarts',
            'foreignKey' => 'CartId',
        ]);

        $this->belongsTo('Products', [
            'className' => 'Products',
            'foreignKey' => 'ProductId',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('CartId', 'create')
            ->integer('CartId')
            ->greaterThan('CartId', 0)
            ->existsIn('CartId', 'ShoppingCarts')
            ->requirePresence('ProductId', 'create')
            ->integer('ProductId')
            ->greaterThan('ProductId', 0)
            ->existsIn('ProductId', 'Products')
            ->requirePresence('Quantity', 'create')
            ->integer('Quantity')
            ->greaterThan('Quantity', 0)
            ->add('ProductId', 'uniquePerCart', [
                'rule' => ['validateUnique', ['scope' => 'CartId']],
                'message' => 'This product is already in the cart.',
            ]);

        return $validator;
    }
}
