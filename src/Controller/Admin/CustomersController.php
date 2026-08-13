<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ContactMask;

/**
 * Customer 360 list and detail.
 */
class CustomersController extends AdminController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $q = trim((string)$this->request->getQuery('q', ''));
        $connection = $this->fetchTable('Customers')->getConnection();
        $sql = 'SELECT customer_id, first_name, last_name, email, phone, status,
                       order_count, lifetime_order_value_cents, last_order_at, open_contact_count
                  FROM v_customer_360_summary
                 WHERE 1 = 1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (
                first_name LIKE :q OR last_name LIKE :q OR email LIKE :q OR phone LIKE :q
                OR CONCAT(COALESCE(first_name, \'\'), \' \', COALESCE(last_name, \'\')) LIKE :q
            )';
            $params['q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        }
        $sql .= ' ORDER BY last_order_at DESC, last_name ASC, first_name ASC';
        $rows = $connection->execute($sql, $params)->fetchAll('assoc');

        $canSeeContact = $this->canViewCustomerContact();
        $this->set(compact('rows', 'q', 'canSeeContact'));
    }

    /**
     * @param string|null $id Customer id.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $customerId = $this->recordId($id);
        $customer = $this->fetchTable('Customers')->get($customerId, contain: [
            'Addresses',
            'SalesOrders' => function ($query) {
                return $query->orderBy(['SalesOrders.placed_at' => 'DESC'])->limit(50);
            },
            'Invoices' => function ($query) {
                return $query->orderBy(['Invoices.issue_date' => 'DESC'])->limit(50);
            },
            'CustomerInteractions' => function ($query) {
                return $query->contain(['Users'])->orderBy(['CustomerInteractions.occurred_at' => 'DESC'])->limit(50);
            },
            'ContactMessages' => function ($query) {
                return $query->orderBy(['ContactMessages.created' => 'DESC'])->limit(50);
            },
        ]);

        $summary = $this->fetchTable('Customers')->getConnection()->execute(
            'SELECT * FROM v_customer_360_summary WHERE customer_id = ? LIMIT 1',
            [$customerId],
        )->fetch('assoc') ?: [];

        $canSeeContact = $this->canViewCustomerContact();
        $emailDisplay = $canSeeContact
            ? ($customer->email ?: '—')
            : ContactMask::email($customer->email);
        $phoneDisplay = $canSeeContact
            ? ($customer->phone ?: '—')
            : ContactMask::phone($customer->phone);

        $this->set(compact(
            'customer',
            'summary',
            'canSeeContact',
            'emailDisplay',
            'phoneDisplay',
        ));
    }
}
