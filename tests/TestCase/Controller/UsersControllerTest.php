<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Authentication\Identity;
use Cake\Cache\Cache;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\UsersController Test Case
 *
 * @link \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
        $this->assertResponseContains('Eco Glow Admin');
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
        $this->assertRedirectContains('/admin/contact-messages');
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
        $this->assertRedirectContains('/admin/contact-messages');
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
        $this->assertRedirectContains('/admin/contact-messages');

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
}
