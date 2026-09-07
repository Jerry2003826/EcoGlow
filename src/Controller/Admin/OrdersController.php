<?php
// src/Controller/Admin/OrdersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class OrdersController extends AdminController
{
    protected function identity()
    {
        return $this->request->getAttribute('identity');
    }

    protected function hasPermission(string $permission): bool
    {
        $identity = $this->identity();

        if ($identity === null) {
            return false;
        }

        $permissionService = new PermissionService();

        return $permissionService->hasPermission(
            (int)$identity->getIdentifier(),
            $permission
        );
    }

    protected function requireAny(array $permissions): void
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return;
            }
        }

        throw new ForbiddenException();
    }

    public function index()
    {
        $identity = $this->identity();

        if ($identity === null) {
            return;
        }

        $this->requireAny(['orders.view', 'services.manage']);

        $q = trim((string)$this->request->getQuery('q'));

        $ordersQuery = $this->fetchTable('SalesOrders')
            ->find()
            ->contain(['Customers'])
            ->orderBy([
                'SalesOrders.placed_at' => 'DESC',
                'SalesOrders.id' => 'DESC',
            ]);

        if ($q !== '') {
            $ordersQuery
                ->leftJoinWith('Customers')
                ->where(function ($exp) use ($q) {
                    return $exp->or([
                        'SalesOrders.order_number LIKE' => '%' . $q . '%',
                        'SalesOrders.guest_name LIKE' => '%' . $q . '%',
                        'SalesOrders.guest_email LIKE' => '%' . $q . '%',
                        'SalesOrders.guest_phone LIKE' => '%' . $q . '%',
                        'Customers.first_name LIKE' => '%' . $q . '%',
                        'Customers.last_name LIKE' => '%' . $q . '%',
                        'Customers.email LIKE' => '%' . $q . '%',
                        'Customers.phone LIKE' => '%' . $q . '%',
                    ]);
                });
        }

        $bookings = $this->fetchTable('ServiceBookings')
            ->find()
            ->contain(['Customers'])
            ->orderBy([
                'ServiceBookings.scheduled_at' => 'ASC',
            ])
            ->all();

        $orders = $ordersQuery->all();

        $canCreateOrder = $this->hasPermission('orders.create');
        $canManageOrders = $this->hasPermission('orders.manage');
        $canManageServices = $this->hasPermission('services.manage');

        $this->set(compact(
            'orders',
            'bookings',
            'q',
            'canCreateOrder',
            'canManageOrders',
            'canManageServices'
        ));
    }

    public function add()
    {
        $identity = $this->identity();

        if ($identity === null) {
            return;
        }

        $this->requireAny(['orders.create']);

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            foreach (['first_name', 'email', 'phone', 'address_line_1'] as $field) {
                if (trim((string)($data[$field] ?? '')) === '') {
                    return;
                }
            }

            $customer = $this->fetchTable('Customers')
                ->findOrCreateFromContact($data);

            $salesOrders = $this->fetchTable('SalesOrders');

            $last = $salesOrders
                ->find()
                ->select(['order_number'])
                ->orderBy(['SalesOrders.id' => 'DESC'])
                ->first();

            $next = 1001;

            if ($last && preg_match('/SO-(\d+)/', (string)$last->order_number, $m)) {
                $next = ((int)$m[1]) + 1;
            }

            $order = $salesOrders->newEntity([
                'order_number' => 'SO-' . $next,
                'customer_id' => $customer->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'currency' => 'AUD',
                'subtotal_cents' => 0,
                'discount_cents' => 0,
                'shipping_cents' => 0,
                'tax_cents' => 0,
                'grand_total_cents' => 0,
                'placed_at' => date('Y-m-d H:i:s'),
            ]);

            if ($salesOrders->save($order)) {
                return $this->redirect(['action' => 'view', $order->id]);
            }
        }
    }

    public function addBooking()
    {
        $identity = $this->identity();

        if ($identity === null) {
            return;
        }

        $this->requireAny(['services.manage']);

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $customer = $this->fetchTable('Customers')
                ->findOrCreateFromContact($data);

            $bookingsTable = $this->fetchTable('ServiceBookings');

            $last = $bookingsTable
                ->find()
                ->orderBy(['ServiceBookings.id' => 'DESC'])
                ->first();

            $next = 2001;

            if ($last && preg_match('/SB-(\d+)/', (string)$last->booking_number, $m)) {
                $next = ((int)$m[1]) + 1;
            }

            $booking = $bookingsTable->newEntity([
                'booking_number' => 'SB-' . $next,
                'customer_id' => $customer->id,
                'service_type' => $data['service_type'],
                'scheduled_at' => $data['scheduled_at'],
                'status' => 'scheduled',
                'notes' => $data['notes'] ?? null,
            ]);

            if ($bookingsTable->save($booking)) {
                return $this->redirect(['action' => 'booking', $booking->id]);
            }
        }
    }

    public function view(int|string $id)
    {
        $identity = $this->identity();

        if ($identity === null) {
            return;
        }

        $this->requireAny(['orders.view']);

        $order = $this->fetchTable('SalesOrders')->get(
            $id,
            contain: ['Customers']
        );

        if (
            $this->request->is('post') &&
            $this->hasPermission('orders.manage')
        ) {
            if ($this->request->getData('form_name') === 'status') {
                $order->status = (string)$this->request->getData('status');
            }

            if ($this->request->getData('form_name') === 'delivery') {
                $order->promised_delivery_on = $this->request->getData('promised_delivery_on');
                $order->delivery_notes = $this->request->getData('delivery_notes');
            }

            $this->fetchTable('SalesOrders')->save($order);

            return $this->redirect(['action' => 'view', $id]);
        }

        $canManageOrders = $this->hasPermission('orders.manage');

        $this->set(compact('order', 'canManageOrders'));
    }

    public function booking(int|string $id)
    {
        $identity = $this->identity();

        if ($identity === null) {
            return;
        }

        $this->requireAny(['services.manage']);

        $booking = $this->fetchTable('ServiceBookings')->get(
            $id,
            contain: ['Customers']
        );

        if ($this->request->is('post')) {
            $booking->status = (string)$this->request->getData('status');
            $booking->scheduled_at = $this->request->getData('scheduled_at');
            $booking->notes = $this->request->getData('notes');

            $this->fetchTable('ServiceBookings')->save($booking);

            return $this->redirect(['action' => 'booking', $id]);
        }

        $this->set(compact('booking'));
    }
}
