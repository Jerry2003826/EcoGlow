<?php
// src/Controller/Admin/QuotationsController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Mailer\QuotationMailer;
use Cake\I18n\DateTime;

class QuotationsController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('quotations.manage');

        $quotations = $this->fetchTable('Quotations')
            ->find()
            ->contain(['Customers'])
            ->orderBy(['QuotationId' => 'DESC'])
            ->all();

        $this->set(compact('quotations'));
    }

    public function view(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('quotations.manage');

        $quotation = $this->fetchTable('Quotations')->get(
            (int)$id,
            contain: ['Customers']
        );

        $this->set(compact('quotation'));
    }

    public function add()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('quotations.manage');

        $quotation = $this->fetchTable('Quotations')->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $customer = $this->fetchTable('Customers')->findOrCreateFromContact([
                'CustomerName' => $data['CustomerName'] ?? '',
                'Email' => $data['Email'] ?? '',
                'Address' => $data['Address'] ?? '',
                'Phone' => $data['Phone'] ?? '',
            ]);

            $quotation = $this->fetchTable('Quotations')->newEntity([
                'CustomerId' => $customer->CustomerId,
                'TotalAmount' => $data['TotalAmount'] ?? 0,
                'Status' => 'issued',
                'Notes' => $data['Notes'] ?? '',
            ]);

            $saved = $this->fetchTable('Quotations')->save($quotation);

            if (!$saved) {
                $quotation = $this->fetchTable('Quotations')->newEntity([
                    'CustomerId' => $customer->CustomerId,
                    'TotalAmount' => $data['TotalAmount'] ?? 0,
                ]);
                $saved = $this->fetchTable('Quotations')->save($quotation);
            }

            if ($saved) {
                $saved = $this->fetchTable('Quotations')->get(
                    $saved->QuotationId,
                    contain: ['Customers']
                );

                try {
                    (new QuotationMailer())->send('quotationIssued', [$saved]);
                    $saved->EmailedAt = DateTime::now();
                    $this->fetchTable('Quotations')->save($saved);
                    $this->Flash->success('Quotation saved and emailed.');
                } catch (\Throwable $exception) {
                    $this->Flash->error(
                        'Quotation saved. Email could not be delivered from this host.'
                    );
                }

                return $this->redirect([
                    'action' => 'view',
                    $saved->QuotationId,
                ]);
            }

            $this->Flash->error('Quotation could not be saved.');
        }

        $this->set(compact('quotation'));
    }
}
