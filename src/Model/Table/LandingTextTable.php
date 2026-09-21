<?php
// src/Model/Table/LandingTextTable.php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\LandingText;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class LandingTextTable extends Table
{
    /**
     * Configure the LandingText table mapping.
     *
     * @param array<string, mixed> $config Table config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('LandingText');
        $this->setPrimaryKey('TextId');
        $this->setDisplayField('TextKey');
        $this->setEntityClass(LandingText::class);
    }

    /**
     * Default validation rules for landing text fields.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('PageKey')
            ->maxLength('PageKey', 50)
            ->notEmptyString('PageKey');

        $validator
            ->scalar('TextKey')
            ->maxLength('TextKey', 100)
            ->requirePresence('TextKey', 'create')
            ->notEmptyString('TextKey');

        $validator
            ->scalar('TextValue')
            ->requirePresence('TextValue', 'create')
            ->notEmptyString('TextValue');

        $validator
            ->scalar('FieldLabel')
            ->maxLength('FieldLabel', 150)
            ->requirePresence('FieldLabel', 'create')
            ->notEmptyString('FieldLabel');

        $validator
            ->scalar('FieldType')
            ->maxLength('FieldType', 20)
            ->inList('FieldType', ['text', 'textarea', 'url'])
            ->notEmptyString('FieldType');

        $validator
            ->integer('SortOrder')
            ->notEmptyString('SortOrder');

        return $validator;
    }
}
