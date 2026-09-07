<?php
// src/Model/Table/OrderItemsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class OrderItemsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Order_Item');
        $this->setPrimaryKey('OrderItemId');

        $this->belongsTo('Orders', [
            'foreignKey' => 'OrderId',
        ]);

        $this->belongsTo('Products', [
            'foreignKey' => 'ProductId',
        ]);
    }
}
