<?php
// src/Model/Table/ReceiptsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ReceiptsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Receipt');
        $this->setPrimaryKey('ReceiptId');
    }
}
