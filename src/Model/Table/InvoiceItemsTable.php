<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * InvoiceItems Model
 *
 * @method \App\Model\Entity\InvoiceItem newEmptyEntity()
 * @method \App\Model\Entity\InvoiceItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class InvoiceItemsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('invoice_items');
        $this->setPrimaryKey('id');
        $this->belongsTo('Invoices', ['foreignKey' => 'invoice_id']);
        $this->mapJsonColumns(['metadata']);
    }
}
