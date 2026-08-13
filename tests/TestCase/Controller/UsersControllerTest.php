<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Authentication\Identity;
use Cake\Cache\Cache;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;

/**
 * App\Controller\UsersController Test Case
 *
 * @link \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    /**
     * Reply shown after *every* "forgot password" submission.
     *
     * Pinned in one constant on purpose: the known-address and unknown-address
     * tests both assert against it, so the two responses cannot drift apart
     * and start leaking which addresses have an account.
     *
     * @var string
     */
    private const RESET_REQUESTED_MESSAGE =
        'If that email address has an account, we have sent a password reset link. Please check your inbox.';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        // Start every test from a clean throttle state so failed-login counters
        // do not leak between tests.
        Cache::clear('login_throttle');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Cache::clear('login_throttle');

        parent::tearDown();
    }

    /**
     * Test that the login page loads.
     *
     * @return void
     */
    public function testLoginGet(): void
    {
        $this->get('/login');

        $this->assertResponseOk();
        $this->assertResponseContains('Welcome back');
    }

    /**
     * Test a successful login redirects to the admin area.
     *
     * @return void
     */
    public function testLoginSuccess(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
    }

    /**
     * Test that invalid credentials show an error and stay on the page.
     *
     * @return void
     */
    public function testLoginFailure(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Invalid email or password');
    }

    /**
     * Test that a malicious absolute redirect target is ignored.
     *
     * @return void
     */
    public function testLoginRejectsAbsoluteRedirect(): void
    {
        $this->get('/login?redirect=https://evil.example.com/phish');

        // After successful auth the user must never leave the app.
        $this->post('/login?redirect=https://evil.example.com/phish', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
    }

    /**
     * Test logout clears the session.
     *
     * @return void
     */
    public function testLogout(): void
    {
        $this->session([
            'Auth' => new Identity(
                $this->fetchTable('Users')->get(1),
            ),
        ]);

        $this->get('/logout');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * Test logging out while already signed out does not loop back through login.
     *
     * @return void
     */
    public function testLogoutWhileUnauthenticated(): void
    {
        $this->get('/logout');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
        $this->assertRedirectNotContains('redirect=');
    }

    /**
     * Test that repeated failed logins are throttled.
     *
     * After the fifth failure the form is locked and even the (subsequent)
     * request is refused with a lockout message instead of the usual
     * "invalid credentials" error.
     *
     * @return void
     */
    public function testLoginThrottlesAfterRepeatedFailures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
            $this->assertResponseContains('Invalid email or password');
        }

        // The sixth attempt is intercepted by the throttle middleware before
        // authentication runs and bounced back to the login form.
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');

        // The follow-up GET shows the lockout reason.
        $this->get('/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Too many failed login attempts');
    }

    /**
     * Test that a correct password is refused while locked out.
     *
     * This is the case a controller-only throttle cannot cover: because the
     * middleware blocks the POST before authentication, no identity is
     * persisted and the admin area stays unreachable.
     *
     * @return void
     */
    public function testCorrectPasswordRefusedWhileLockedOut(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Correct credentials during the lockout window are short-circuited.
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');

        // The session was never authenticated, so the admin area is still gated.
        $this->get('/admin/contact-messages');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * Test that a successful login clears the failed-attempt counter.
     *
     * @return void
     */
    public function testSuccessfulLoginResetsThrottle(): void
    {
        // Four failures (below the lockout threshold of five).
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // A correct login must succeed and reset the counter.
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');

        // Because the counter was reset, a fresh wrong attempt is treated as
        // the first failure again, not a continuation toward lockout.
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);
        $this->assertResponseContains('Invalid email or password');
        $this->assertResponseNotContains('Too many failed login attempts');
    }

    /**
     * Test that unauthenticated access to the admin area redirects to login.
     *
     * @return void
     */
    public function testAdminRequiresAuthentication(): void
    {
        $this->get('/admin/contact-messages');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * Test that the "forgot password" form loads for anonymous visitors.
     *
     * @return void
     */
    public function testForgotPasswordGet(): void
    {
        $this->get('/forgot-password');

        $this->assertResponseOk();
        $this->assertResponseContains('Forgot your password?');
    }

    /**
     * Test that a known address is emailed a reset link.
     *
     * Also covers what is stored: the column must hold a hash rather than the
     * token from the link, so that a database dump cannot be replayed.
     *
     * @return void
     */
    public function testForgotPasswordSendsResetLink(): void
    {
        $this->post('/forgot-password', ['email' => 'admin@example.com']);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
        $this->assertFlashMessage(self::RESET_REQUESTED_MESSAGE);

        $this->assertMailCount(1);
        $this->assertMailSentTo('admin@example.com');
        $this->assertMailSubjectContains('Reset your Eco Glow Lighting password');
        $this->assertMailContainsText('/reset-password/');

        $token = $this->resetTokenFromMail();
        $user = $this->fetchTable('Users')->get(1);

        $this->assertNotSame($token, $user->password_reset_token, 'The raw token must not be stored.');
        $this->assertSame(hash('sha256', $token), $user->password_reset_token);
        $this->assertNotNull($user->password_reset_expires);
        $this->assertSame(
            DateTime::now()->addHours(1)->format('Y-m-d H:i'),
            $user->password_reset_expires->format('Y-m-d H:i'),
            'A reset link should be valid for one hour.',
        );
    }

    /**
     * Test that an unknown address produces exactly the same reply.
     *
     * Any difference here — wording, status code or redirect target — would
     * let an attacker enumerate which addresses hold an admin account.
     *
     * @return void
     */
    public function testForgotPasswordDoesNotRevealUnknownAddresses(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
        $this->assertFlashMessage(self::RESET_REQUESTED_MESSAGE);

        $this->assertNoMailSent();
        $this->assertNull(
            $this->fetchTable('Users')->get(1)->password_reset_token,
            'An unrelated address must not touch an existing account.',
        );
    }

    /**
     * Test that reset requests are throttled the same way logins are.
     *
     * @return void
     */
    public function testForgotPasswordThrottlesRepeatedRequests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => 'admin@example.com']);
            $this->assertResponseCode(302);
        }

        $this->post('/forgot-password', ['email' => 'admin@example.com']);

        $this->assertResponseOk();
        $this->assertResponseContains('Too many password reset requests');
        $this->assertMailCount(5, 'The throttled request must not send a sixth email.');
    }

    /**
     * Test that an unknown token is refused.
     *
     * @return void
     */
    public function testResetPasswordRejectsInvalidToken(): void
    {
        $this->get('/reset-password/' . str_repeat('a', 64));

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/forgot-password');
        $this->assertFlashMessage('That password reset link is invalid or has expired. Please request a new one.');
    }

    /**
     * Test that a token past its one-hour window is refused.
     *
     * @return void
     */
    public function testResetPasswordRejectsExpiredToken(): void
    {
        $token = 'expired-token';
        $this->storeResetToken($token, DateTime::now()->subMinutes(1));

        $this->get('/reset-password/' . $token);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/forgot-password');
        $this->assertFlashMessage('That password reset link is invalid or has expired. Please request a new one.');
    }

    /**
     * Test that a mismatched confirmation keeps the user on the form.
     *
     * @return void
     */
    public function testResetPasswordRejectsMismatchedConfirmation(): void
    {
        $token = 'valid-token';
        $this->storeResetToken($token, DateTime::now()->addHours(1));

        $this->post('/reset-password/' . $token, [
            'password' => 'brand-new-password',
            'confirm_password' => 'something-else',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The two passwords do not match.');

        // The old password still works, and the link is still usable.
        $this->assertNotNull($this->fetchTable('Users')->get(1)->password_reset_token);
    }

    /**
     * Test the happy path: reset, then sign in with the new password.
     *
     * The link is also replayed afterwards to prove it is single-use.
     *
     * @return void
     */
    public function testResetPasswordSucceedsAndAllowsLoginWithNewPassword(): void
    {
        $this->post('/forgot-password', ['email' => 'admin@example.com']);
        $token = $this->resetTokenFromMail();

        $this->get('/reset-password/' . $token);
        $this->assertResponseOk();
        $this->assertResponseContains('Choose a new password');

        $this->post('/reset-password/' . $token, [
            'password' => 'brand-new-password',
            'confirm_password' => 'brand-new-password',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
        $this->assertFlashMessage('Your password has been updated. Please sign in.');

        // Used once, gone: replaying the same link lands back on the request form.
        $this->get('/reset-password/' . $token);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/forgot-password');

        // The old password is no longer accepted...
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password']);
        $this->assertResponseOk();
        $this->assertResponseContains('Invalid email or password');

        // ...and the new one is.
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'brand-new-password']);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
    }

    /**
     * Test that the reset form cannot be used to change the account's email.
     *
     * @return void
     */
    public function testResetPasswordIgnoresOtherPostedFields(): void
    {
        $token = 'valid-token';
        $this->storeResetToken($token, DateTime::now()->addHours(1));

        $this->post('/reset-password/' . $token, [
            'password' => 'brand-new-password',
            'confirm_password' => 'brand-new-password',
            'email' => 'attacker@example.com',
        ]);

        $this->assertResponseCode(302);
        $this->assertSame('admin@example.com', $this->fetchTable('Users')->get(1)->email);
    }

    /**
     * Write a reset token straight onto the seeded account.
     *
     * Bypasses the entity so the test sets up state the same way the database
     * holds it — a hash, never the token itself.
     *
     * @param string $token The plain-text token.
     * @param \Cake\I18n\DateTime $expires When the token stops working.
     * @return void
     */
    protected function storeResetToken(string $token, DateTime $expires): void
    {
        $this->fetchTable('Users')->updateAll(
            [
                'password_reset_token' => hash('sha256', $token),
                'password_reset_expires' => $expires->format('Y-m-d H:i:s'),
            ],
            ['id' => 1],
        );
    }

    /**
     * Pull the plain-text token out of the reset email that was just sent.
     *
     * @return string
     */
    protected function resetTokenFromMail(): string
    {
        $messages = TestEmailTransport::getMessages();
        $this->assertNotEmpty($messages, 'No reset email was sent.');

        $body = end($messages)->getBodyText();
        $this->assertSame(
            1,
            preg_match('~/reset-password/([0-9a-f]{64})~', $body, $matches),
            'The reset email should contain a link with a 32-byte hex token.',
        );

        return $matches[1];
    }
}
