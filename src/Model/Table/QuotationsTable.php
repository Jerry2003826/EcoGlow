<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

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
}
