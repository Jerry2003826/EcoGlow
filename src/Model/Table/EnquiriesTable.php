<?php
// src/Model/Table/EnquiriesTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTime;
use DateTimeZone;

class EnquiriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Enquiry');
        $this->setPrimaryKey('EnquiryId');
        $this->setDisplayField('Name');
    }

    public function beforeSave(EventInterface $event, $entity, $options): void
    {
        if ($entity->isNew() && empty($entity->CreatedDate)) {
            $entity->CreatedDate = (new DateTime('now', new DateTimeZone('Australia/Melbourne')))
                ->format('Y-m-d H:i:s');
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->notEmptyString('Name');
        $validator->email('Email')->notEmptyString('Email');
        $validator->notEmptyString('Message');
        $validator
            ->scalar('Status')
            ->inList('Status', ['New', 'Pending', 'Resolved'])
            ->allowEmptyString('Status');

        return $validator;
    }
}
