<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;
use Cake\Routing\Router;

class UsersController extends AppController
{

    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);


        $this->Authentication->allowUnauthenticated([
            'login',
            'add',
            'forgotPassword',
            'resetPassword',
        ]);
        $this->loadComponent('Turnstile');

        $action = $this->request->getParam('action');
        $identity = $this->request->getAttribute('identity');

        if (
            $action === 'login'
            || $action === 'add'
            || $action === 'forgotPassword'
            || $action === 'resetPassword'
        ) {
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

            return $this->redirect('/account');

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

    public function forgotPassword()
    {
        if ($this->request->is('post')) {
            $email = trim((string)$this->request->getData('email'));
            $user = $this->Users->find()
                ->where([
                    'Email' => $email,
                    'Status' => 'active',
                ])
                ->first();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->ResetToken = $token;
                $user->ResetTokenExpires = DateTime::now()->addHours(1);
                $this->Users->save($user);

                $resetUrl = Router::url([
                    'controller' => 'Users',
                    'action' => 'resetPassword',
                    $token,
                ], true);

                @mail(
                    (string)$user->Email,
                    'Reset your Eco Glow password',
                    "Use this link to reset your password:\n\n"
                    . $resetUrl
                    . "\n\nThis link expires in 1 hour."
                );
            }

            $this->Flash->success(
                'If that email is registered, a reset link has been sent.'
            );

            return $this->redirect(['action' => 'login']);
        }
    }

    public function resetPassword(?string $token = null)
    {
        if ($token === null || $token === '') {
            $this->Flash->error('That reset link is not valid.');

            return $this->redirect(['action' => 'forgotPassword']);
        }

        $user = $this->Users->find()
            ->where([
                'ResetToken' => $token,
                'Status' => 'active',
            ])
            ->first();

        $expired = !$user
            || empty($user->ResetTokenExpires)
            || strtotime((string)$user->ResetTokenExpires) < time();

        if ($expired) {
            $this->Flash->error('That reset link is invalid or has expired.');

            return $this->redirect(['action' => 'forgotPassword']);
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            $password = (string)$this->request->getData('password');
            $confirm = (string)$this->request->getData('password_confirm');

            if ($password === '' || strlen($password) < 8) {
                $this->Flash->error('Password must be at least 8 characters.');
                $this->set(compact('token'));

                return;
            }

            if ($password !== $confirm) {
                $this->Flash->error('Passwords do not match.');
                $this->set(compact('token'));

                return;
            }

            $user->Password = $password;
            $user->ResetToken = null;
            $user->ResetTokenExpires = null;

            if ($this->Users->save($user)) {
                $this->Flash->success('Your password has been reset. Please log in.');

                return $this->redirect(['action' => 'login']);
            }

            $this->Flash->error('The password could not be reset. Please try again.');
        }

        $this->set(compact('token'));
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
