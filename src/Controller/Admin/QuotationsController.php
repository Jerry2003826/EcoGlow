<?php
// src/Controller/Admin/QuotationsController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Mailer\QuotationMailer;
use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\DateTime;

class QuotationsController extends AdminController
{
    protected function requireManagePermission(): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (
            !$permissionService->hasPermission(
                (int)$identity->getIdentifier(),
                'quotations.manage'
            )
        ) {
            throw new ForbiddenException('Forbidden');
        }
    }

    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requireManagePermission();

        $quotations = $this->fetchTable('Quotations')
            ->find()
            ->contain(['Customers'])
            ->orderBy(['Quotations.quotation_number' => 'DESC'])
            ->all();

        $this->set(compact('quotations'));
    }

    public function view(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requireManagePermission();

        $quotation = $this->fetchTable('Quotations')->get(
            $id,
            contain: ['Customers', 'QuotationItems']
        );

        $this->set(compact('quotation'));
    }

    public function add()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requireManagePermission();

        $quotations = $this->fetchTable('Quotations');
        $quotation = $quotations->newEmptyEntity();

        $customers = $this->fetchTable('Customers')->find()->all();
        $productVariants = $this->fetchTable('ProductVariants')->find()->all();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            if (empty($data['customer_id']) || empty($data['category'])) {
                $this->Flash->error('Customer and category are required.');
                $this->set(compact('quotation', 'customers', 'productVariants'));
                return;
            }

            $items = [];
            $subtotal = 0;

            foreach (($data['items'] ?? []) as $row) {
                $variantId = (int)($row['product_variant_id'] ?? 0);

                if ($variantId <= 0) {
                    continue;
                }

                $variant = $this->fetchTable('ProductVariants')->get($variantId);
                $qty = max(1, (int)($row['quantity'] ?? 1));
                $lineTotal = ((int)$variant->price_cents) * $qty;

                $subtotal += $lineTotal;

                $items[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->name,
                    'sku' => $variant->sku,
                    'quantity' => $qty,
                    'unit_price_cents' => $variant->price_cents,
                    'line_total_cents' => $lineTotal,
                ];
            }

            if ($items === []) {
                $this->Flash->error('At least one item is required.');
                $this->set(compact('quotation', 'customers', 'productVariants'));
                return;
            }

            $tax = (int)round($subtotal * 0.10);
            $grand = $subtotal + $tax;

            $last = $quotations->find()->orderBy(['id' => 'DESC'])->first();
            $next = 3001;

            if ($last && preg_match('/QT-(\d+)/', (string)$last->quotation_number, $m)) {
                $next = ((int)$m[1]) + 1;
            }

            $quotation = $quotations->patchEntity($quotation, [
                'quotation_number' => 'QT-' . $next,
                'customer_id' => (int)$data['customer_id'],
                'category' => $data['category'],
                'status' => $data['status'],
                'valid_until' => $data['valid_until'] ?: null,
                'notes' => $data['notes'] ?? null,
                'subtotal_cents' => $subtotal,
                'tax_cents' => $tax,
                'grand_total_cents' => $grand,
                'created_by_user_id' => (int)$identity->getIdentifier(),
                'quotation_items' => $items,
            ], ['associated' => ['QuotationItems']]);

            if ($quotations->save($quotation, ['associated' => ['QuotationItems']])) {
                $savedQuotation = $quotations->get(
                    $quotation->id,
                    contain: ['Customers', 'QuotationItems']
                );

                if ($savedQuotation->status === 'issued') {
                    try {
                        (new QuotationMailer())
                            ->send('quotationIssued', [$savedQuotation]);

                        $savedQuotation->emailed_at = DateTime::now();
                        $quotations->save($savedQuotation);
                    } catch (\Throwable $exception) {
                        $this->Flash->error(
                            'Quotation saved but email delivery failed.'
                        );
                    }
                }

                return $this->redirect([
                    'action' => 'view',
                    $quotation->id,
                ]);
            }
        }

        $this->set(compact('quotation', 'customers', 'productVariants'));
    }
}
