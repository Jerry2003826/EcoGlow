<?php
// src/Model/Table/ProductsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProductsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Product');
        $this->setPrimaryKey('ProductId');
        $this->setDisplayField('ProductName');

        $this->belongsTo('ProductCategories', [
            'className' => 'ProductCategories',
            'foreignKey' => 'CategoryId',
        ]);

        $this->belongsTo('Suppliers', [
            'foreignKey' => 'SupplierId',
        ]);

        $this->hasOne('Inventory', [
            'foreignKey' => 'ProductId',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('ProductName')
            ->maxLength('ProductName', 150)
            ->requirePresence('ProductName', 'create')
            ->notEmptyString('ProductName');

        $validator
            ->integer('CategoryId')
            ->requirePresence('CategoryId', 'create')
            ->notEmptyString('CategoryId');

        $validator
            ->numeric('Price')
            ->requirePresence('Price', 'create')
            ->notEmptyString('Price');

        $validator->allowEmptyString('Description');
        $validator->allowEmptyString('Image');
        $validator->allowEmptyString('SupplierId');

        return $validator;
    }
}
