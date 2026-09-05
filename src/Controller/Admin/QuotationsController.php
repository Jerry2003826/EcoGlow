<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

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
            throw new ForbiddenException();
        }
    }

    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requireManagePermission();

        $quotations = $this->fetchTable('Quotations')
            ->find()
            ->contain(['Customers', 'QuotationItems'])
            ->orderBy([
                'Quotations.quotation_number' => 'DESC',
            ])
            ->all();

        $this->set(compact('quotations'));
    }

    public function view(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
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

        $quotationsTable = $this->fetchTable('Quotations');

        $quotation = $quotationsTable->newEmptyEntity();

        $customers = $this->fetchTable('Customers')
            ->find()
            ->orderBy([
                'Customers.first_name' => 'ASC',
                'Customers.last_name' => 'ASC',
            ])
            ->all();

        $productVariants = $this->fetchTable('ProductVariants')
            ->find()
            ->orderBy([
                'ProductVariants.name' => 'ASC',
            ])
            ->all();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $items = [];
            $subtotalCents = 0;

            foreach (($data['items'] ?? []) as $row) {
                $variantId = (int)($row['product_variant_id'] ?? 0);
                $quantity = max(1, (int)($row['quantity'] ?? 1));

                if ($variantId <= 0) {
                    continue;
                }

                $variant = $this->fetchTable('ProductVariants')->get($variantId);

                $lineTotal = (int)$variant->price_cents * $quantity;

                $subtotalCents += $lineTotal;

                $items[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->name,
                    'sku' => $variant->sku,
                    'quantity' => $quantity,
                    'unit_price_cents' => (int)$variant->price_cents,
                    'line_total_cents' => $lineTotal,
                ];
            }

            $taxCents = (int)round($subtotalCents * 0.10);
            $grandTotalCents = $subtotalCents + $taxCents;

            $last = $quotationsTable
                ->find()
                ->select(['quotation_number'])
                ->orderBy(['Quotations.id' => 'DESC'])
                ->first();

            $nextNumber = 3001;

            if ($last !== null && preg_match('/QT-(\d+)/', (string)$last->quotation_number, $matches)) {
                $nextNumber = ((int)$matches[1]) + 1;
            }

            $quotationData = [
                'quotation_number' => 'QT-' . $nextNumber,
                'customer_id' => (int)$data['customer_id'],
                'status' => (string)($data['status'] ?? 'draft'),
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal_cents' => $subtotalCents,
                'tax_cents' => $taxCents,
                'grand_total_cents' => $grandTotalCents,
                'created_by_user_id' => (int)$identity->getIdentifier(),
                'quotation_items' => $items,
            ];

            $quotation = $quotationsTable->patchEntity(
                $quotation,
                $quotationData,
                ['associated' => ['QuotationItems']]
            );

            if ($quotationsTable->save($quotation, ['associated' => ['QuotationItems']])) {
                return $this->redirect([
                    'action' => 'view',
                    $quotation->id,
                ]);
            }
        }

        $this->set(compact('quotation', 'customers', 'productVariants'));
    }
}
