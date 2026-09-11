<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Customer;

class AccountController extends AppController
{
    /**
     * @return array{0: ?Customer, 1: mixed}
     */
    private function requireCustomer(): array
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return [null, $this->redirect('/login')];
        }

        $role = (string)($identity->get('UserRole') ?? '');

        if ($role === 'admin' || $role === 'staff') {
            return [null, $this->redirect('/admin')];
        }

        $customer = $this->fetchTable('Customers')->find()
            ->where(['UserId' => (int)$identity->getIdentifier()])
            ->first();

        if (!$customer) {
            $this->Flash->error('No customer account is linked to this login.');

            return [null, $this->redirect('/')];
        }

        return [$customer, null];
    }

    public function index()
    {
        [$customer, $redirect] = $this->requireCustomer();

        if ($redirect) {
            return $redirect;
        }

        $this->set(compact('customer'));
    }

    public function orders()
    {
        [$customer, $redirect] = $this->requireCustomer();

        if ($redirect) {
            return $redirect;
        }

        $orders = $this->fetchTable('Orders')
            ->find()
            ->where(['CustomerId' => $customer->CustomerId])
            ->contain(['OrderItems.Products', 'Payments'])
            ->orderBy([
                'OrderDate' => 'DESC',
                'OrderId' => 'DESC',
            ])
            ->all();

        $this->set(compact('customer', 'orders'));
    }

    public function order($id = null)
    {
        [$customer, $redirect] = $this->requireCustomer();

        if ($redirect) {
            return $redirect;
        }

        $order = $this->fetchTable('Orders')
            ->find()
            ->where([
                'OrderId' => (int)$id,
                'CustomerId' => $customer->CustomerId,
            ])
            ->contain([
                'Customers',
                'OrderItems.Products',
                'Payments',
                'Delivery',
            ])
            ->first();

        if (!$order) {
            $this->Flash->error('That order could not be found.');

            return $this->redirect(['action' => 'orders']);
        }

        $this->set(compact('customer', 'order'));
    }
}
