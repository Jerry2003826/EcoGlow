<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * CreditNotes Model
 */
class CreditNotesTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('credit_notes');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Invoices', ['foreignKey' => 'invoice_id']);
        $this->hasMany('CreditNoteItems', ['foreignKey' => 'credit_note_id']);
        $this->mapJsonColumns(['metadata']);
    }
}
