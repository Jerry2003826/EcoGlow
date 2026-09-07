<?php
// src/Controller/Admin/MessagesController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class MessagesController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('messages.manage');

        $messages = $this->fetchTable('Enquiries')
            ->find()
            ->orderBy(['EnquiryId' => 'DESC'])
            ->all();

        $this->set(compact('messages'));
    }

    public function view(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('messages.manage');

        $message = $this->fetchTable('Enquiries')->get((int)$id);

        $this->set(compact('message'));
    }
}
