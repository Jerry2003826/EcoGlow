<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * CreditNoteItems Model
 */
class CreditNoteItemsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('credit_note_items');
        $this->setPrimaryKey('id');
        $this->belongsTo('CreditNotes', ['foreignKey' => 'credit_note_id']);
    }
}
