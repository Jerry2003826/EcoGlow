<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class SalesOrdersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sales_orders');
        $this->setPrimaryKey('id');
        $this->setDisplayField('order_number');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'LEFT',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('order_number')->maxLength('order_number', 190);
        $validator->allowEmptyString('order_number');

        $validator->nonNegativeInteger('customer_id');
        $validator->allowEmptyString('customer_id');

        $validator->allowEmptyString('guest_name');
        $validator->email('guest_email');
        $validator->allowEmptyString('guest_email');
        $validator->allowEmptyString('guest_phone');

        $validator->allowEmptyString('status');
        $validator->allowEmptyString('payment_status');
        $validator->allowEmptyString('currency');

        $validator->integer('subtotal_cents');
        $validator->integer('discount_cents');
        $validator->integer('shipping_cents');
        $validator->integer('tax_cents');
        $validator->integer('grand_total_cents');

        $validator->dateTime('placed_at');
        $validator->allowEmptyDateTime('placed_at');

        $validator->allowEmptyString('source_channel');
        $validator->integer('created_by_user_id');
        $validator->allowEmptyString('created_by_user_id');

        return $validator;
    }

    public function findPendingShipment(SelectQuery $query): SelectQuery
    {
        return $query->where([
            'SalesOrders.status IN' => ['confirmed', 'processing'],
            'SalesOrders.payment_status !=' => 'refunded',
        ]);
    }
}
