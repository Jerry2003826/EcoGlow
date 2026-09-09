<?php
declare(strict_types=1);

namespace App\Controller;

class UsersController extends AppController
{

    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);


        $this->Authentication->allowUnauthenticated(['login', 'add']);
        $this->loadComponent('Turnstile');

        $action = $this->request->getParam('action');
        $identity = $this->request->getAttribute('identity');

        if($action === 'login' || $action === 'add') {
            return;
        }

        //If not logged in, stop check the role and allow access to login and signup
        if (!$identity) {
            return;
        }

        //admin and staff can access all actions in userController
        if($identity->UserRole === 'admin' ||$identity->UserRole === 'staff') {
            return;
        }
        //Cusomer can logout
        if($action === 'logout') {
            return;
        }
        //Customer can edit their own profile
        if($action === 'edit') {
            $id = (int)$this->request->getParam('pass.0');
            if($id === (int)$identity->UserId) {
                return;
            }
        }

        $this->Flash->error(__('You are not authorized to access that location.'));
        $event->setResult($this->redirect(['controller' => 'Pages', 'action' => 'display']));

    }

    public function login()
    {
        $result = $this->Authentication->getResult();

        $turnstileToken = $this->request->getData('cf-turnstile-response');
        if ($this->request->is('post') && $turnstileToken) {
            $turnstileResponse = $this->Turnstile->validateTurnstile($turnstileToken, $this->request->clientIp());

            if (!$turnstileResponse || !$turnstileResponse['success']) {
                $this->log('Turnstile Response Error: ' . json_encode($turnstileResponse));
                $this->Flash->error('CAPTCHA challenge failed. Please try again.');
                $this->Authentication->logout();

                return $this->redirect(['controller' => 'Pages', 'action' => 'display']);
            }
        }

        if ($result && $result->isValid()) {
            $role = $this->request->getAttribute('identity');
            if($role->UserRole === 'admin' || $role->UserRole === 'staff') {
                $target = $this->Authentication->getLoginRedirect() ?? '/admin';
                return $this->redirect($target);
            } 
                
            return $this->redirect(['controller' => 'Pages', 'action' => 'display']);
             
        }
        if ($this->request->is('post')) {
            $this->Flash->error(__('Invalid username or password'));
        }
    }

    public function logout()
    {
        $this->Authentication->logout();
        return $this->redirect(['controller' => 'Pages', 'action' => 'display']);
    }

    public function index()
    {
        $query = $this->Users->find();
        $users = $this->paginate($query);

        $this->set(compact('users'));
    }

    public function view($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        $this->set(compact('user'));
    }

    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        $errors = [];

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['UserRole'] = 'customer';
            $data['customer']['Email'] = $data['Email'] ?? '';

            if (!empty($data['customer']['Phone'])) {
                $data['customer']['Phone'] = preg_replace(
                    '/[^0-9]/',
                    '',
                    (string)$data['customer']['Phone']
                );
            }

            $user = $this->Users->patchEntity($user, $data, [
                'fields' => ['Username', 'Email', 'Password', 'UserRole', 'customer'],
                'associated' => ['Customers'],
            ]);

            if (empty($data['customer']['CustomerName'])) {
                $errors['CustomerName'] = true;
            }

            if (empty($data['Email'])) {
                $errors['Email'] = true;
            }

            if (!empty($errors)) {
                $this->set(compact('user', 'errors'));

                return;
            }

            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'login']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user', 'errors'));
    }

    public function edit($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
