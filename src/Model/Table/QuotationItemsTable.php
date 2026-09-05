<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class QuotationItemsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('quotation_items');

        $this->belongsTo('Quotations');
        $this->belongsTo('ProductVariants');
    }
}
