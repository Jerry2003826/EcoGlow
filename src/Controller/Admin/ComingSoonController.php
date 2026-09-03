<?php

declare(strict_types=1);

namespace App\Controller\Admin;

class ComingSoonController extends AdminController
{
    public function index(string $module)
    {
        $definitions = [
            'orders' => [
                'Order lifecycle management',
                'Shipment workflow tracking',
                'Customer order history',
            ],
            'shipments' => [
                'Packing management',
                'Dispatch tracking',
                'Carrier integrations',
            ],
            'products' => [
                'Product catalogue maintenance',
                'Variant management',
                'Pricing administration',
            ],
            'inventory' => [
                'Stock adjustments',
                'Warehouse allocation',
                'Replenishment planning',
            ],
            'customers' => [
                'Customer profiles',
                'Customer communication history',
                'Customer account insights',
            ],
            'invoices' => [
                'Invoice generation',
                'Tax invoice history',
                'Invoice delivery tracking',
            ],
            'payments' => [
                'Payment recording',
                'Payment reconciliation',
                'Payment audit trail',
            ],
            'refunds' => [
                'Refund processing',
                'Refund approvals',
                'Refund reporting',
            ],
            'reports' => [
                'Sales reporting',
                'Financial reporting',
                'Operational analytics',
            ],
            'messages' => [
                'Inbox management',
                'Message assignment',
                'Response tracking',
            ],
            'settings' => [
                'Role administration',
                'Store configuration',
                'System preferences',
            ],
        ];

        $this->set([
            'module' => $module,
            'features' => $definitions[$module] ?? [
                'Future capability one',
                'Future capability two',
                'Future capability three',
            ],
        ]);
    }
}
