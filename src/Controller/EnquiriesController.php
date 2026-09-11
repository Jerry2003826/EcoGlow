<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\ForbiddenException;

class EnquiriesController extends AppController
{

    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['add']);

        $this->loadComponent('Turnstile');

        if ($this->request->getParam('action') !== 'add') {
            $identity = $this->request->getAttribute('identity');
            $role = $identity ? (string)$identity->get('UserRole') : '';

            if (!in_array($role, ['admin', 'staff'], true)) {
                throw new ForbiddenException('You are not authorized to access enquiries.');
            }
        }
    }

    public function index()
    {
        $query = $this->Enquiries->find();
        $enquiries = $this->paginate($query);

        $this->set(compact('enquiries'));
    }

    public function view($id = null)
    {
        $enquiry = $this->Enquiries->get($id, contain: []);
        $this->set(compact('enquiry'));
    }

    public function add()
    {
        $enquiry = $this->Enquiries->newEmptyEntity();
        if ($this->request->is('post')) {
            $enquiry = $this->Enquiries->patchEntity($enquiry, $this->request->getData());

            $turnstileToken = $this->request->getData('cf-turnstile-response');
            $turnstileResponse = is_string($turnstileToken) && trim($turnstileToken) !== ''
                ? $this->Turnstile->validateTurnstile($turnstileToken, $this->request->clientIp())
                : ['success' => false];

            if (!$turnstileResponse || !$turnstileResponse['success']) {
                $this->log('Turnstile Response Error: ' . json_encode($turnstileResponse));
                $this->Flash->error('CAPTCHA challenge failed. Please try again.');

                $this->set(compact('enquiry'));

                return;
            }

            if ($this->Enquiries->save($enquiry)) {
                $this->Flash->success(__('The enquiry has been saved.'));

                return $this->redirect(['controller' => 'Pages', 'action' => 'display']);
            }
            $this->Flash->error(__('The enquiry could not be saved. Please, try again.'));
        }
        $this->set(compact('enquiry'));
    }

    public function edit($id = null)
    {
        $enquiry = $this->Enquiries->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $enquiry = $this->Enquiries->patchEntity($enquiry, $this->request->getData());
            if ($this->Enquiries->save($enquiry)) {
                $this->Flash->success(__('The enquiry has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The enquiry could not be saved. Please, try again.'));
        }
        $this->set(compact('enquiry'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $enquiry = $this->Enquiries->get($id);
        if ($this->Enquiries->delete($enquiry)) {
            $this->Flash->success(__('The enquiry has been deleted.'));
        } else {
            $this->Flash->error(__('The enquiry could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
