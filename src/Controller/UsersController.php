<?php
declare(strict_types=1);

namespace App\Controller;

use App\Middleware\LoginThrottleMiddleware;
use App\Model\Entity\User;
use App\Model\Table\UsersTable;
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
            'logout',
            'forgotPassword',
            'resetPassword',
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

        // An already-valid session always wins, even from a locked-out IP.
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            LoginThrottleMiddleware::clear($ip);

            $target = $this->Authentication->getLoginRedirect() ?? '/admin/contact-messages';
            // Only allow relative redirect targets to prevent open redirects.
            if (str_starts_with($target, 'http') || str_starts_with($target, '//')) {
                $target = '/admin/contact-messages';
            }

            return $this->redirect($target);
        }

        // Locked-out POSTs are intercepted by the middleware and redirected
        // here as a GET; surface the reason to the user.
        if (LoginThrottleMiddleware::isLockedOut($ip)) {
            $this->Flash->error(
                __('Too many failed login attempts. Please wait a few minutes and try again.'),
            );

            return null;
        }

        if ($this->request->is('post')) {
            LoginThrottleMiddleware::registerFailure($ip);
            $this->Flash->error(__('Invalid email or password'));
        }

        return null;
    }

    /**
     * Logout method.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
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
        if (!$this->request->is('post')) {
            return null;
        }

        $ip = $this->request->clientIp() ?: 'unknown';
        $scope = LoginThrottleMiddleware::SCOPE_PASSWORD_RESET;

        if (LoginThrottleMiddleware::isLockedOut($ip, $scope)) {
            $this->Flash->error(
                __('Too many password reset requests. Please wait a few minutes and try again.'),
            );

            return null;
        }

        // Count the attempt before any lookup so the counter cannot be
        // out-run by a script hammering the endpoint.
        LoginThrottleMiddleware::registerAttempt($ip, $scope);

        $email = trim((string)$this->request->getData('email'));

        // A malformed address is rejected on the spot. This reveals nothing
        // about who holds an account, so it is safe to be specific here.
        if (!Validation::email($email)) {
            $this->Flash->error(__('Please enter a valid email address.'));

            return null;
        }

        $user = $this->Users->find()
            ->where(['email' => $email])
            ->first();

        if ($user !== null) {
            $this->sendResetLink($user);
        }

        $this->Flash->success(__(
            'If that email address has an account, we have sent a password reset link. Please check your inbox.',
        ));

        return $this->redirect(['action' => 'login']);
    }

    /**
     * Choose a new password using a link from the reset email.
     *
     * @param string|null $token The plain-text token taken from the URL.
     * @return \Cake\Http\Response|null
     */
    public function resetPassword(?string $token = null): ?Response
    {
        $user = $this->findUserByResetToken((string)$token);

        if ($user === null) {
            $this->Flash->error(
                __('That password reset link is invalid or has expired. Please request a new one.'),
            );

            return $this->redirect(['action' => 'forgotPassword']);
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

                if ($this->Users->save($user)) {
                    LoginThrottleMiddleware::clear(
                        $this->request->clientIp() ?: 'unknown',
                        LoginThrottleMiddleware::SCOPE_PASSWORD_RESET,
                    );

                    $this->Flash->success(__('Your password has been updated. Please sign in.'));

                    return $this->redirect(['action' => 'login']);
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
     * @return void
     */
    protected function sendResetLink(User $user): void
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
            $this->getMailer('User')->send('resetPassword', [$user, $token, self::RESET_TOKEN_TTL_HOURS]);
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
}
