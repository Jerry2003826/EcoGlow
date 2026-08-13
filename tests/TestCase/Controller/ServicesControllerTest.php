<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\ServiceRequest;
use Authentication\Identity;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Customer installation bookings and cross-account isolation.
 */
class ServicesControllerTest extends TestCase
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
        'app.ServiceTypes',
        'app.ServiceRequests',
        'app.ServiceAppointments',
        'app.FeatureFlags',
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
    public function testBookRedirectsGuestsToCustomerLogin(): void
    {
        $this->get('/services/book');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/account/login');
    }

    /**
     * @return void
     */
    public function testCustomerCanSubmitBooking(): void
    {
        $this->loginAs(4);
        $this->get('/services/book');
        $this->assertResponseOk();
        $this->assertResponseContains('Book installation or repair');

        $this->post('/services/book', [
            'service_type_id' => 1,
            'contact_name' => 'Casey Aitken',
            'contact_phone' => '0400000004',
            'address_line1' => '10 Flinders Lane',
            'suburb' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
            'preferred_window' => 'morning',
            'issue_description' => 'Please install the Marlow floor lamp in the lounge.',
        ]);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/account/bookings');

        $request = $this->fetchTable('ServiceRequests')->find()
            ->where(['customer_id' => 2])
            ->first();
        $this->assertNotNull($request);
        $this->assertSame(ServiceRequest::STATUS_NEW, $request->status);
    }

    /**
     * @return void
     */
    public function testCustomerCannotViewAnotherCustomersBooking(): void
    {
        $requests = $this->fetchTable('ServiceRequests');
        $theirs = $requests->newEmptyEntity();
        $theirs->set('request_number', 'SRV-B-HIDDEN');
        $theirs->set('customer_id', 3);
        $theirs->set('service_type_id', 1);
        $theirs->set('contact_name', 'Blair Nguyen');
        $theirs->set('address_line1', '1 Queen Street');
        $theirs->set('suburb', 'Melbourne');
        $theirs->set('state', 'VIC');
        $theirs->set('postcode', '3000');
        $theirs->set('country_code', 'AU');
        $theirs->set('issue_description', 'Repair a pendant.');
        $theirs->set('attachment_urls', []);
        $theirs->set('priority', 'normal');
        $theirs->set('status', ServiceRequest::STATUS_NEW);
        $requests->saveOrFail($theirs);

        $this->loginAs(4);
        $this->get('/account/bookings/' . $theirs->id);
        $this->assertResponseCode(404);
    }

    /**
     * @param int $userId UsersFixture id.
     * @return void
     */
    private function loginAs(int $userId): void
    {
        $this->session([
            'Auth' => new Identity($this->fetchTable('Users')->get($userId)),
        ]);
    }
}
