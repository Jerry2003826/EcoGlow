<?php
declare(strict_types=1);

namespace App\Controller;

use App\Middleware\LoginThrottleMiddleware;
use App\Middleware\SessionIntegrityMiddleware;
use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use App\Service\AuditLogger;
use App\Service\Cart\CartService;
use App\Service\Security\SensitiveSession;
use App\Service\Security\TotpService;
use Cake\Datasource\ConnectionInterface;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\MailerAwareTrait;
use Cake\Utility\Security;
use Cake\Validation\Validation;
use Throwable;

/**
 * Users Controller
 *
 * Handles administrator authentication for the Eco Glow Lighting admin area,
 * including the self-service password reset flow.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class UsersController extends AppController
{
    use MailerAwareTrait;

    /**
     * How long a password reset link stays usable, in hours.
     *
     * @var int
     */
    public const RESET_TOKEN_TTL_HOURS = 1;

    /**
     * Entropy of a reset token, in bytes (rendered as twice as many hex chars).
     *
     * @var int
     */
    public const RESET_TOKEN_BYTES = 32;

    /**
     * The users table.
     *
     * @var \App\Model\Table\UsersTable
     */
    protected UsersTable $Users;

    /**
     * Controller initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // `logout` is allowed anonymously so that hitting it while already
        // signed out lands on the login page instead of bouncing through
        // /login?redirect=/logout and signing the user straight back out.
        // The reset actions are anonymous by nature: the whole point is that
        // the visitor cannot sign in.
        $this->Authentication->allowUnauthenticated([
            'login',
            'customerLogin',
            'register',
            'logout',
            'forgotPassword',
            'resetPassword',
            'verifyEmail',
        ]);

        $this->Users = $this->fetchTable('Users');
    }

    /**
     * Login method.
     *
     * Redirects an already-authenticated user straight to the admin area.
     * After a successful login, honours the sanitized `redirect` query param
     * (relative paths only, to prevent open-redirect attacks).
     *
     * Brute-force throttling is split in two: LoginThrottleMiddleware blocks
     * login POSTs while locked out (so authentication never runs), and this
     * action counts failures and clears the counter on success.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $ip = $this->request->clientIp() ?: 'unknown';
        $email = trim((string)$this->request->getData('email'));

        // An already-valid session always wins, even from a locked-out IP.
        $result = $this->Authentication->getResult();
        if ($result->isValid() && $this->request->is('post') && $email !== '') {
            $identity = $this->Authentication->getIdentity();
            if ($identity !== null) {
                $current = $this->Users->get((int)$identity->getIdentifier());
                if (strcasecmp((string)$current->email, $email) !== 0) {
                    $this->Authentication->logout();
                    SensitiveSession::clear($this->request->getSession());
                    $this->request->getSession()->renew();
                    $this->Flash->info(__('You were signed out of the previous account. Please sign in again.'));

                    return null;
                }
            }
        }
        if ($result->isValid()) {
            LoginThrottleMiddleware::clear($ip, LoginThrottleMiddleware::SCOPE_LOGIN, $email);

            if ($this->request->is('post')) {
                $identity = $this->Authentication->getIdentity();
                if ($identity !== null) {
                    $user = $this->Users->get((int)$identity->getIdentifier());
                    $status = (string)($user->get('status') ?: 'active');
                    if ($status !== 'active' || $user->get('deleted') !== null) {
                        $this->Authentication->logout();
                        $this->Flash->error(__('This account is inactive.'));

                        return null;
                    }
                    $user->set('last_login_at', DateTime::now('UTC'));
                    $this->Users->save($user);
                    $session = $this->request->getSession();
                    $session->delete(SensitiveSession::MFA_SETUP);
                    $session->delete(SensitiveSession::CHECKOUT_ATTEMPT);
                }
            }

            $identity = $this->Authentication->getIdentity();
            $user = $identity !== null
                ? $this->Users->get((int)$identity->getIdentifier())
                : null;
            $this->mergeCartAfterLogin($user);
            if ($user !== null) {
                $this->persistSessionVersion($user);
            }
            $fallback = $user !== null ? $this->afterLoginPath($user) : '/admin';
            $target = $this->Authentication->getLoginRedirect($fallback) ?? $fallback;
            if ($user !== null && $this->isCustomerUser($user) && str_starts_with((string)$target, '/admin')) {
                $target = '/account';
            }
            if ($user !== null && $this->isStaffUser($user) && str_starts_with((string)$target, '/account')) {
                $target = '/admin';
            }

            return $this->redirect($target);
        }

        // Locked-out POSTs are intercepted by the middleware and redirected
        // here as a GET; surface the reason to the user.
        if (LoginThrottleMiddleware::isLockedOut($ip, LoginThrottleMiddleware::SCOPE_LOGIN, $email)) {
            $this->Flash->error(
                __('Too many failed login attempts. Please wait a few minutes and try again.'),
            );

            return null;
        }

        if ($this->request->is('post')) {
            LoginThrottleMiddleware::registerFailure($ip, $email);
            $this->Flash->error(__('Invalid email or password'));
        }

        return null;
    }

    /**
     * Customer sign-in. Same authenticator as /login; destination is /account.
     *
     * @return \Cake\Http\Response|null
     */
    public function customerLogin(): ?Response
    {
        $this->viewBuilder()->setTemplate('customer_login');

        return $this->login();
    }

    /**
     * Create a customer user + customers row. Role is set() after newEntity.
     *
     * @return \Cake\Http\Response|null
     */
    public function register(): ?Response
    {
        $this->viewBuilder()->setTemplatePath('Pages');
        $this->viewBuilder()->setTemplate('register');

        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $identity = $this->Authentication->getIdentity();
            $user = $identity !== null
                ? $this->Users->get((int)$identity->getIdentifier())
                : null;

            return $this->redirect($user !== null ? $this->afterLoginPath($user) : '/account');
        }

        if (!$this->request->is('post')) {
            $this->set('user', $this->Users->newEmptyEntity());

            return null;
        }

        $name = trim((string)$this->request->getData('name'));
        $phone = trim((string)$this->request->getData('phone'));
        $email = trim((string)$this->request->getData('email'));

        $user = $this->Users->newEntity(
            [
                'email' => $email,
                'password' => (string)$this->request->getData('password'),
                'confirm_password' => (string)$this->request->getData('password_confirm'),
            ],
            [
                'validate' => 'register',
                'fields' => ['email', 'password', 'confirm_password'],
            ],
        );

        if ($name === '') {
            $user->setError('name', ['_empty' => __('Please enter your name.')]);
        }
        if ($phone === '') {
            $user->setError('phone', ['_empty' => __('Please enter a phone number.')]);
        }

        if ($user->hasErrors()) {
            $this->Flash->error(__('Your account could not be created. Please check the form and try again.'));
            $this->set(compact('user'));

            return null;
        }

        [$firstName, $lastName] = $this->splitName($name);

        try {
            $this->connection()->transactional(function () use ($user, $firstName, $lastName, $phone, $email) {
                $user->set('first_name', $firstName);
                $user->set('last_name', $lastName);
                $user->set('phone', $phone);
                $user->set('role', 'customer');
                $user->set('status', 'active');
                $this->Users->saveOrFail($user);

                $customers = $this->fetchTable('Customers');
                $customer = $customers->newEmptyEntity();
                $customer->set('user_id', $user->id);
                $customer->set('email', $email);
                $customer->set('phone', $phone);
                $customer->set('first_name', $firstName);
                $customer->set('last_name', $lastName !== '' ? $lastName : null);
                $customer->set('status', 'active');
                $customer->set('source', 'web');
                $customers->saveOrFail($customer);
            });
        } catch (Throwable $exception) {
            Log::error('Customer registration failed: ' . $exception->getMessage());
            $this->Flash->error(__('Your account could not be created. Please check the form and try again.'));

            return null;
        }

        $this->sendEmailVerification($user);
        $this->Authentication->setIdentity($user);
        $this->persistSessionVersion($user);
        $this->mergeCartAfterLogin($user);
        $this->Flash->success(__(
            'Your account is ready. Please confirm your email before completing a purchase.',
        ));

        return $this->redirect('/account');
    }

    /**
     * Logout method.
     *
     * GET never destroys the session (that would be CSRF-logout). It shows a
     * confirm form when signed in, or redirects to login when already signed out.
     * Only POST + CSRF token actually signs the user out.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        if (!$this->request->is('post')) {
            if ($identity === null) {
                return $this->redirect(['action' => 'login']);
            }

            return null;
        }

        $wasCustomer = false;
        if ($identity !== null) {
            $user = $this->Users->get((int)$identity->getIdentifier());
            $wasCustomer = $this->isCustomerUser($user);
        }
        $this->Authentication->logout();
        $session = $this->request->getSession();
        SensitiveSession::clear($session);
        $session->renew();

        return $this->redirect($wasCustomer ? '/account/login' : ['action' => 'login']);
    }

    /**
     * Request a password reset link.
     *
     * Every submission ends on the same flash message and the same redirect,
     * whether or not the address belongs to an account. That is deliberate:
     * any difference in wording, status code or timing branch would turn this
     * form into an oracle for enumerating admin accounts.
     *
     * @return \Cake\Http\Response|null
     */
    public function forgotPassword(): ?Response
    {
        $resetArea = $this->passwordResetArea();
        $loginPath = $this->loginPathForArea($resetArea);
        $this->set(compact('loginPath'));

        if (!$this->request->is('post')) {
            return null;
        }

        $started = hrtime(true);
        $ip = $this->request->clientIp() ?: 'unknown';
        $scope = LoginThrottleMiddleware::SCOPE_PASSWORD_RESET;
        $email = trim((string)$this->request->getData('email'));

        if (LoginThrottleMiddleware::isLockedOut($ip, $scope, $email)) {
            $this->Flash->error(
                __('Too many password reset requests. Please wait a few minutes and try again.'),
            );
            $this->padForgotPasswordTiming($started);

            return null;
        }

        // Count the attempt before any lookup so the counter cannot be
        // out-run by a script hammering the endpoint.
        LoginThrottleMiddleware::registerAttempt($ip, $scope, $email);

        // A malformed address is rejected on the spot. This reveals nothing
        // about who holds an account, so it is safe to be specific here.
        if (!Validation::email($email)) {
            $this->Flash->error(__('Please enter a valid email address.'));
            $this->padForgotPasswordTiming($started);

            return null;
        }

        $user = $this->Users->find()
            ->where(['email' => $email])
            ->first();

        if ($user !== null) {
            $this->sendResetLink($user, $resetArea);
        }

        $this->Flash->success(__(
            'If that email address has an account, we have sent a password reset link. Please check your inbox.',
        ));
        $this->padForgotPasswordTiming($started);

        return $this->redirect($loginPath);
    }

    /**
     * Choose a new password using a link from the reset email.
     *
     * @param string|null $token The plain-text token taken from the URL.
     * @return \Cake\Http\Response|null
     */
    public function resetPassword(?string $token = null): ?Response
    {
        $resetArea = $this->passwordResetArea();
        $loginPath = $this->loginPathForArea($resetArea);
        $this->set(compact('loginPath'));

        $user = $this->findUserByResetToken((string)$token);

        if ($user === null) {
            $this->Flash->error(
                __('That password reset link is invalid or has expired. Please request a new one.'),
            );

            return $this->redirect(['action' => 'forgotPassword', '?' => $this->resetAreaQuery($resetArea)]);
        }

        if ($this->request->is(['post', 'put'])) {
            // The two fields are listed explicitly rather than handing the
            // whole request body to patchEntity: `email` is mass-assignable,
            // so a crafted POST could otherwise take over the account.
            $user = $this->Users->patchEntity(
                $user,
                [
                    'password' => (string)$this->request->getData('password'),
                    'confirm_password' => (string)$this->request->getData('confirm_password'),
                ],
                ['validate' => 'resetPassword'],
            );

            if (!$user->hasErrors()) {
                // Clearing the token in the same write that stores the new
                // password is what makes a link single-use.
                $user->set('password_reset_token', null);
                $user->set('password_reset_expires', null);
                $user->set('auth_version', (int)($user->get('auth_version') ?: 1) + 1);

                if ($this->Users->save($user)) {
                    LoginThrottleMiddleware::clear(
                        $this->request->clientIp() ?: 'unknown',
                        LoginThrottleMiddleware::SCOPE_PASSWORD_RESET,
                    );

                    $this->Flash->success(__('Your password has been updated. Please sign in.'));

                    return $this->redirect($loginPath);
                }
            }

            $this->Flash->error(__('Your password could not be updated. Please check the form and try again.'));
        }

        $minPasswordLength = UsersTable::MIN_PASSWORD_LENGTH;
        $this->set(compact('user', 'token', 'minPasswordLength'));

        return null;
    }

    /**
     * Issue a fresh reset token and email the link to its owner.
     *
     * Delivery failures are logged but never surfaced: an SMTP outage must not
     * change what the visitor sees, or the difference becomes an account
     * oracle. The reset simply does not arrive and can be requested again.
     *
     * @param \App\Model\Entity\User $user The account requesting the reset.
     * @param string $resetArea customer|staff, used only for the return path.
     * @return void
     */
    protected function sendResetLink(User $user, string $resetArea = 'staff'): void
    {
        $token = bin2hex(Security::randomBytes(self::RESET_TOKEN_BYTES));

        // Only the hash is persisted, so a leaked database row cannot be
        // turned back into a working link. Overwriting the column also
        // invalidates any link handed out earlier.
        $user->set('password_reset_token', hash('sha256', $token));
        $user->set('password_reset_expires', DateTime::now()->addHours(self::RESET_TOKEN_TTL_HOURS));

        if (!$this->Users->save($user)) {
            Log::error(sprintf('Could not store a password reset token for user #%d.', $user->id));

            return;
        }

        try {
            $this->getMailer('User')->send('resetPassword', [
                $user,
                $token,
                self::RESET_TOKEN_TTL_HOURS,
                $resetArea === 'customer',
            ]);
        } catch (Throwable $e) {
            Log::error(sprintf('Could not send the password reset email: %s', $e->getMessage()));
        }
    }

    /**
     * Look up the account a still-valid reset token belongs to.
     *
     * The lookup is by hash, so the column the query matches on is the same
     * useless-on-its-own value that is stored; and the expiry is part of the
     * condition, so an outdated token simply matches nothing.
     *
     * @param string $token The plain-text token taken from the URL.
     * @return \App\Model\Entity\User|null
     */
    protected function findUserByResetToken(string $token): ?User
    {
        if ($token === '') {
            return null;
        }

        /** @var \App\Model\Entity\User|null $user */
        $user = $this->Users->find()
            ->where([
                'password_reset_token' => hash('sha256', $token),
                'password_reset_expires >=' => DateTime::now(),
            ])
            ->first();

        return $user;
    }

    /**
     * Where this reset was started: customer storefront or staff console.
     *
     * Query, posted field, then session — never inferred from the account —
     * so known and unknown addresses still share one redirect.
     *
     * @return string customer|staff
     */
    private function passwordResetArea(): string
    {
        $from = (string)$this->request->getQuery('from');
        if ($from === '') {
            $from = (string)$this->request->getData('from');
        }
        if ($from === '') {
            $from = (string)$this->request->getSession()->read('PasswordReset.from');
        }
        $area = $from === 'customer' ? 'customer' : 'staff';
        $this->request->getSession()->write('PasswordReset.from', $area);

        return $area;
    }

    /**
     * @param string $area customer|staff
     * @return string
     */
    private function loginPathForArea(string $area): string
    {
        return $area === 'customer' ? '/account/login' : '/login';
    }

    /**
     * @param string $area customer|staff
     * @return array<string, string>
     */
    private function resetAreaQuery(string $area): array
    {
        return $area === 'customer' ? ['from' => 'customer'] : [];
    }

    /**
     * @param string $name Full name from the form.
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $name = trim((string)preg_replace('/\s+/', ' ', $name));
        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * Confirm a registration email from the mailed token.
     *
     * @param string|null $token Plain token.
     * @return \Cake\Http\Response|null
     */
    public function verifyEmail(?string $token = null): ?Response
    {
        $user = $this->findUserByHashedToken(
            (string)$token,
            'email_verification_token',
            'email_verification_expires',
        );
        if ($user === null) {
            $this->Flash->error(__('That confirmation link is invalid or has expired.'));

            return $this->redirect('/account/login');
        }
        $user->set('email_verified_at', DateTime::now('UTC'));
        $user->set('email_verification_token', null);
        $user->set('email_verification_expires', null);
        $user->set('auth_version', (int)($user->get('auth_version') ?: 1) + 1);
        $this->Users->saveOrFail($user);
        $this->Flash->success(__('Your email is confirmed. You can complete checkout.'));

        return $this->redirect('/account');
    }

    /**
     * Staff TOTP challenge after password login.
     *
     * @return \Cake\Http\Response|null
     */
    public function mfa(): ?Response
    {
        $user = $this->requireStaffForMfa();
        if ($user instanceof Response) {
            return $user;
        }
        if (!$user->get('mfa_enabled')) {
            return $this->redirect('/login/mfa-setup');
        }
        if ($this->request->is('post')) {
            if ($this->mfaLocked($user)) {
                $this->Flash->error(__('Too many authentication codes. Please wait a few minutes and try again.'));

                return null;
            }
            $code = (string)$this->request->getData('code');
            $accepted = $this->acceptMfaCode($user, $code);
            if ($accepted) {
                $this->clearMfaThrottle($user);
                $this->request->getSession()->write(SessionIntegrityMiddleware::SESSION_MFA, true);
                $this->auditMfa('mfa.succeeded', (int)$user->id);

                return $this->redirect('/admin');
            }
            $this->registerMfaFailure($user);
            $this->auditMfa('mfa.failed', (int)$user->id);
            $this->Flash->error(__('That code was not recognised. Try again.'));
        }

        return null;
    }

    /**
     * First-time staff TOTP enrolment.
     *
     * @return \Cake\Http\Response|null
     */
    public function mfaSetup(): ?Response
    {
        $user = $this->requireStaffForMfa();
        if ($user instanceof Response) {
            return $user;
        }
        $session = $this->request->getSession();
        $setup = $this->mfaSetupState((int)$user->id);
        $plain = (string)($setup['secret'] ?? '');
        if ($this->request->is('post')) {
            if ($this->mfaLocked($user)) {
                $this->Flash->error(__('Too many authentication codes. Please wait a few minutes and try again.'));
                $this->set([
                    'secret' => $plain,
                    'otpauth' => TotpService::provisioningUri($plain, (string)$user->email),
                    'recoveryCodes' => [],
                ]);

                return null;
            }
            $step = TotpService::acceptedTimestep($plain, (string)$this->request->getData('code'));
            if ($step !== null) {
                $codes = TotpService::generateRecoveryCodes();
                $user->set('mfa_secret', TotpService::seal($plain));
                $user->set('mfa_enabled', true);
                $user->set('mfa_confirmed_at', DateTime::now('UTC'));
                $user->set('mfa_last_timestep', $step);
                $user->set('mfa_recovery_hashes', TotpService::hashRecoveryCodes($codes));
                $this->Users->saveOrFail($user);
                $session->delete(SensitiveSession::MFA_SETUP);
                $session->write(SessionIntegrityMiddleware::SESSION_MFA, true);
                $this->clearMfaThrottle($user);
                $this->auditMfa('mfa.enrolled', (int)$user->id);
                $this->Flash->success(__('Two-factor authentication is now enabled. Store the recovery codes below.'));
                $this->set([
                    'secret' => $plain,
                    'otpauth' => TotpService::provisioningUri($plain, (string)$user->email),
                    'recoveryCodes' => $codes,
                ]);

                return null;
            }
            $this->registerMfaFailure($user);
            $this->auditMfa('mfa.setup_failed', (int)$user->id);
            $this->Flash->error(__('That code was not recognised. Try again.'));
        }
        $this->set([
            'secret' => $plain,
            'otpauth' => TotpService::provisioningUri($plain, (string)$user->email),
            'recoveryCodes' => [],
        ]);

        return null;
    }

    /**
     * Fold the anonymous session basket into the signed-in customer.
     *
     * @param \App\Model\Entity\User|null $user Signed-in user.
     * @return void
     */
    private function mergeCartAfterLogin(?User $user): void
    {
        if ($user === null) {
            return;
        }
        $token = (string)$this->request->getSession()->read(CartService::SESSION_KEY);
        if ($token === '') {
            return;
        }
        (new CartService())->mergeOnLogin((int)$user->id, $token);
    }

    /**
     * @param \App\Model\Entity\User $user Signed-in user.
     * @return void
     */
    private function persistSessionVersion(User $user): void
    {
        $this->request->getSession()->write(
            SessionIntegrityMiddleware::SESSION_VERSION,
            (int)($user->get('auth_version') ?: 1),
        );
    }

    /**
     * @param \App\Model\Entity\User $user New or existing customer.
     * @return void
     */
    private function sendEmailVerification(User $user): void
    {
        $token = bin2hex(Security::randomBytes(self::RESET_TOKEN_BYTES));
        $user->set('email_verification_token', hash('sha256', $token));
        $user->set('email_verification_expires', DateTime::now('UTC')->addHours(24));
        if (!$this->Users->save($user)) {
            return;
        }
        try {
            $this->getMailer('User')->send('verifyEmail', [$user, $token]);
        } catch (Throwable $exception) {
            Log::error('Could not send the email confirmation: ' . $exception->getMessage());
        }
    }

    /**
     * @param string $token Plain token.
     * @param string $hashField Stored hash column.
     * @param string $expiresField Expiry column.
     * @return \App\Model\Entity\User|null
     */
    private function findUserByHashedToken(string $token, string $hashField, string $expiresField): ?User
    {
        if ($token === '') {
            return null;
        }

        /** @var \App\Model\Entity\User|null $user */
        $user = $this->Users->find()
            ->where([
                $hashField => hash('sha256', $token),
                $expiresField . ' >=' => DateTime::now('UTC'),
            ])
            ->first();

        return $user;
    }

    /**
     * @return \App\Model\Entity\User|\Cake\Http\Response
     */
    private function requireStaffForMfa(): User|Response
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->redirect('/login');
        }
        $user = $this->Users->get((int)$identity->getIdentifier());
        if (!$this->isStaffUser($user)) {
            return $this->redirect('/account');
        }

        return $user;
    }

    /**
     * @param \App\Model\Entity\User $user Staff user.
     * @param string $code TOTP or recovery code.
     * @return bool
     */
    private function acceptMfaCode(User $user, string $code): bool
    {
        $remaining = TotpService::consumeRecoveryCode((string)$user->get('mfa_recovery_hashes'), $code);
        if ($remaining !== null) {
            $user->set('mfa_recovery_hashes', $remaining);
            $this->Users->saveOrFail($user);

            return true;
        }
        try {
            $secret = TotpService::open((string)$user->get('mfa_secret'));
        } catch (Throwable) {
            return false;
        }
        $step = TotpService::acceptedTimestep($secret, $code);
        if ($step === null) {
            return false;
        }
        $last = $user->get('mfa_last_timestep');
        if ($last !== null && (int)$last === $step) {
            return false;
        }
        $user->set('mfa_last_timestep', $step);
        $this->Users->saveOrFail($user);

        return true;
    }

    /**
     * @param int $userId Current user.
     * @return array<string, mixed>
     */
    private function mfaSetupState(int $userId): array
    {
        $session = $this->request->getSession();
        $setup = $session->read(SensitiveSession::MFA_SETUP);
        $now = time();
        if (
            is_array($setup)
            && (int)($setup['user_id'] ?? 0) === $userId
            && (int)($setup['expires_at'] ?? 0) > $now
            && (string)($setup['secret'] ?? '') !== ''
        ) {
            return $setup;
        }
        $setup = [
            'user_id' => $userId,
            'secret' => TotpService::generateSecret(),
            'created_at' => $now,
            'expires_at' => $now + 600,
            'attempts' => 0,
            'nonce' => bin2hex(random_bytes(8)),
        ];
        $session->write(SensitiveSession::MFA_SETUP, $setup);

        return $setup;
    }

    /**
     * @param \App\Model\Entity\User $user Staff user.
     * @return bool
     */
    private function mfaLocked(User $user): bool
    {
        $ip = $this->request->clientIp() ?: 'unknown';
        $sessionId = (string)$this->request->getSession()->id();

        return LoginThrottleMiddleware::isLockedOut($ip, LoginThrottleMiddleware::SCOPE_MFA)
            || LoginThrottleMiddleware::isLockedOut('user:' . $user->id, LoginThrottleMiddleware::SCOPE_MFA)
            || LoginThrottleMiddleware::isLockedOut('session:' . $sessionId, LoginThrottleMiddleware::SCOPE_MFA);
    }

    /**
     * @param \App\Model\Entity\User $user Staff user.
     * @return void
     */
    private function registerMfaFailure(User $user): void
    {
        $ip = $this->request->clientIp() ?: 'unknown';
        $sessionId = (string)$this->request->getSession()->id();
        LoginThrottleMiddleware::registerAttempt($ip, LoginThrottleMiddleware::SCOPE_MFA);
        LoginThrottleMiddleware::registerAttempt('user:' . $user->id, LoginThrottleMiddleware::SCOPE_MFA);
        LoginThrottleMiddleware::registerAttempt('session:' . $sessionId, LoginThrottleMiddleware::SCOPE_MFA);
    }

    /**
     * @param \App\Model\Entity\User $user Staff user.
     * @return void
     */
    private function clearMfaThrottle(User $user): void
    {
        $ip = $this->request->clientIp() ?: 'unknown';
        $sessionId = (string)$this->request->getSession()->id();
        LoginThrottleMiddleware::clear($ip, LoginThrottleMiddleware::SCOPE_MFA);
        LoginThrottleMiddleware::clear('user:' . $user->id, LoginThrottleMiddleware::SCOPE_MFA);
        LoginThrottleMiddleware::clear('session:' . $sessionId, LoginThrottleMiddleware::SCOPE_MFA);
    }

    /**
     * @param string $action Audit verb.
     * @param int $userId Target user.
     * @param int|null $actorUserId Acting user, defaults to the target.
     * @return void
     */
    private function auditMfa(string $action, int $userId, ?int $actorUserId = null): void
    {
        Log::info('Staff MFA event', ['action' => $action, 'user_id' => $userId]);
        try {
            (new AuditLogger())->record(
                $actorUserId ?? $userId,
                $action,
                'users',
                $userId,
                null,
                ['user_id' => $userId],
            );
        } catch (Throwable) {
            // Authentication must still complete if the audit table is unavailable.
        }
    }

    /**
     * @param float|int $started hrtime(true) at the start of the action.
     * @return void
     */
    private function padForgotPasswordTiming(int|float $started): void
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            return;
        }
        $elapsedMs = (hrtime(true) - $started) / 1_000_000;
        $minimum = 250 + random_int(0, 80);
        if ($elapsedMs < $minimum) {
            usleep((int)(($minimum - $elapsedMs) * 1000));
        }
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->Users->getConnection();
    }
}
