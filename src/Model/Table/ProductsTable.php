<?php
// src/Model/Table/ProductsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ProductsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Product');
        $this->setPrimaryKey('ProductId');

        $this->belongsTo('ProductCategories', [
            'className' => 'ProductCategory',
            'foreignKey' => 'CategoryId',
        ]);

        $this->hasOne('Inventory', [
            'foreignKey' => 'ProductId',
        ]);
    }
}
