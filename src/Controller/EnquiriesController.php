<?php
declare(strict_types=1);

namespace App\Controller;

class EnquiriesController extends AppController
{

    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['add']);

        $this->loadComponent('Turnstile');
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
            if ($turnstileToken) {
                $turnstileResponse = $this->Turnstile->validateTurnstile($turnstileToken, $this->request->clientIp());

                if (!$turnstileResponse || !$turnstileResponse['success']) {
                    $this->log('Turnstile Response Error: ' . json_encode($turnstileResponse));
                    $this->Flash->error('CAPTCHA challenge failed. Please try again.');

                    $this->set(compact('enquiry'));

                    return $this->render();
                }
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
