<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * InvoiceStatusHistory Model
 *
 * @method \App\Model\Entity\InvoiceStatusHistory newEmptyEntity()
 * @method \App\Model\Entity\InvoiceStatusHistory saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class InvoiceStatusHistoryTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('invoice_status_history');
        $this->setPrimaryKey('id');
        $this->belongsTo('Invoices', ['foreignKey' => 'invoice_id']);
        $this->belongsTo('Users', ['foreignKey' => 'changed_by_user_id']);
    }
}
