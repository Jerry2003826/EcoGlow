<?php
// src/Model/Table/ProductCategoriesTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProductCategoriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Product_Category');
        $this->setPrimaryKey('CategoryId');
        $this->setDisplayField('CategoryName');

        $this->hasMany('Products', [
            'foreignKey' => 'CategoryId',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('CategoryName')
            ->maxLength('CategoryName', 100)
            ->requirePresence('CategoryName', 'create')
            ->notEmptyString('CategoryName');

        return $validator;
    }
}
