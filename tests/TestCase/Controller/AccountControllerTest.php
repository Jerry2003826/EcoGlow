<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Service\Authorization\PermissionService;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Customer register, login split, account isolation, and admin denial.
 */
class AccountControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.RolePermissions',
        'app.UserRoles',
        'app.UserPermissionOverrides',
        'app.Customers',
        'app.Products',
        'app.ProductVariants',
        'app.SalesOrders',
        'app.SalesOrderItems',
        'app.ContactMessages',
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
    }

    /**
     * @return void
     */
    public function testRegisterCreatesCustomerRoleOnly(): void
    {
        $this->post('/register', [
            'name' => 'Jordan Lee',
            'email' => 'jordan@example.com',
            'phone' => '0411111111',
            'password' => 'EcoGlow-Test-99',
            'password_confirm' => 'EcoGlow-Test-99',
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/account');

        $user = $this->fetchTable('Users')->find()
            ->where(['email' => 'jordan@example.com'])
            ->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame('Jordan', $user->first_name);

        $customer = $this->fetchTable('Customers')->find()
            ->where(['user_id' => $user->id])
            ->first();
        $this->assertNotNull($customer);
        $this->assertSame('jordan@example.com', $customer->email);
    }

    /**
     * Posted role/status cannot create a staff account.
     *
     * @return void
     */
    public function testRegisterIgnoresMassAssignedRole(): void
    {
        $this->post('/register', [
            'name' => 'Riley Chen',
            'email' => 'riley@example.com',
            'phone' => '0411222333',
            'password' => 'EcoGlow-Test-99',
            'password_confirm' => 'EcoGlow-Test-99',
            'role' => 'owner',
            'status' => 'active',
        ]);

        $user = $this->fetchTable('Users')->find()
            ->where(['email' => 'riley@example.com'])
            ->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', (string)$user->get('role'));
        $this->assertFalse(
            (new PermissionService())->hasAny((int)$user->id),
        );
    }

    /**
     * @return void
     */
    public function testCustomerLoginGoesToAccount(): void
    {
        $this->post('/account/login', [
            'email' => 'customer-a@example.com',
            'password' => 'password',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/account');
        $this->assertRedirectNotContains('/admin');
    }

    /**
     * Same authenticator: a customer posting to the staff form still lands on /account.
     *
     * @return void
     */
    public function testCustomerLoginOnStaffFormGoesToAccount(): void
    {
        $this->post('/login', [
            'email' => 'customer-a@example.com',
            'password' => 'password',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/account');
        $this->assertRedirectNotContains('/admin');
    }

    /**
     * @return void
     */
    public function testStaffLoginFromCustomerFormGoesToAdmin(): void
    {
        $this->post('/account/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
    }

    /**
     * @return void
     */
    public function testCustomerCannotAccessAdmin(): void
    {
        $this->loginAs(4);
        $this->get('/admin');
        $this->assertResponseCode(403);
        $this->get('/admin/orders');
        $this->assertResponseCode(403);
        $this->get('/admin/users');
        $this->assertResponseCode(403);
    }

    /**
     * Customer A must not see customer B's order.
     *
     * @return void
     */
    public function testCustomerCannotViewAnotherCustomersOrder(): void
    {
        $orders = $this->fetchTable('SalesOrders');
        $orderA = $orders->newEmptyEntity();
        $orderA->set('order_number', 'SO-A-001');
        $orderA->set('customer_id', 2);
        $orderA->set('status', 'confirmed');
        $orderA->set('source_channel', 'web');
        $orderA->set('subtotal_cents', 24900);
        $orderA->set('grand_total_cents', 24900);
        $orderA->set('promised_delivery_date', Date::parse('2026-08-20'));
        $orderA->set('placed_at', '2026-08-10 00:00:00');
        $orderA->set('metadata', []);
        $orders->saveOrFail($orderA);

        $orderB = $orders->newEmptyEntity();
        $orderB->set('order_number', 'SO-B-001');
        $orderB->set('customer_id', 3);
        $orderB->set('status', 'processing');
        $orderB->set('source_channel', 'web');
        $orderB->set('subtotal_cents', 18900);
        $orderB->set('grand_total_cents', 18900);
        $orderB->set('promised_delivery_date', Date::parse('2026-08-22'));
        $orderB->set('placed_at', '2026-08-11 00:00:00');
        $orderB->set('metadata', []);
        $orders->saveOrFail($orderB);

        $this->loginAs(4);
        $this->get('/account/orders/' . $orderA->id);
        $this->assertResponseOk();
        $this->assertResponseContains('SO-A-001');
        $this->assertResponseContains('20 Aug 2026');

        $this->get('/account/orders/' . $orderB->id);
        $this->assertResponseCode(404);
    }

    /**
     * @param int $userId UsersFixture id.
     * @return void
     */
    private function loginAs(int $userId): void
    {
        $user = $this->fetchTable('Users')->get($userId);
        $this->session([
            'AuthV2' => $userId,
            'AuthVersion' => (int)($user->get('auth_version') ?: 1),
        ]);
    }
}
