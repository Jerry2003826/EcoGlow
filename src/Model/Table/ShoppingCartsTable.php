<?php
// src/Model/Table/ShoppingCartsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ShoppingCartsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Shopping_Cart');
        $this->setPrimaryKey('CartId');

        $this->belongsTo('Guests', [
            'className' => 'Guests',
            'foreignKey' => 'GuestId',
        ]);

        $this->hasMany('CartItems', [
            'className' => 'CartItems',
            'foreignKey' => 'CartId',
        ]);
    }
}
