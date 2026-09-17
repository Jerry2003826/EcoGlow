<?php
// src/Model/Table/PaymentsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class PaymentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Payment');
        $this->setPrimaryKey('PaymentId');

        $this->belongsTo('Orders', [
            'className' => 'Orders',
            'foreignKey' => 'OrderId',
        ]);
    }
}
