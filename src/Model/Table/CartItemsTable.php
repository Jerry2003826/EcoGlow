<?php
// src/Model/Table/CartItemsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

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
}
