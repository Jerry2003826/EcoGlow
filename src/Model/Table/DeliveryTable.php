<?php
// src/Model/Table/DeliveryTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class DeliveryTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Delivery');
        $this->setPrimaryKey('DeliveryId');

        $this->belongsTo('Orders', [
            'foreignKey' => 'OrderId',
        ]);
    }
}
