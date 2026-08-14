<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Customer;
use App\Model\Entity\User;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\MailerAwareTrait;
use Cake\Utility\Security;
use Cake\Validation\Validation;
use Throwable;

/**
 * Customer account: profile, addresses, own orders.
 */
class AccountController extends AppController
{
    use MailerAwareTrait;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->addHelper('Money');
        $this->Authentication->allowUnauthenticated(['confirmEmail']);
    }

    /**
     * Console-style account shell: same composition as the staff console.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $identity = $this->request->getAttribute('identity');
        $accountUserEmail = '';
        $accountUserName = '';
        if ($identity !== null) {
            $accountUserEmail = (string)$identity['email'];
            $accountUserName = trim(
                (string)$identity['first_name'] . ' ' . (string)$identity['last_name'],
            );
        }

        $action = (string)$this->request->getParam('action');
        $accountCurrent = match ($action) {
            'addresses', 'addAddress', 'deleteAddress' => 'addresses',
            'orders', 'order' => 'orders',
            'bookings', 'booking' => 'bookings',
            default => 'index',
        };

        $this->set(compact('accountUserEmail', 'accountUserName', 'accountCurrent'));
        $this->viewBuilder()->setLayout('account');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $customer = $this->requireCustomer();
        $user = $this->fetchTable('Users')->get((int)$customer->user_id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $first = trim((string)$this->request->getData('first_name'));
            $last = trim((string)$this->request->getData('last_name'));
            $phone = trim((string)$this->request->getData('phone'));
            $email = strtolower(trim((string)$this->request->getData('email')));
            if ($first === '' || $email === '') {
                $this->Flash->error(__('Name and email are required.'));
            } elseif (!Validation::email($email)) {
                $this->Flash->error(__('Please enter a valid email address.'));
            } else {
                $customer->set('first_name', $first);
                $customer->set('last_name', $last !== '' ? $last : null);
                $customer->set('phone', $phone !== '' ? $phone : null);
                $user->set('first_name', $first);
                $user->set('last_name', $last);
                $user->set('phone', $phone);
                $emailChanged = strtolower((string)$user->email) !== $email;
                if ($emailChanged) {
                    $currentPassword = (string)$this->request->getData('current_password');
                    if (!(new DefaultPasswordHasher())->check($currentPassword, (string)$user->password)) {
                        $this->Flash->error(__('Enter your current password to change your email.'));
                        $this->set(compact('customer', 'user'));

                        return null;
                    }
                    $taken = $this->fetchTable('Users')->find()
                        ->where(['email' => $email, 'id !=' => $user->id])
                        ->first();
                    if ($taken !== null) {
                        $this->Flash->error(__('Those details could not be saved. The email may already be in use.'));
                        $this->set(compact('customer', 'user'));

                        return null;
                    }
                    $this->queueEmailChange($user, $email);
                }
                if ($this->fetchTable('Customers')->save($customer) && $this->fetchTable('Users')->save($user)) {
                    $this->Flash->success($emailChanged
                        ? __('Your details were saved. Confirm the new email from the message we sent.')
                        : __('Your details have been updated.'));

                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('Those details could not be saved. The email may already be in use.'));
            }
        }

        $this->set(compact('customer', 'user'));

        return null;
    }

    /**
     * @return void
     */
    public function addresses(): void
    {
        $customer = $this->requireCustomer();
        $addresses = $this->fetchTable('Addresses')->find()
            ->where(['customer_id' => $customer->id])
            ->orderBy(['is_default_shipping' => 'DESC', 'id' => 'ASC'])
            ->all();
        $address = $this->fetchTable('Addresses')->newEmptyEntity();
        $this->set(compact('customer', 'addresses', 'address'));
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function addAddress(): ?Response
    {
        $this->request->allowMethod(['post']);
        $customer = $this->requireCustomer();
        $addresses = $this->fetchTable('Addresses');
        $address = $addresses->newEntity($this->request->getData(), [
            'fields' => [
                'label', 'recipient_name', 'company', 'line1', 'line2',
                'suburb', 'state', 'postcode', 'country_code', 'phone',
                'is_default_shipping', 'is_default_billing',
            ],
        ]);
        $address->set('customer_id', $customer->id);
        if (!(string)$address->get('country_code')) {
            $address->set('country_code', 'AU');
        }
        if ($addresses->save($address)) {
            $this->Flash->success(__('Address saved.'));
        } else {
            $this->Flash->error(__('That address could not be saved. Please check the form.'));
        }

        return $this->redirect(['action' => 'addresses']);
    }

    /**
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null
     */
    public function deleteAddress(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $customer = $this->requireCustomer();
        $address = $this->fetchTable('Addresses')->find()
            ->where(['id' => $this->recordId($id), 'customer_id' => $customer->id])
            ->first();
        if ($address === null) {
            throw new NotFoundException();
        }
        $this->fetchTable('Addresses')->delete($address);
        $this->Flash->success(__('Address removed.'));

        return $this->redirect(['action' => 'addresses']);
    }

    /**
     * @return void
     */
    public function orders(): void
    {
        $customer = $this->requireCustomer();
        $orders = $this->fetchTable('SalesOrders')->find()
            ->where(['customer_id' => $customer->id])
            ->orderBy(['SalesOrders.placed_at' => 'DESC', 'SalesOrders.id' => 'DESC'])
            ->all();
        $this->set(compact('customer', 'orders'));
    }

    /**
     * @param string|null $id Order id.
     * @return void
     */
    public function order(?string $id = null): void
    {
        $customer = $this->requireCustomer();
        $order = $this->fetchTable('SalesOrders')->find()
            ->contain(['SalesOrderItems'])
            ->where([
                'SalesOrders.id' => $this->recordId($id),
                'SalesOrders.customer_id' => $customer->id,
            ])
            ->first();
        if ($order === null) {
            throw new NotFoundException();
        }
        $this->set(compact('customer', 'order'));
    }

    /**
     * @return void
     */
    public function bookings(): void
    {
        $customer = $this->requireCustomer();
        $requests = $this->fetchTable('ServiceRequests')->find()
            ->contain(['ServiceTypes', 'ServiceAppointments'])
            ->where(['customer_id' => $customer->id])
            ->orderBy(['ServiceRequests.id' => 'DESC'])
            ->all();
        $this->set(compact('customer', 'requests'));
    }

    /**
     * @param string|null $id Request id.
     * @return void
     */
    public function booking(?string $id = null): void
    {
        $customer = $this->requireCustomer();
        $request = $this->fetchTable('ServiceRequests')->find()
            ->contain(['ServiceTypes', 'ServiceAppointments' => ['Users']])
            ->where([
                'ServiceRequests.id' => $this->recordId($id),
                'ServiceRequests.customer_id' => $customer->id,
            ])
            ->first();
        if ($request === null) {
            throw new NotFoundException();
        }
        $this->set(compact('customer', 'request'));
    }

    /**
     * @return \App\Model\Entity\Customer
     */
    private function requireCustomer(): Customer
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            throw new NotFoundException();
        }
        $user = $this->fetchTable('Users')->get((int)$identity->getIdentifier());
        $customer = $this->fetchTable('Customers')->find()
            ->where(['user_id' => $user->id])
            ->first();
        if ($customer === null) {
            $this->Flash->error(__('This account is not linked to a customer profile.'));
            throw new NotFoundException();
        }

        return $customer;
    }

    /**
     * Confirm a pending email change from the mailed token.
     *
     * @param string|null $token Plain token.
     * @return \Cake\Http\Response|null
     */
    public function confirmEmail(?string $token = null): ?Response
    {
        $users = $this->fetchTable('Users');
        $user = $users->find()
            ->where([
                'pending_email_token' => hash('sha256', (string)$token),
                'pending_email_expires >=' => DateTime::now('UTC'),
            ])
            ->first();
        if ($user === null || (string)$user->get('pending_email') === '') {
            $this->Flash->error(__('That confirmation link is invalid or has expired.'));

            return $this->redirect('/account/login');
        }
        $newEmail = (string)$user->get('pending_email');
        $user->set('email', $newEmail);
        $user->set('email_verified_at', DateTime::now('UTC'));
        $user->set('pending_email', null);
        $user->set('pending_email_token', null);
        $user->set('pending_email_expires', null);
        $user->set('auth_version', (int)($user->get('auth_version') ?: 1) + 1);
        $users->saveOrFail($user);
        $customer = $this->fetchTable('Customers')->find()
            ->where(['user_id' => $user->id])
            ->first();
        if ($customer !== null) {
            $customer->set('email', $newEmail);
            $this->fetchTable('Customers')->save($customer);
        }
        $this->Flash->success(__('Your email has been updated. Please sign in again.'));

        return $this->redirect('/account/login');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function resendVerification(): ?Response
    {
        $this->request->allowMethod(['post']);
        $customer = $this->requireCustomer();
        $users = $this->fetchTable('Users');
        $user = $users->get((int)$customer->user_id);
        if ($user->get('email_verified_at') !== null) {
            $this->Flash->success(__('This email is already confirmed.'));

            return $this->redirect(['action' => 'index']);
        }
        $token = bin2hex(Security::randomBytes(32));
        $user->set('email_verification_token', hash('sha256', $token));
        $user->set('email_verification_expires', DateTime::now('UTC')->addHours(24));
        $users->saveOrFail($user);
        try {
            $this->getMailer('User')->send('verifyEmail', [$user, $token]);
        } catch (Throwable $exception) {
            Log::error('Could not resend the email confirmation: ' . $exception->getMessage());
        }
        $this->Flash->success(__('If that mailbox can receive mail, a confirmation link is on the way.'));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @param \App\Model\Entity\User $user Account.
     * @param string $newEmail Address being confirmed.
     * @return void
     */
    private function queueEmailChange(User $user, string $newEmail): void
    {
        $token = bin2hex(Security::randomBytes(32));
        $user->set('pending_email', $newEmail);
        $user->set('pending_email_token', hash('sha256', $token));
        $user->set('pending_email_expires', DateTime::now('UTC')->addHours(24));
        try {
            $this->getMailer('User')->send('confirmEmailChange', [$user, $newEmail, $token]);
        } catch (Throwable $exception) {
            Log::error('Could not send the email-change confirmation: ' . $exception->getMessage());
        }
    }
}
