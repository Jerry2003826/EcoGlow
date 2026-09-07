<?php
// src/Model/Table/ProductCategoriesTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ProductCategoriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Product_Category');
        $this->setPrimaryKey('CategoryId');
    }
}
