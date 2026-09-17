<?php
// src/Controller/Admin/MessagesController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Mailer\EnquiryMailer;

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

        $enquiryOptions = [];

        foreach ($messages as $message) {
            $enquiryOptions[(int)$message->EnquiryId] = $message->Name . ' — ' . $message->Email;
        }

        $this->set(compact('messages', 'enquiryOptions'));
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

    public function contact()
    {
        $this->request->allowMethod(['post']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('messages.manage');

        $identity = $this->request->getAttribute('identity');
        $enquiryId = (int)$this->request->getData('enquiry_id');
        $subject = trim((string)$this->request->getData('subject'));
        $messageText = trim((string)$this->request->getData('message'));

        $enquiry = $this->fetchTable('Enquiries')
            ->find()
            ->where(['EnquiryId' => $enquiryId])
            ->first();

        if (!$enquiry) {
            $this->Flash->error('Enquiry not found.');

            return $this->redirect(['action' => 'index']);
        }

        if (empty($enquiry->Email)) {
            $this->Flash->error('This enquiry does not have an email address.');

            return $this->redirect(['action' => 'index']);
        }

        if ($subject === '' || $messageText === '') {
            $this->Flash->error('Subject and message are required.');

            return $this->redirect(['action' => 'index']);
        }

        try {
            (new EnquiryMailer())->send('enquiryContact', [
                $enquiry,
                $subject,
                $messageText,
                (string)($identity?->get('Email') ?? ''),
            ]);
            $this->Flash->success('Enquiry email sent successfully.');
        } catch (\Throwable $exception) {
            $this->Flash->error(
                'The email could not be delivered from this host. Please try again later.'
            );
        }

        return $this->redirect(['action' => 'index']);
    }
}
