<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\ServiceRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Staff appointment scheduling.
 */
class AppointmentsControllerTest extends TestCase
{
    use AdminAuthTrait;
    use IntegrationTestTrait;

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
    public function testIndexRequiresAuthentication(): void
    {
        $this->get('/admin/appointments');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/appointments');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testIndexOkForMaster(): void
    {
        $this->loginAs(1);
        $this->get('/admin/appointments');
        $this->assertResponseOk();
        $this->assertResponseContains('Appointments');
    }

    /**
     * @return void
     */
    public function testScheduleCreatesAppointment(): void
    {
        $requests = $this->fetchTable('ServiceRequests');
        $request = $requests->newEmptyEntity();
        $request->set('request_number', 'SRV-ADMIN-1');
        $request->set('customer_id', 2);
        $request->set('service_type_id', 1);
        $request->set('contact_name', 'Casey Aitken');
        $request->set('address_line1', '10 Flinders Lane');
        $request->set('suburb', 'Melbourne');
        $request->set('state', 'VIC');
        $request->set('postcode', '3000');
        $request->set('country_code', 'AU');
        $request->set('issue_description', 'Install a lamp.');
        $request->set('attachment_urls', []);
        $request->set('priority', 'normal');
        $request->set('status', ServiceRequest::STATUS_NEW);
        $requests->saveOrFail($request);

        $this->loginAs(1);
        $this->post('/admin/appointments/schedule/' . $request->id, [
            'assigned_staff_user_id' => 1,
            'starts_at' => '2026-08-21T09:00',
            'ends_at' => '2026-08-21T11:00',
            'customer_instructions' => 'Use the side gate.',
        ]);
        $this->assertResponseCode(302);

        $request = $requests->get($request->id, contain: ['ServiceAppointments']);
        $this->assertSame(ServiceRequest::STATUS_SCHEDULED, $request->status);
        $this->assertCount(1, $request->service_appointments);
    }
}
