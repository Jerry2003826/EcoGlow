<?php
// src/Model/Table/InventoryTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

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

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('Quantity')
            ->notEmptyString('Quantity');

        $validator
            ->integer('LowStockThreshold')
            ->notEmptyString('LowStockThreshold');

        return $validator;
    }
}
