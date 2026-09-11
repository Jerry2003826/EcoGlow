<?php
// src/Controller/Admin/ComingSoonController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Exception\ForbiddenException;

class ComingSoonController extends AdminController
{
    public function index(string $module)
    {
        $identity = $this->request->getAttribute('identity');
        $role = (string)($identity?->get('UserRole') ?? '');

        if (
            $role === 'staff'
            && in_array($module, ['reports', 'access', 'suppliers'], true)
        ) {
            throw new ForbiddenException('Forbidden');
        }

        $definitions = [
            'suppliers' => [
                'Supplier profile management',
                'Supplier contact directory',
                'Supplier performance tracking',
            ],
            'quotations' => [
                'Quotation drafting workflow',
                'Quotation approval management',
                'Quotation customer delivery',
            ],
            'access' => [
                'Role assignment management',
                'Permission review dashboard',
                'User access governance',
            ],
            'inventory' => [
                'Inventory adjustment tools',
                'Reorder monitoring',
                'Stock movement history',
            ],
            'customers' => [
                'Customer profile maintenance',
                'Customer interaction history',
                'Customer service activities',
            ],
            'products' => [
                'Product catalogue editing',
                'Product image management',
                'Product availability tracking',
            ],
            'shipments' => [
                'Shipment scheduling',
                'Carrier tracking updates',
                'Delivery workflow controls',
            ],
            'invoices' => [
                'Invoice generation',
                'Invoice payment tracking',
                'Invoice export options',
            ],
            'payments' => [
                'Payment reconciliation',
                'Payment history review',
                'Refund request linkage',
            ],
            'refunds' => [
                'Refund processing',
                'Refund approval workflow',
                'Refund audit history',
            ],
            'messages' => [
                'Customer message inbox',
                'Message assignment tools',
                'Response tracking',
            ],
            'reports' => [
                'Sales performance analysis',
                'Inventory reporting',
                'Operational KPI dashboards',
            ],
        ];

        $features = $definitions[$module] ?? [
            ucfirst($module) . ' module administration',
            ucfirst($module) . ' workflow management',
            ucfirst($module) . ' reporting and monitoring',
        ];

        $this->set(compact('module', 'features'));
    }
}
