<?php
// src/Model/Table/ChatbotFaqTable.php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ChatbotFaqTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array $config Configuration values.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('ChatbotFaq');
        $this->setPrimaryKey('FaqId');
    }

    /**
     * Build validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('Question')
            ->maxLength('Question', 255)
            ->requirePresence('Question', 'create')
            ->notEmptyString('Question');

        $validator
            ->scalar('Answer')
            ->requirePresence('Answer', 'create')
            ->notEmptyString('Answer');

        $validator
            ->integer('SortOrder');

        $validator
            ->boolean('IsActive');

        return $validator;
    }

    /**
     * Find visible FAQ entries.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query instance.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query
            ->where([
                'IsActive' => 1,
            ])
            ->orderBy([
                'SortOrder' => 'ASC',
                'FaqId' => 'ASC',
            ]);
    }
}
