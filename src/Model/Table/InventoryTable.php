<?php
// src/Model/Table/InventoryTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class InventoryTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Inventory');
        $this->setPrimaryKey('InventoryId');

        $this->belongsTo('Products', [
            'foreignKey' => 'ProductId',
        ]);
    }
}
