<?php
// src/Model/Table/OrdersTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class OrdersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Orders');
        $this->setPrimaryKey('OrderId');

        $this->belongsTo('ShoppingCarts', [
            'className' => 'ShoppingCarts',
            'foreignKey' => 'CartId',
        ]);

        $this->belongsTo('Guests', [
            'className' => 'Guests',
            'foreignKey' => 'GuestId',
        ]);

        $this->hasMany('OrderItems', [
            'className' => 'OrderItems',
            'foreignKey' => 'OrderId',
        ]);
    }
}
