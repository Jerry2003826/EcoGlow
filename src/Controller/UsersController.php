<?php
declare(strict_types=1);

namespace App\Controller;

use App\Middleware\LoginThrottleMiddleware;
use Cake\Http\Response;

/**
 * Users Controller
 *
 * Handles administrator authentication for the Eco Glow Lighting admin area.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class UsersController extends AppController
{
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
        $this->Authentication->allowUnauthenticated(['login', 'logout']);
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
}
