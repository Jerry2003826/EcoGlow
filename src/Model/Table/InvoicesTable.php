<?php
// src/Model/Table/InvoicesTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class InvoicesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Invoice');
        $this->setPrimaryKey('InvoiceId');

        $this->belongsTo('Orders', [
            'foreignKey' => 'OrderId',
        ]);
    }
}
