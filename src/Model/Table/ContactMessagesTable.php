<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ContactMessagesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('contact_messages');
        $this->setDisplayField('subject');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'LEFT',
        ]);
    }
}
