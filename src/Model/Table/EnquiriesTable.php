<?php
// src/Model/Table/EnquiriesTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class EnquiriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Enquiry');
        $this->setPrimaryKey('EnquiryId');
        $this->setDisplayField('Name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->notEmptyString('Name');
        $validator->email('Email')->notEmptyString('Email');
        $validator->notEmptyString('Message');

        return $validator;
    }
}
