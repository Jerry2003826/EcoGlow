<?php
// src/Model/Table/QuotationsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class QuotationsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('quotations');

        $this->belongsTo('Customers');

        $this->hasMany('QuotationItems', [
            'foreignKey' => 'quotation_id',
            'saveStrategy' => 'replace',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('customer_id');

        $validator
            ->notEmptyString('category')
            ->inList('category', [
                'product_sale',
                'installation',
                'repair',
            ]);

        return $validator;
    }
}
